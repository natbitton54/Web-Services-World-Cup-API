<?php

namespace App\Controllers;

use App\Core\AppSettings;
use App\Exceptions\HttpInvalidInputException;
use App\Exceptions\HttpNotFoundException;
use Slim\Exception\HttpNotFoundException as ExceptionHttpNotFoundException;
use App\Models\PlayersModel;
use App\Models\TeamsModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


/**
 * Class TeamsController
 *
 * Handles API requests related to teams such as retrieving a list of teams,
 * fetching details of a specific team, and retrieving team appearances.
 *
 * @package App\Controllers
 */
class TeamsController extends BaseController
{
    /**
     * TeamsController constructor.
     *
     * @param TeamsModel $team_model The TeamsModel instance for interacting with the team data.
     */
    public function __construct(private TeamsModel $team_model) {}

    /**
     * Handle GET /teams - Retrieve a list of teams based on filters.
     *
     * @param Request $request The request object containing query parameters.
     * @param Response $response The response object to return.
     *
     * @return Response The response object with the list of teams in JSON format.
     *
     * @throws HttpNotFoundException If no teams are found for the given filters.
     */
    public function handleGetTeams(Request $request, Response $response): Response
    {
        //? step 1: extract the list of filters
        $filters = $request->getQueryParams();
        // dd($filters);

        // Validate pagination
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        //  Set pagination options in the model
        $this->team_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // ?step 2: fetch the list of teams from the db
        $teams = $this->team_model->getTeams($filters);

        // if no team found throw a 404 not found error
        if (empty($teams)) {
            throw new HttpNotFoundException($request, "No teams found for that criteria.");
        }

        //! step 3: prepare the http response message
        //! step 3.a: encode the response payload in json
        return $this->renderJson($response, $teams);
    }

    /**
     * Handle GET /teams/{team_id} - Retrieve information about a specific team.
     *
     * @param Request $request The request object.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the team ID.
     *
     * @return Response The response object with the team information in JSON format.
     *
     * @throws HttpInvalidInputException If the team ID format is invalid.
     * @throws ExceptionHttpNotFoundException If no team is found with the given ID.
     */
    public function handleGetTeamsById(Request $request, Response $response, array $uri_args): Response
    {
        // Retrieve team ID from the URI arguments, or set it to null if not provided
        $team_id = $uri_args['team_id'];

        // Validate team id format
        $regex_team_id = "/^T-\d{2}$/";
        if (preg_match($regex_team_id, $team_id) === 0) {
            throw new HttpInvalidInputException(
                $request,
                "The provided Team ID is invalid. Expected format: T-00 or T-99."
            );
        }

        // Fetch team info
        $team_info = $this->team_model->getTeamsById($team_id);

        // If no data is found, throw a NotFoundException
        if ($team_info === false) {
            throw new ExceptionHttpNotFoundException( //  404
                $request,
                "No Team found with ID: $team_id"
            );
        }

        // Return the team data as a JSON response with a 200 OK status
        return $this->renderJson($response, $team_info, 200);
    }

    /**
     * Handle GET /teams/{team_id}/appearances - Retrieve appearances for a specific team.
     *
     * @param Request $request The request object containing query filters.
     * @param Response $response The response object to return.
     * @param array $uri_args The URI arguments containing the team ID.
     *
     * @return Response The response object with the team appearances in JSON format.
     *
     * @throws HttpInvalidInputException If the team ID format is invalid or the match result filter is invalid.
     * @throws HttpNotFoundException If no appearances are found for the given team ID or filters.
     */
    public function handleGetTeamAppearances(Request $request, Response $response, array $uri_args): Response
    {
        // Retrieve team ID from the URI arguments, or set it to null if not provided
        $team_id = $uri_args["team_id"] ?? null;

        // Validate team_id format (T-00 to T-99, e.g., T-01, T-99)
        $regex_team_id = '/^T-\d{2}$/';
        if (!$team_id || !preg_match($regex_team_id, $team_id)) {
            throw new HttpInvalidInputException($request, "The provided team ID is invalid! Expected format: T-XX (e.g., T-00, T-01, T-99).");
        }

        // Extract sorting parameters from filters
        $filters = $request->getQueryParams();
        $filter_values = [];

        // Validate and apply match_result filter
        if(isset($filters['match_result'])){
            $validMatchResults = ['win', 'lose', 'draw'];
            $matchResults = strtolower(trim($filters['match_result']));
            if(!in_array($matchResults, $validMatchResults)){
                throw new HttpInvalidInputException($request, "Invalid match result filter. Expected values: win, lose, draw.");
            }
            $filter_values['match_result'] = $filters['match_result'];
        }

        // validate pagination
        [$pageCount, $page_size] = $this->validatePaginationParams($request, $filters);

        // Set pagination options in the model
        $this->team_model->setPaginationOptions(
            $pageCount,
            $page_size
        );

        // Fetch appearances from the db
        $appearances = $this->team_model->getAppearancesByTeamId($team_id, $filter_values);

        // If no data is found, throw a NotFoundException
        if ($appearances === false || empty($appearances)) {
            throw new HttpNotFoundException($request, "No appearances found for team ID or for a specific match result, $team_id or team does not exist.");
        }

       // Return the data as a JSON response with a 200 OK status
        return $this->renderJson($response, $appearances, 200);
    }
}
