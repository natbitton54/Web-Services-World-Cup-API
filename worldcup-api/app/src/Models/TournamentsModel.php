<?php

namespace App\Models;

use App\Core\PDOService;
use App\Exceptions\HttpInvalidInputException;
use App\Exceptions\HttpNotFoundException;
use DateTime;
use PDO;
use PDOException;

/**
 * Class TournamentsModel
 *
 * Model for interacting with the tournaments database table. Provides methods to retrieve tournament
 * information, filter tournaments based on criteria, and fetch related matches.
 *
 * @package App\Models
 */
class TournamentsModel extends BaseModel
{
    /**
     * TournamentsModel constructor.
     *
     * @param PDOService $pdo The PDO service instance for database interactions.
     */
    public function __construct(PDOService $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Retrieve a list of tournaments based on filters such as start date, winner, host country, and tournament type.
     *
     * @param array $filters An array of filters for the query, such as 'start_date_min', 'start_date_max', 'winner', etc.
     *
     * @return array An array of tournaments matching the filter criteria.
     *
     * @throws PDOException If the provided date format is invalid or there is a database error.
     */
    public function getTournaments(array $filters): array
    {
        // Step 1: Initialize the query and the filters array to store conditions.
        $filters_values = [];
        $sql = "SELECT * FROM tournaments WHERE 1"; // Base query to fetch all tournaments

        // Step 2: Handle 'start_date_min' filter.
        if (isset($filters['start_date_min'])) {
            // Parse the date using DateTime.
            $date = DateTime::createFromFormat('Y-m-d', $filters['start_date_min']);
            // Check if the date is valid and matches the 'YYYY-MM-DD' format.
            if (!$date || $date->format('Y-m-d') !== $filters['start_date_min']) {
                throw new PDOException("Start date (min) must be in YYYY-MM-DD format.", 400);
            }
            // Add condition to query for tournaments that start on or after the 'start_date_min'.
            $sql .= " AND start_date >= :start_date_min ";
            $filters_values['start_date_min'] = $filters['start_date_min'];
        }

        // Step 3: Handle 'start_date_max' filter.
        if (isset($filters['start_date_max'])) {
            // Parse the date using DateTime.
            $date = DateTime::createFromFormat('Y-m-d', $filters['start_date_max']);
            // Check if the date is valid and matches the 'YYYY-MM-DD' format.
            if (!$date || $date->format('Y-m-d') !== $filters['start_date_max']) {
                throw new PDOException("Start date (max) must be in YYYY-MM-DD format.", 400);
            }
            // Add condition to query for tournaments that start on or before the 'start_date_max'.
            $sql .= " AND start_date <= :start_date_max";
            $filters_values['start_date_max'] = $filters['start_date_max'];
        }

        // Step 4: Handle filters
        if (isset($filters['winner']) && !empty(trim($filters['winner']))) {
            $sql .= " AND winner = :winner ";
            $filters_values['winner'] = trim($filters['winner']);
        }

        if (isset($filters['host_country']) && !empty(trim($filters['host_country']))) {
            $sql .= " AND host_country = :host_country ";
            $filters_values['host_country'] = trim($filters['host_country']);
        }

        if (isset($filters['tournament_type'])) {
            $sql .= " AND tournament_name LIKE :tournament_name ";
            $filters_values['tournament_name'] = "%" . $filters['tournament_type'] . "%";
        }

        // Step 7: Execute the query with pagination and return the results 'tournament'.
        $tournament = $this->paginate($sql, $filters_values);
        return $tournament;
    }

    /**
     * Retrieve a specific tournament by its ID.
     *
     * @param string $tournament_id The unique ID of the tournament.
     *
     * @return mixed The tournament details if found, otherwise null.
     *
     * @throws PDOException If there is a database error.
     */
    public function getTournamentById(String $tournament_id): mixed
    {
        // Initialize the query
        $sql = "SELECT * FROM tournaments WHERE tournament_id = :tournament_id ";

        // Execute the query
        return $this->fetchSingle($sql, ["tournament_id" => $tournament_id]);
    }

    /**
     * Retrieve the matches of a specific tournament by its ID with optional filters.
     *
     * @param string $tournament_id The unique ID of the tournament.
     * @param array $filters Optional filters for the query (e.g., 'stage_name').
     *
     * @return array|bool An array of tournament matches, or false if no matches are found.
     *
     * @throws PDOException If there is a database error.
     */
    public function getTournamentMatchesById(string $tournament_id, array $filters): array|bool
    {
        // Step 1: Start by defining the SQL query to get matches for a given tournament.
        $sql = "SELECT * FROM matches WHERE tournament_id = :tournament_id";
        $params = ['tournament_id' => $tournament_id]; // Bind the tournament_id parameter to the SQL query

        // Step 2: Check if the 'stage_name' filter is provided in the $filters array.
        if (!empty($filters['stage_name'])) {
            // Step 3: If 'stage_name' filter is provided, add the condition to the SQL query.
            $sql .= " AND stage_name = :stage_name";
            $params['stage_name'] = $filters['stage_name'];
        }

        // Step 4: Execute the query and return the result.
        $result = $this->paginate($sql, $params);
        return $result ?: false;
    }
}
