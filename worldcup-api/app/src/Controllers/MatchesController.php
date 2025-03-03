<?php

namespace App\Controllers;

use App\Core\AppSettings;
use App\Exceptions\HttpInvalidInputException;
use App\Exceptions\HttpNotFoundException;
use App\Models\MatchesModel;
use DI\NotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException as ExceptionHttpNotFoundException;

/**
 * Class MatchesController
 *
 * Handles the match-related API requests such as retrieving players who played in a specific match.
 *
 * @package App\Controllers
 */
class MatchesController extends BaseController
{
    /**
     * MatchesController constructor.
     *
     * @param MatchesModel $match_model The MatchesModel instance for interacting with the match data.
     */
    public function __construct(private MatchesModel $match_model) {}

    /**
     * Handle GET /matches/{match_id}/players - Retrieve players who played in a specific match.
     *
     * @param Request $request The request object containing the query and URI parameters.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the match ID.
     *
     * @return Response The response object with the players' data in JSON format.
     *
     * @throws HttpInvalidInputException If the match ID format is invalid or if an invalid position is provided.
     * @throws NotFoundException If no match data is found for the given match ID.
     */
    public function handlePlayerMatchesPlayedById(Request $request, Response $response, array $uri_args): Response
    {
        // Retrieve match ID from the URI arguments, or set it to null if not provided
        $match_id = $uri_args["match_id"] ?? null;

        // Validate match ID format (M-YYYY-MM)
        $regex_match_id = '/^M-\d{4}-\d{2}$/';
        if (!$match_id || !preg_match($regex_match_id, $match_id)) {
            throw new HttpInvalidInputException($request, "The provided match ID is invalid! Expected format: M-YYYY-MM.");
        }

        // Retrieve filters from query parameters
        $filters = $request->getQueryParams();
        $filter_values = [];

        // Validate the 'position' filter if it's set
        if (isset($filters['position'])) {
            $validPositions = ['goalkeeper', 'forward', 'defender', 'midfielder'];
            $position = strtolower(trim($filters['position']));
            if (!in_array($position, $validPositions)) {
                // Throw an error if the position is not one of the allowed values
                throw new HttpInvalidInputException($request, "Invalid position provided. Valid positions are: goalkeeper, forward, defender, midfielder.");
            }
            // Store the valid position filter
            $filter_values['position'] = $filters['position'];
        }

        // Validate and retrieve pagination parameters (e.g., page size, page number)
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        // Set pagination options in the match model
        $this->match_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Fetch player match data from the database using the match ID and filters
        $match = $this->match_model->getPlayersMatchPlayedById($match_id, $filter_values);

        // If no data is found, throw a NotFoundException
        if (!$match) {
            throw new NotFoundException("No match data found for the given match ID");
        }

        // Return the match data as a JSON response with a 200 OK status
        return $this->renderJson($response, $match, 200);
    }
}
