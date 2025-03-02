<?php

declare(strict_types=1);

use App\Controllers\AboutController;
use App\Controllers\MatchesController;
use App\Controllers\PlayersController;
use App\Controllers\RootController;
use App\Controllers\StadiumsController;
use App\Controllers\TeamsController;
use App\Controllers\TournamentsController;
use App\Helpers\DateTimeHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

return static function (Slim\App $app): void {

    // Routes without authentication check: /login, /token

    // Routes with authentication
    //* ROUTE: GET /
    $app->get('/', [RootController::class, 'handleAboutWebService']);

    //* ROUTE: GET /players
    $app->get("/players", [PlayersController::class, 'handleGetPlayers']);

    //* ROUTE: GET /players/{player_id}
    $app->get("/players/{player_id}", [PlayersController::class, 'handleGetPlayersById']);

    //* ROUTE: GET /players/{player_id}/goals
    $app->get('/players/{player_id}/goals', [PlayersController::class, 'handleGetPlayerGoals']);

    //* ROUTE: GET /players/{player_id}/appearances
    $app->get('/players/{player_id}/appearances', [PlayersController::class, 'handleGetPlayerAppearances']);

    //* ROUTE: GET /teams
    $app->get("/teams", [TeamsController::class, 'handleGetTeams']);

    //* ROUTE: GET /teams/{team_id}
    $app->get("/teams/{team_id}", [TeamsController::class, 'handleGetTeamsById']);

    //* ROUTE: GET /teams{team_id}/appearances
    $app->get("/teams/{team_id}/appearances", [TeamsController::class, 'handleGetTeamAppearances']);

    //* ROUTE: GET /tournament
    $app->get("/tournaments", [TournamentsController::class, 'handleGetTournaments']);

    //* ROUTE: GET /tournament/{tournament_id}
    $app->get("/tournaments/{tournament_id}", [TournamentsController::class, 'handleGetTournamentById']);

    //* ROUTE: GET /tournament{tournament_id}/matches
    $app->get("/tournaments/{tournament_id}/matches", [TournamentsController::class, 'handleGetTournamentMatchesById']);

    //* ROUTE: GET /matches{match_id}/players
    $app->get("/matches/{match_id}/players", [MatchesController::class, 'handlePlayerMatchesPlayedById']);

    //* ROUTE: GET /stadiums
    $app->get("/stadiums", [StadiumsController::class, 'handleGetStadiums']);

    //* ROUTE: GET /stadiums
    $app->get("/stadiums/{stadium_id}/matches", [StadiumsController::class, 'handleGetMatchesByStadiumId']);

    //* ROUTE: GET /ping
    $app->get('/ping', function (Request $request, Response $response, $args) {

        $payload = [
            "greetings" => "Reporting! Hello there!",
            "now" => DateTimeHelper::now(DateTimeHelper::Y_M_D_H_M),
        ];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
        return $response;
    });
    // Example route to test error handling
    $app->get('/error', function (Request $request, Response $response, $args) {
        throw new HttpNotFoundException($request, "Something went wrong");
    });
    // $app->get('/error', function (Request $request, Response $response, $args) {
    //     throw new \Slim\Exception\HttpNotFoundException($request, "Something went wrong");
    // });
};
