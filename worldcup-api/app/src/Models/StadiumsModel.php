<?php

namespace App\Models;

use App\Core\PDOService;

/**
 * Class StadiumsModel
 *
 * Model for interacting with the stadiums database table. Provides methods to retrieve stadium information
 * and associated matches, with optional filters for sorting and other criteria.
 *
 * @package App\Models
 */
class StadiumsModel extends BaseModel
{
    /**
     * StadiumsModel constructor.
     *
     * @param PDOService $pdo The PDO service instance for database interactions.
     */
    public function __construct(PDOService $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Retrieve a list of stadiums with optional filters, sorting, and pagination.
     *
     * @param array $filters An array of filters for the query (e.g., 'country', 'city', 'capacity').
     * @param string $sort_by The field by which to sort the results (default: 'stadium_name').
     * @param string $sort_order The sort order (default: 'ASC').
     *
     * @return array An array of stadiums matching the filter criteria.
     *
     * @throws \PDOException If there is a database error.
     */
    public function getStadiums(array $filters, string $sort_by = 'name', string $sort_order = 'ASC'): array
    {
        // Step 1: Initialize filter values and allowed sort fields
        $filters_values = [];
        $allowed_sort_fields = [
            'name' => 'stadium_name',
            'country_name' => 'country',
            'stadium_capacity' => 'capacity',
            'city_name' => 'city',
            'stadium_id' => 'stadium_id'
        ];

        // Step 2: Validate and map sort field to database column
        $sort_column = $allowed_sort_fields[$sort_by] ?? 'stadium_name';
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        // Step 3: Start building the SQL query
        $sql = "SELECT DISTINCT s.* FROM stadiums s JOIN matches m ON s.stadium_id = m.stadium_id WHERE 1=1";

        // Step 4: Apply filters to the query
        if (isset($filters['country']) && !empty(trim($filters['country']))) {
            $sql .= " AND s.country_name = :country";
            $filters_values['country'] = trim($filters['country']);
        }

        if (isset($filters['city']) && !empty(trim($filters['city']))) {
            $sql .= " AND s.city_name = :city";
            $filters_values['city'] = trim($filters['city']);
        }

        if (isset($filters['capacity'])) {
            $sql .= " AND s.stadium_capacity >= :capacity";
            $filters_values['capacity'] = $filters['capacity'];
        }

        // Step 5: Add sorting to the SQL query
        $sql .= " ORDER BY " . $sort_column . " " . $sort_order;

        // Step 6: Execute the query with pagination and return the result
        $result = $this->paginate($sql, $filters_values);
        return $result;
    }

    /**
     * Retrieve the matches held in a stadium with optional filters.
     *
     * @param string $stadiumId The unique ID of the stadium to retrieve matches for.
     * @param array $filters Optional filters for the query (e.g., 'tournament_name', 'stage').
     *
     * @return array An array of matches held in the specified stadium.
     *
     * @throws \PDOException If there is a database error.
     */
    public function getMatchesByStadiumId(string $stadiumId, array $filters = []): array
    {
        // Step 1: Start building the SQL query with a JOIN between matches, stadiums, and tournaments
        $sql = "
            SELECT t.key_id, t.tournament_id, t.tournament_name, m.*
            FROM matches m
            JOIN stadiums s ON m.stadium_id = s.stadium_id
            LEFT JOIN tournaments t ON m.tournament_id = t.tournament_id
            WHERE s.stadium_id = :stadium_id
    ";

        // Step 2: Initialize query parameters with the provided stadium_id
        $params = ['stadium_id' => $stadiumId];

        // Step 3: Apply filters for tournament_name if provided
        if (isset($filters['tournament_name']) && !empty($filters['tournament_name'])) {
            $sql .= " AND t.tournament_name LIKE :tournament_name";
            $params['tournament_name'] = "%" . $filters['tournament_name'] . "%";
        }

        // Step 4: Apply filters for stage if provided
        if (isset($filters['stage']) && !empty($filters['stage'])) {
            $sql .= " AND m.stage_name = :stage_name";
            $params['stage_name'] = $filters['stage'];
        }

        // Step 5: Execute the query with pagination and return the results
        return $this->paginate($sql, $params);
    }
}
