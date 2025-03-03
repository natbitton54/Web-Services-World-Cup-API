<?php

namespace App\Controllers;

use App\Exceptions\HttpInvalidInputException;
use App\Exceptions\HttpNotFoundException;
use App\Models\StadiumsModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


/**
 * Class StadiumsController
 *
 * Handles the stadium-related API requests such as retrieving stadiums where World Cup matches took place
 * and retrieving matches for specific stadiums.
 *
 * @package App\Controllers
 */
class StadiumsController extends BaseController
{
    /**
     * StadiumsController constructor.
     *
     * @param StadiumsModel $stadium_model The StadiumsModel instance for interacting with the stadium data.
     */
    public function __construct(private StadiumsModel $stadium_model) {}

    /**
     * Handle GET /stadiums - Retrieve stadiums where World Cup matches took place.
     *
     * @param Request $request The request object containing query parameters.
     * @param Response $response The response object to return.
     *
     * @return Response The response object with the list of stadiums in JSON format.
     *
     * @throws HttpInvalidInputException If the filters provided (capacity, country, city) are invalid.
     */
    public function handleGetStadiums(Request $request, Response $response): Response
    {
        // Step 1: Extract the list of filters
        $filters = $request->getQueryParams();

        // Validate capacity
        if (isset($filters['capacity'])) {
            if (!is_numeric($filters['capacity']) || (int)$filters['capacity'] < 0) {
                throw new HttpInvalidInputException($request, "Capacity must be a non-negative number.");
            }
        }

        // Validate country and city
        if (isset($filters['country']) && empty(trim($filters['country']))) {
            throw new HttpInvalidInputException($request, "Country must be a non-empty string.");
        }
        if (isset($filters['city']) && empty(trim($filters['city']))) {
            throw new HttpInvalidInputException($request, "City must be a non-empty string.");
        }

        //  Extract sorting parameters from filters
        $sort_by = $filters['sort_by'] ?? "stadium_name";
        $sort_order = strtoupper($filters["sort_order"] ?? 'ASC');

        //  Ensures sorting order is valid (either "ASC" or "DESC")
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'ASC';
        }

        // validate pagination
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        //Set pagination options in the player model
        $this->stadium_model->setPaginationOptions(
            $pageCount,
            $page_size
        );
        // Fetch the list of stadiums from the database
        $stadiums = $this->stadium_model->getStadiums($filters, $sort_by, $sort_order);

        // Step 3: Prepare the HTTP response message
        // Step 3.a: Encode the response payload in JSON
        return $this->renderJson($response, $stadiums, 200);
    }

    /**
     * Handle GET /stadiums/{stadium_id}/matches - Retrieve matches for a specific stadium based on stadium ID.
     *
     * @param Request $request The request object containing the URI parameters and query filters.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the stadium ID.
     *
     * @return Response The response object with matches data in JSON format.
     *
     * @throws HttpInvalidInputException If the stadium ID format or filters (tournament_name, stage) are invalid.
     * @throws HttpNotFoundException If no matches are found for the given stadium ID.
     */
    public function handleGetMatchesByStadiumId(Request $request, Response $response, array $uri_args): Response
    {
        // Step 1: Extract stadium ID from the URL arguments
        $stadium_id = $uri_args["stadium_id"] ?? null;

        // Validate stadium_id format (S-###)
        $regex_stadium_id = '/^S-\d{3}$/';
        if (!$stadium_id || !preg_match($regex_stadium_id, $stadium_id)) {
            throw new HttpInvalidInputException($request, "The provided stadium ID is invalid!  format (S-###) expected");
        }

        // Extract query parameters (filters)
        $filters = $request->getQueryParams();

        // Validate filters for tournament name and stage
        if (isset($filters['tournament_name'])) {
            $tournament_name = trim($filters['tournament_name']);
            if (empty($tournament_name) || !is_string($tournament_name)) {
                throw new HttpInvalidInputException($request, "The 'tournament_name' filter must be a non-empty string.");
            }
        }
        if (isset($filters['stage'])) {
            $stage = trim($filters['stage']);
            if (empty($stage) || !is_string($stage)) {
                throw new HttpInvalidInputException($request, "The 'stage' filter must be a valid non-empty string.");
            }
        }

        // validate pagination
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);


        //  Set pagination options in the stadium model
        $this->stadium_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Fetch matches from the db and filters
        $matches = $this->stadium_model->getMatchesByStadiumId($stadium_id, $filters);

        // If no stadium are found, throw a 404 error
        if ($matches === false) {
            throw new HttpNotFoundException($request, "No matches found for stadium ID: $stadium_id or stadium does not exist.");
        }

        // Encode the response payload in JSON and return it
        return $this->renderJson($response, $matches, 200);
    }

}
