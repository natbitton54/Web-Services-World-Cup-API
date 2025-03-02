<?php

namespace App\Models;

use App\Core\PDOService;

/**
 * Class MatchesModel
 *
 * Model for interacting with the matches database table and related player data.
 * Provides methods to retrieve players who participated in a specific match, optionally filtered by position.
 *
 * @package App\Models
 */
class MatchesModel extends BaseModel
{
    /**
     * MatchesModel constructor.
     *
     * @param PDOService $pdo The PDO service instance for database interactions.
     */
    public function __construct(PDOService $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Retrieve the list of players who played in a specific match, with optional position filtering.
     *
     * @param string $matchId The ID of the match to retrieve players for.
     * @param array $filters An array of optional filters (e.g., 'position' for player position).
     *
     * @return array|null A list of players who participated in the match, or null if no players are found.
     *
     * @throws \PDOException If there is a database error.
     */
    public function getPlayersMatchPlayedById(string $matchId, array $filters): ?array
    {
        $sql = "
            SELECT DISTINCT p.* FROM players p
            JOIN player_appearances pa ON p.player_id = pa.player_id
            JOIN matches m ON pa.match_id = m.match_id
            WHERE m.match_id = :match_id
        ";
        $params = ['match_id' => $matchId];

        if (isset($filters['position'])) {
            $position = strtolower(trim($filters['position']));
            $positionMap = [
                'goalkeeper' => 'goal_keeper',
                'defender' => 'defender',
                'midfielder' => 'midfielder',
                'forward' => 'forward'
            ];

            $sql .= " AND p.{$positionMap[$position]} = 1";
        }

        $results = $this->paginate($sql, $params);

        return $results;
    }
}
