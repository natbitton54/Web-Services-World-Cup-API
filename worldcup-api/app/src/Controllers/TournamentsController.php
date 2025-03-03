<?php

namespace App\Controllers;

use App\Core\AppSettings;
use App\Exceptions\HttpInvalidInputException;
use App\Exceptions\HttpNotFoundException;
use Slim\Exception\HttpNotFoundException as ExceptionHttpNotFoundException;
use App\Models\TournamentsModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class TournamentsController
 *
 * Handles API requests related to tournaments such as retrieving a list of tournaments,
 * fetching details of a specific tournament, and retrieving matches for a given tournament.
 *
 * @package App\Controllers
 */
class TournamentsController extends BaseController
{
    /**
     * TournamentsController constructor.
     *
     * @param TournamentsModel $tournaments_model The TournamentsModel instance for interacting with tournament data.
     */
    public function __construct(private TournamentsModel $tournaments_model) {}

    /**
     * Handle GET /tournaments - Retrieve a list of tournaments based on filters.
     *
     * @param Request $request The request object containing query parameters.
     * @param Response $response The response object to return.
     *
     * @return Response The response object with the list of tournaments in JSON format.
     *
     * @throws HttpNotFoundException If no tournaments are found for the given filters.
     */
    public function handleGetTournaments(Request $request, Response $response): Response
    {
        //? step 1: extract the list of filters
        $filters = $request->getQueryParams();
        // dd($filters);

        // validate pagination
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

         // Set pagination options in the model
        $this->tournaments_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // ?step 2: fetch the list of tourneys from the db
        $tournaments = $this->tournaments_model->getTournaments($filters);

         // If no data is found, throw a NotFoundException
        if (empty($tournaments)) {
            throw new HttpNotFoundException($request, "No tournaments found for that criteria.");
        }

        //! step 3: prepare the http response message
        //! step 3.a: encode the response payload in json
        return $this->renderJson($response, $tournaments);
    }

    /**
     * Handle GET /tournaments/{tournament_id} - Retrieve information about a specific tournament.
     *
     * @param Request $request The request object.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the tournament ID.
     *
     * @return Response The response object with the tournament information in JSON format.
     *
     * @throws HttpInvalidInputException If the tournament ID format is invalid.
     * @throws ExceptionHttpNotFoundException If no tournament is found with the given ID.
     */
    public function handleGetTournamentById(Request $request, Response $response, array $uri_args): Response
    {
        // Retrieve tournament ID from the URI arguments, or set it to null if not provided
        $tournament_id = $uri_args['tournament_id'];

        // Validate player_id format ... WC-xxxx
        $regex_tournament_id = "/^WC-\d{4}$/";
        if (preg_match($regex_tournament_id, $tournament_id) === 0) {
            throw new HttpInvalidInputException(
                $request,
                "The provided Tournament ID is invalid. Expected format: WC-xxxx or for example WC-2026."
            );
        }

        // Fetch team info by the provided id
        $tournament_info = $this->tournaments_model->getTournamentById($tournament_id);

        // If no data is found, throw a NotFoundException
        if ($tournament_info === false) {
            throw new ExceptionHttpNotFoundException( //  404
                $request,
                "No Tournament found with ID: $tournament_id"
            );
        }

       // Return the data as a JSON response with a 200 OK status
        return $this->renderJson($response, $tournament_info, 200);
    }


    /**
     * Handle GET /tournaments/{tournament_id}/matches - Retrieve matches for a specific tournament.
     *
     * @param Request $request The request object containing query filters.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the tournament ID.
     *
     * @return Response The response object with the tournament matches in JSON format.
     *
     * @throws HttpInvalidInputException If the tournament ID format is invalid.
     * @throws HttpNotFoundException If no matches are found for the given tournament ID or filters.
     */
    public function handleGetTournamentMatchesByID(Request $request, Response $response, array $uri_args): Response
    {
        // Retrieve tournament ID from the URI arguments, or set it to null if not provided
        $tournament_id = $uri_args["tournament_id"] ?? null;

        // Validate tournament_id format (WC-YYYY, e.g., WC-2022)
        $regex_tournament_id = '/^WC-\d{4}$/';
        if (!$tournament_id || !preg_match($regex_tournament_id, $tournament_id)) {
            throw new HttpInvalidInputException($request, "The provided tournament ID is invalid! Expected format: WC-XXXX (e.g., WC-2022).");
        }

        // Extract query parameters (filters)
        $filters = $request->getQueryParams();
        $filter_values = [];

        // Apply stage_name filter
        if (isset($filters['stage_name'])) {
            $filter_values['stage_name'] = $filters['stage_name'];
        }

        // validate pagination
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        // Set pagination in the model class
        $this->tournaments_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Fetch tourneys from the db by a given id
        $matches = $this->tournaments_model->getTournamentMatchesById($tournament_id, $filter_values);

        // If no data is found, throw a NotFoundException
        if ($matches === false || empty($matches)) {
            throw new HttpNotFoundException($request, "No matches found for tournament ID: $tournament_id or tournament does not exist.");
        }

        // Return the data as a JSON response with a 200 OK status
        return $this->renderJson($response, $matches, 200);
    }
}
