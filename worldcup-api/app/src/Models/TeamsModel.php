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
        $filters_values = [];
        $sql = "SELECT * FROM teams WHERE 1";

        if(isset($filters['region'])){
            $sql .= " AND region_name LIKE CONCAT('%', :region, '%') ";
            $filters_values['region'] = $filters['region'];
        }

        $teams = $this->paginate($sql, $filters_values);
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
        $sql = "SELECT * FROM teams WHERE team_id = :team_id ";
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
        $sql = "SELECT * FROM team_appearances WHERE team_id = :team_id";
        $params = ['team_id' => $team_id];

        if (!empty($filters['match_result'])) {
            $sql .= " AND match_result = :match_result";
            $params['match_result'] = $filters['match_result'];
        }

        return $this->paginate($sql, $params);
    }
}
