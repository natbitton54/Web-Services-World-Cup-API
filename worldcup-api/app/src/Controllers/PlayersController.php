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
        //? step 1: extract the list of filters
        $filters = $request->getQueryParams();

        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        $sort_by = $filters['sort_by'] ?? "last_name";
        $sort_order = strtoupper($filters["sort_order"] ?? 'ASC');

        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'ASC';
        }


        $this->player_model->setPaginationOptions(
            $pageCount,
            $page_size
        );
        // dd($filters);

        // ?step 1: fetch the list of players from the db
        $players = $this->player_model->getPlayers($filters, $sort_by, $sort_order);

        if (empty($players)) {
            throw new HttpNotFoundException($request, "No players found for that criteria.");
        }
        // dd($players);
        //! step 2: prepare the http response message
        //! step 2.a: encode the response payload in json
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
        $player_id = $uri_args['player_id'];

        // Validate player_id format
        $regex_player_id = '/^P-\d{5,6}$/';
        if (preg_match($regex_player_id, $player_id) === 0) {
            throw new HttpInvalidInputException(
                $request,
                "The provided player ID is invalid. Expected format: P-12345 or P-123456."
            );
        }

        // Fetch player info
        $player_info = $this->player_model->getPlayersById($player_id);


        if ($player_info === false) {
            throw new ExceptionHttpNotFoundException( //  404
                $request,
                "No player found with ID: $player_id"
            );
        }

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
        $player_id = $uri_args["player_id"] ?? null;

        // Validate player_id format (P-##### or P-######)
        $regex_player_id = '/^P-\d{5,6}$/';
        if (!$player_id || !preg_match($regex_player_id, $player_id)) {
            throw new HttpInvalidInputException($request, "The provided player ID is invalid!");
        }

        $filters = $request->getQueryParams();
        $filter_values = [];

        // Validate and map tournament (user-facing) to tournament_id (database)
        if (isset($filters['tournament'])) {
            if (!preg_match('/^WC-\d{4}$/', $filters['tournament'])) {
                throw new ExceptionHttpNotFoundException($request, "Tournament must be in WC-YYYY format.");
            }
            $filter_values['tournament_id'] = $filters['tournament'];
        }

        // Validate and map match (user-facing) to match_id (database)
        if (isset($filters['match'])) {
            if (!preg_match('/^M-\d{4}-\d{2}$/', $filters['match'])) {
                throw new HttpInvalidInputException($request, "Match must be in M-YYYY-MM format.");
            }
            $filter_values['match_id'] = $filters['match'];
        }

        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filter_values);

        $this->player_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Fetch goals from the db
        $goals = $this->player_model->getGoalsByPlayerId($player_id, $filter_values);

        if ($goals === false) {
            throw new ExceptionHttpNotFoundException($request, "No goals found for player ID: $player_id or player does not exist.");
        }

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
        $player_id = $uri_args["player_id"] ?? null;

        // Validate player_id format (P-##### or P-######)
        $regex_player_id = '/^P-\d{5,6}$/';
        if (!$player_id || !preg_match($regex_player_id, $player_id)) {
            throw new HttpInvalidInputException($request, "The provided player ID is invalid!");
        }

        $filters = $request->getQueryParams();

        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        $this->player_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Fetch appearances from the db
        $appearances = $this->player_model->getAppearancesByPlayerId($player_id, $filters);

        if ($appearances === false) {
            throw new ExceptionHttpNotFoundException($request, "No appearances found for player ID: $player_id or player does not exist.");
        }

        return $this->renderJson($response, $appearances, 200);
    }
}
