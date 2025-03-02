<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RootController extends BaseController
{
    /**
     * Handle GET / - Provide information about all exposed resources
     *
     * @param Request  $request  The HTTP request object
     * @param Response $response The HTTP response object
     *
     * @return Response The JSON-encoded response
     */
    public function handleAboutWebService(Request $request, Response $response): Response
    {
        $resources = [
            // Players Resources
            [
                'id'              => 1,
                'uri'             => '/players',
                'description'     => 'Gets a list of zero or more players resources that match the request’s filtering criteria',
                'sortingSupported' => [
                    'sort_by'        => ['first_name', 'last_name', 'dob', 'player_id'],
                    'default'        => 'last_name',
                    'default_order'  => 'ASC',
                ],
                'filtersSupported' => [
                    'first_name'     => 'String (partial match)',
                    'last_name'      => 'String (partial match)',
                    'dob'            => 'Date (greater than the specified date)',
                    'position'       => 'String (goalkeeper, forward, defender, OR midfielder)',
                    'gender'         => 'String (male/female)',
                ],
            ],
            [
                'id'              => 2,
                'uri'             => '/players/{player_id}',
                'description'     => 'Gets the details of the specified player',
                'filtersSupported' => 'N/A',
            ],
            [
                'id'              => 3,
                'uri'             => '/players/{player_id}/goals',
                'description'     => 'Gets a list of zero or more goal resources scored by the specified player that match the request’s filtering criteria',
                'filtersSupported' => [
                    'tournament'     => 'String (WC-YYYY format)',
                    'match'          => 'String (M-YYYY-MM format)',
                ],
            ],
            [
                'id'              => 4,
                'uri'             => '/players/{player_id}/appearances',
                'description'     => 'Gets a list of the specified player’s appearances',
                'filtersSupported' => 'N/A',
            ],

            // Teams Resources
            [
                'id'              => 5,
                'uri'             => '/teams',
                'description'     => 'Gets a list of zero or more teams matching the specified filter',
                'filtersSupported' => [
                    'region'         => 'String (partial match)',
                ],
            ],
            [
                'id'              => 6,
                'uri'             => '/teams/{team_id}/appearances',
                'description'     => 'Gets a list of zero or more appearances of the specified team',
                'filtersSupported' => [
                    'match_result'   => 'String (e.g., win, loss, draw)',
                ],
            ],

            // Tournaments Resources
            [
                'id'              => 7,
                'uri'             => '/tournaments',
                'description'     => 'Gets a list of zero or more World Cup tournaments resources',
                'filtersSupported' => [
                    'start_date'     => 'Date (between two dates)',
                    'host_country'   => 'String (partial match)',
                    'tournament_type' => 'String (women/men)',
                ],
            ],
            [
                'id'              => 8,
                'uri'             => '/tournaments/{tournament_id}/matches',
                'description'     => 'Gets the list of matches that took place in the specified tournament and match the request filter',
                'filtersSupported' => [
                    'stage'          => 'String (e.g., group, quarter-final)',
                ],
            ],

            // Matches Resources
            [
                'id'              => 9,
                'uri'             => '/matches/{match_id}/players',
                'description'     => 'Gets the list of players who played in the specified match',
                'filtersSupported' => [
                    'position'       => 'String (goalkeeper, forward, defender, OR midfielder)',
                ],
            ],

            // Stadiums Resources
            [
                'id'              => 10,
                'uri'             => '/stadiums',
                'description'     => 'Gets the list of stadiums where World Cup matches took place',
                'sortingSupported' => [
                    'sort_by'        => ['name', 'capacity', 'country', 'city', 'stadium_id'],
                    'default'        => 'name',
                    'default_order'  => 'ASC',
                ],
                'filtersSupported' => [
                    'country'        => 'String (partial match)',
                    'city'           => 'String (partial match)',
                    'capacity'       => 'Number (greater than the specified number)',
                ],
            ],
            [
                'id'              => 11,
                'uri'             => '/stadiums/{stadium_id}/matches',
                'description'     => 'Gets list of matches that took place the specified stadium',
                'filtersSupported' => [
                    'tournament'     => 'String (partial match)',
                    'stage_name'     => 'String (e.g., group, final)',
                ],
            ],
        ];

        $payload = [
            'version'  => '1.0',
            'api'      => 'World Cup API',
            'about'    => 'Welcome! This is a Web service that provides resources on FIFA World Cup',
            'author'   => 'Nat Bitton',
            'resources' => $resources,
        ];

        return $this->renderJson($response, $payload);
    }
}
