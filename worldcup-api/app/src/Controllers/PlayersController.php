<?php

namespace App\Controllers;

use App\Core\AppSettings;
use App\Exceptions\HttpInvalidInputException;
use App\Exceptions\HttpNotFoundException;
use App\Models\PlayersModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException as ExceptionHttpNotFoundException;


/**
 * Class PlayersController
 *
 * Handles the player-related API requests such as retrieving players, player goals, and player appearances.
 *
 * @package App\Controllers
 */
class PlayersController extends BaseController
{
    /**
     * PlayersController constructor.
     *
     * @param PlayersModel $player_model The PlayersModel instance for interacting with the player data.
     */
    public function __construct(private PlayersModel $player_model) {}

    /**
     * Handle GET /players - Retrieve a list of players based on the provided filters.
     *
     * @param Request $request The request object containing query parameters.
     * @param Response $response The response object to return.
     *
     * @return Response The response object with the list of players in JSON format.
     *
     * @throws HttpNotFoundException If no players match the provided filters.
     */
    public function handleGetPlayers(Request $request, Response $response): Response
    {
        // Step 1: Extract the list of filters from the query parameters
        $filters = $request->getQueryParams();

        // Step 2: Validate and retrieve pagination parameters (e.g., page size, page number)
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        // Step 3: Extract sorting parameters from filters
        $sort_by = $filters['sort_by'] ?? "last_name"; // Default sort by "last_name"
        $sort_order = strtoupper($filters["sort_order"] ?? 'ASC'); // Default to ASC

        // Step 4: Ensure sorting order is valid (either "ASC" or "DESC")
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'ASC'; // Default to ASC if invalid value is provided
        }

        // Step 5: Set pagination options in the player model
        $this->player_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Step 6: Fetch the list of players from the database using filters and sorting options
        $players = $this->player_model->getPlayers($filters, $sort_by, $sort_order);

        // Step 7: If no players are found, throw a 404 error
        if (empty($players)) {
            throw new HttpNotFoundException($request, "No players found for that criteria.");
        }

        // Step 8: Encode the response payload in JSON and return it
        return $this->renderJson($response, $players);
    }


    /**
     * Handle GET /players/{player_id} - Retrieve player information based on player ID.
     *
     * @param Request $request The request object containing the URI parameters.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the player ID.
     *
     * @return Response The response object with player info in JSON format.
     *
     * @throws HttpInvalidInputException If the player ID format is invalid.
     * @throws ExceptionHttpNotFoundException If no player is found with the provided player ID.
     */
    public function handleGetPlayersById(Request $request, Response $response, array $uri_args): Response
    {
        // Step 1: Extract player ID from the URL arguments
        $player_id = $uri_args['player_id'];

        // Step 2: Validate player_id format using a regular expression (e.g., "P-12345" or "P-123456")
        $regex_player_id = '/^P-\d{5,6}$/';
        if (preg_match($regex_player_id, $player_id) === 0) {
            throw new HttpInvalidInputException(
                $request,
                "The provided player ID is invalid. Expected format: P-12345 or P-123456."
            );
        }

        // Step 3: Fetch player information from the database
        $player_info = $this->player_model->getPlayersById($player_id);

        // Step 4: Handle case where no player is found
        if ($player_info === false) {
            throw new HttpNotFoundException( // Fix incorrect exception name
                $request,
                "No player found with ID: $player_id"
            );
        }

        // Step 5: Return the player information as a JSON response with HTTP 200 status
        return $this->renderJson($response, $player_info, 200);
    }


    /**
     * Handle GET /players/{player_id}/goals - Retrieve goals for a specific player.
     *
     * @param Request $request The request object containing the URI parameters and query filters.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the player ID.
     *
     * @return Response The response object with goals data in JSON format.
     *
     * @throws HttpInvalidInputException If the player ID or tournament/match format is invalid.
     * @throws ExceptionHttpNotFoundException If no goals are found for the given player ID.
     */
    public function handleGetPlayerGoals(Request $request, Response $response, array $uri_args): Response
    {
        // Step 1: Extract player ID from the URL arguments
        $player_id = $uri_args["player_id"] ?? null;

        // Step 2: Validate player_id format (P-##### or P-######)
        $regex_player_id = '/^P-\d{5,6}$/';
        if (!$player_id || !preg_match($regex_player_id, $player_id)) {
            throw new HttpInvalidInputException($request, "The provided player ID is invalid!");
        }

        // Step 3: Extract query parameters (filters)
        $filters = $request->getQueryParams();
        $filter_values = [];

        // Step 4: Validate and map tournament (user-facing) to tournament_id (database)
        if (isset($filters['tournament'])) {
            if (!preg_match('/^WC-\d{4}$/', $filters['tournament'])) {
                throw new ExceptionHttpNotFoundException($request, "Tournament must be in WC-YYYY format.");
            }
            $filter_values['tournament_id'] = $filters['tournament'];
        }

        // Step 5: Validate and map match (user-facing) to match_id (database)
        if (isset($filters['match'])) {
            if (!preg_match('/^M-\d{4}-\d{2}$/', $filters['match'])) {
                throw new HttpInvalidInputException($request, "Match must be in M-YYYY-MM format.");
            }
            $filter_values['match_id'] = $filters['match'];
        }

        // Step 6: Validate pagination parameters
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filter_values);

        // Step 7: Set pagination options in the model
        $this->player_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Step 8: Fetch goals from the database for the given player ID and filters
        $goals = $this->player_model->getGoalsByPlayerId($player_id, $filter_values);

        // Step 9: Handle case where no goals are found
        if ($goals === false) {
            throw new ExceptionHttpNotFoundException($request, "No goals found for player ID: $player_id or player does not exist.");
        }

        // Step 10: Return the goals data as a JSON response with HTTP 200 status
        return $this->renderJson($response, $goals, 200);
    }

    /**
     * Handle GET /players/{player_id}/appearances - Retrieve appearances for a specific player.
     *
     * @param Request $request The request object containing the URI parameters and query filters.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the player ID.
     *
     * @return Response The response object with appearances data in JSON format.
     *
     * @throws HttpInvalidInputException If the player ID format is invalid.
     */
    public function handleGetPlayerAppearances(Request $request, Response $response, array $uri_args): Response
    {
        // Step 1: Extract player ID from the URL arguments
        $player_id = $uri_args["player_id"] ?? null;

        // Step 2: Validate player_id format (must be "P-##### or P-######")
        $regex_player_id = '/^P-\d{5,6}$/';
        if (!$player_id || !preg_match($regex_player_id, $player_id)) {
            throw new HttpInvalidInputException($request, "The provided player ID is invalid!");
        }

        // Step 3: Extract query parameters (filters)
        $filters = $request->getQueryParams();

        // Step 4: Validate pagination parameters
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        // Step 5: Set pagination options in the model
        $this->player_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Step 6: Fetch appearances from the database for the given player ID and filters
        $appearances = $this->player_model->getAppearancesByPlayerId($player_id, $filters);

        // Step 7: Handle case where no appearances are found
        if ($appearances === false) {
            throw new ExceptionHttpNotFoundException($request, "No appearances found for player ID: $player_id or player does not exist.");
        }

        // Step 8: Return the appearances data as a JSON response with HTTP 200 status
        return $this->renderJson($response, $appearances, 200);
    }
}
