<?php

namespace App\Models;

use App\Core\PDOService;

/**
 * Class PlayersModel
 *
 * Model for interacting with the players database table. Provides methods to retrieve player information,
 * including goals and appearances, with optional filters for sorting and other criteria.
 *
 * @package App\Models
 */
class PlayersModel extends BaseModel
{
    /**
     * PlayersModel constructor.
     *
     * @param PDOService $pdo The PDO service instance for database interactions.
     */
    public function __construct(PDOService $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Retrieve a list of players with optional filters, sorting, and pagination.
     *
     * @param array $filters An array of filters for the query (e.g., 'first_name', 'last_name', 'dob', 'position', etc.).
     * @param string $sort_by The field by which to sort the results (default: 'family_name').
     * @param string $sort_order The sort order (default: 'ASC').
     *
     * @return array An array of players matching the filter criteria.
     *
     * @throws \PDOException If there is a database error.
     */
    public function getPlayers(array $filters, string $sort_by = 'family_name', string $sort_order = 'ASC'): array
    {
        $filters_values = [];
        $allowed_sort_fields = [
            'first_name' => 'given_name',
            'last_name' => 'family_name',
            'dob' => 'birth_date',
            'player_id' => 'player_id'
        ];

        // Validate and map sort field to database column
        $sort_column = $allowed_sort_fields[$sort_by] ?? 'family_name';
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM players WHERE 1";
        // step 1: Add a conditions for the received filter(s)
        if (isset($filters['first_name'])) {
            $sql .= " AND given_name LIKE CONCAT(:first_name, '%') ";
            $filters_values['first_name'] = $filters['first_name'];
        }

        if (isset($filters['last_name'])) {
            $sql .= " AND family_name LIKE CONCAT(:last_name, '%') ";
            $filters_values['last_name'] = $filters['last_name'];
        }

        if (isset($filters['dob'])) { // dob: date of birth
            $sql .= " AND birth_date > :dob ";
            $filters_values['dob'] = $filters['dob'];
        }

        if (isset($filters['position']) && in_array($filters['position'], ['goalkeeper', 'forward', 'defender', 'midfielder'])) {
            switch ($filters['position']) {
                case 'goalkeeper':
                    $sql .= " AND goal_keeper = 1";
                    break;
                case 'forward':
                    $sql .= " AND forward = 1";
                case 'defender':
                    $sql .= " AND defender = 1";
                case 'midfielder':
                    $sql .= " AND midfielder = 1";
            }
        }

        if (isset($filters['gender'])) {
            if ($filters['gender'] == 'female') {
                $sql .= " AND female = 1";
            } elseif ($filters['gender'] == 'male') {
                $sql .= " AND female = 0";
            }
        }

        $sql .= " ORDER BY " . $sort_column . " " . $sort_order;
        $players = $this->paginate($sql, $filters_values);
        return $players;
    }

    /**
     * Retrieve a player by their unique ID.
     *
     * @param string $player_id The unique ID of the player to retrieve.
     *
     * @return mixed The player information, or null if not found.
     */
    public function getPlayersById(String $player_id): mixed
    {
        $sql = "SELECT * FROM players WHERE player_id = :player_id ";

        return $this->fetchSingle($sql, ["player_id" => $player_id]);
    }


    /**
     * Retrieve the goals scored by a player with optional filters.
     *
     * @param string $player_id The player ID to retrieve goals for.
     * @param array $filters Optional filters (e.g., 'tournament', 'match').
     *
     * @return array|bool A list of goals scored by the player, or false if no goals are found.
     */
    public function getGoalsByPlayerId(string $player_id, array $filters): array|bool
    {
        $sql = "SELECT * FROM goals WHERE player_id = :player_id";
        $params = ['player_id' => $player_id];

        if (!empty($filters['tournament'])) {
            $sql .= " AND tournament_id = :tournament";
            $params['tournament'] = $filters['tournament'];
        }
        if (!empty($filters['match'])) {
            $sql .= " AND match_id = :match";
            $params['match'] = $filters['match'];
        }

        return $this->paginate($sql, $params) ?: false;
    }

    /**
     * Retrieve the appearances made by a player with optional filters.
     *
     * @param string $player_id The player ID to retrieve appearances for.
     * @param array $filters Optional filters for appearances.
     *
     * @return array|bool A list of appearances made by the player, or false if no appearances are found.
     */
    public function getAppearancesByPlayerId(string $player_id, array $filters): array|bool
    {
        $sql = "SELECT * FROM player_appearances WHERE player_id = :player_id";
        $params = ['player_id' => $player_id];

        return $this->paginate($sql, $params);
    }
}
