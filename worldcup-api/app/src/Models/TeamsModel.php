<?php

namespace App\Models;
use App\Core\PDOService;

/**
 * Class TeamsModel
 *
 * Model for interacting with the teams database table. Provides methods to retrieve team information,
 * team appearances, and apply filters for more specific queries.
 *
 * @package App\Models
 */
class TeamsModel extends BaseModel
{
    /**
     * TeamsModel constructor.
     *
     * @param PDOService $pdo The PDO service instance for database interactions.
     */
    public function __construct(PDOService $pdo)
    {
        parent::__construct($pdo);
    }


    /**
     * Retrieve a list of teams with optional filters for sorting and pagination.
     *
     * @param array $filters An array of filters for the query (e.g., 'region').
     *
     * @return array An array of teams matching the filter criteria.
     *
     * @throws \PDOException If there is a database error.
     */
    public function getTeams(array $filters): array
    {
        // Step 1: Initialize an empty array to hold filter values for the SQL query.
        $filters_values = [];
        // Step 2: Start building the SQL query. 'WHERE 1' is a placeholder to simplify the construction of conditional clauses.
        $sql = "SELECT * FROM teams WHERE 1";

        // Step 3: Apply the region filter if it is provided in the input.
        if(isset($filters['region'])){
            $sql .= " AND region_name LIKE CONCAT('%', :region, '%') ";
            $filters_values['region'] = $filters['region'];
        }

        // Step 4: Call the paginate function to execute the query with the applied filters.
        $teams = $this->paginate($sql, $filters_values);

        // Step 5: Return the results from the pagination function.
        return $teams;
    }

    /**
     * Retrieve a specific team by its ID.
     *
     * @param string $team_id The unique ID of the team.
     *
     * @return mixed The team details if found, otherwise null.
     *
     * @throws \PDOException If there is a database error.
     */
    public function getTeamsById(String $team_id): mixed
    {
        // Initialize the query
        $sql = "SELECT * FROM teams WHERE team_id = :team_id ";

        // Execute the query
        return $this->fetchSingle($sql, ["team_id" => $team_id]);
    }

    /**
     * Retrieve the appearances of a team by its ID with optional filters.
     *
     * @param string $team_id The unique ID of the team.
     * @param array $filters Optional filters for the query (e.g., 'match_result').
     *
     * @return array|bool An array of team appearances, or false if no matches are found.
     *
     * @throws \PDOException If there is a database error.
     */
    public function getAppearancesByTeamId(string $team_id, array $filters): array|bool
    {
        // Step 1: Initialize the base SQL query to fetch appearances based on the provided team_id.
        $sql = "SELECT * FROM team_appearances WHERE team_id = :team_id";
        // Step 2: Initialize the query parameters array with the team_id.
        $params = ['team_id' => $team_id];

        // Step 3: Apply the match_result filter if provided in the $filters array.
        if (!empty($filters['match_result'])) {
            $sql .= " AND match_result = :match_result";
            $params['match_result'] = $filters['match_result'];
        }

        // Step 4: Execute the query with pagination, passing the SQL query and parameters.
        return $this->paginate($sql, $params);
    }
}
