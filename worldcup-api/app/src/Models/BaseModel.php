<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\PDOService;
use App\Helpers\PaginationHelper;
use PDO;
use Exception;

/**
 * A wrapper class for interacting with a MySQL DB using the PDO API.
 * This class can be extended for further customization.
 */
abstract class BaseModel
{

    /**
     * holds a handle to a database connection.
     */
    private ?PDO $db = null;

    /**
     * The index of the current page.
     * @var int
     */
    private $current_page = 1;

    /**
     * Holds the number of records to include per page..
     * @var int
     */
    private $records_per_page = 5;

    /**
     * Instantiates the PDO wrapper.
     *
     * @param PDOService $pdo A helper object that contains the
     *                        established DB connection.
     */
    public function __construct(PDOService $pdo)
    {
        $this->db = $pdo->getPDO();
    }

    /**
     * Executes a SQL query with the provided arguments.
     *
     * This method prepares and executes a SQL statement, binding parameters appropriately
     * whether they are provided as an associative or indexed array.
     *
     * @param string $sql The SQL query to be executed.
     * @param array $args An optional array of parameters to bind to the SQL query.
     *                     If empty, the query is executed directly without parameter binding.
     * @return PDOStatement The PDOStatement object representing the prepared statement.
     */
    private function run(string $sql, array $args = [])
    {
        if (empty($args)) {
            return $this->db->query($sql);
        }
        $stmt = $this->db->prepare($sql);
        //check if args is associative or sequential?
        $is_assoc = (array() === $args) ? false : array_keys($args) !== range(0, count($args) - 1);
        if ($is_assoc) {
            foreach ($args as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":$key", $value);
                }
            }
            $stmt->execute();
        } else {
            $stmt->execute($args);
        }
        return $stmt;
    }

    /**
     * Fetches all results from a SQL query as an array.
     *
     * This method executes a SQL query and returns the results in the specified fetch mode.
     *
     * @param string $sql The SQL query to be executed.
     * @param array $args An optional array of parameters to bind to the SQL query.
     * @param int $fetchMode The PDO fetch mode to use for the results. Defaults to PDO::FETCH_ASSOC.
     * @return array An array of results from the query, formatted according to the specified fetch mode.
     */
    protected function fetchAll(string $sql, array $args = [], $fetchMode = PDO::FETCH_ASSOC): array
    {
        return (array) $this->run($sql, $args)->fetchAll($fetchMode);
    }

    protected function paginate(string $sql, array $args = [], $fetchMode = PDO::FETCH_ASSOC): array
    {
        // Step 1: Determine the total number of records in the set
        // hint use the count method
        $total_records = $this->count($sql, $args);

        // Step 2: Initiate the pagination helper class, and pass to its constructor the required inputs as parameters
        $pagination_helper = new PaginationHelper(
            $this->current_page,
            $this->records_per_page,
            $total_records
        );

        $offset = $pagination_helper->getOffset();

        // Step 3: Query the database for the paginated results
        $sql .= " LIMIT $this->records_per_page OFFSET $offset ";

        // Step 4: Execute the query
        $data = $this->fetchAll($sql, $args, $fetchMode);

        // Step 5: Retrieve the pagination meta data from the pagination helper class
        $meta_data = $pagination_helper->getPaginationMetaData();

        // Step 6: Return the paginated data and meta data in the same array
        $meta_data['data'] = $data;
        return $meta_data;

    }

    /**
     * Fetches a single result from a SQL query.
     *
     * This method executes a SQL query and returns a single result in the specified fetch mode.
     *
     * @param string $sql The SQL query to be executed.
     * @param array $conditions An optional array of parameters to bind to the SQL query.
     *                          It should contain the filtering options.
     * @param int $fetchMode The PDO fetch mode to use for the result. Defaults to PDO::FETCH_ASSOC.
     * @return mixed The result of the query, formatted according to the specified fetch mode, or false if no result is found.
     */
    protected function fetchSingle(string $sql, array $conditions = [], $fetchMode = PDO::FETCH_ASSOC)
    {
        return $this->run($sql, $conditions)->fetch($fetchMode);
    }


    /**
     * Counts the number of rows affected by a SQL query.
     *
     * This method executes a SQL query and returns the count of rows that were affected.
     *
     * @param string $sql The SQL query to be executed, typically a SELECT query.
     * @param array $args An optional array of parameters to bind to the SQL query.
     * @return int The number of rows affected by the query.
     */
    protected function count(string $sql, array $args = []): int
    {
        return $this->run($sql, $args)->rowCount();
    }

    /**
     * Retrieves the ID of the last inserted record.
     *
     * This method returns the ID generated by the last INSERT operation
     * performed by the current database connection.
     *
     * @return string The ID of the last inserted record, or an empty string if no record was inserted.
     */
    protected function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    /**
     * Inserts a new record into the specified table with the provided data.
     *
     * @param string $table The name of the table to insert the record into.
     * @param array $data An associative array of column-value pairs to insert
     *              (e.g., ["username"=>"frostybee", "email" =>"frostybee@me.com"]).
     * @return mixed The ID of the last inserted record or other relevant value,
     *               depending on the implementation.
     */
    protected function insert(string $table, array $data): mixed
    {
        //add columns into comma separated string
        $columns = implode(',', array_keys($data));

        //get values
        $values = array_values($data);

        $placeholders = array_map(function ($val) {
            return '?';
        }, array_keys($data));

        //convert array into comma separated string
        $placeholders = implode(',', array_values($placeholders));

        $this->run("INSERT INTO $table ($columns) VALUES ($placeholders)", $values);

        return $this->lastInsertId();
    }

    /**
     * Updates record(s) in the specified table based on the provided data and conditions.
     *
     * @param string $table The name of the table to update.
     * @param array $data An associative array of table column-value pairs to update (e.g.,
     *              ["username"=>"frostybee", "email" =>"frostybee@me.com"]).
     * @param array $where_conditions An associative array of conditions for the update
     *              (e.g., ["user_id"=> 3]).
     * @return int The number of rows affected by the update operation.
     */
    protected function update(string $table, array $data, array $where_conditions): int
    {
        //merge data and where together
        $collection = array_merge($data, $where_conditions);

        //collect the values from collection
        $values = array_values($collection);

        //setup fields
        $field_details = null;
        foreach ($data as $key => $value) {
            $field_details .= "$key = ?,";
        }
        $field_details = rtrim($field_details, ',');

        //setup where
        $where_details = null;
        $i = 0;
        foreach ($where_conditions as $key => $value) {
            $where_details .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $i++;
        }

        $stmt = $this->run("UPDATE $table SET $field_details WHERE $where_details", $values);

        return $stmt->rowCount();
    }

    /**
     * Deletes record(s) from the specified table based on the given conditions.
     *
     * @param string $table The name of the table from which to delete records.
     * @param array $where_conditions An associative array of conditions for the deletion
     * in the form of ['table_column' => 'value'] (e.g., ['user_id' => 3]).
     * @param int $limit The maximum number of records to delete. Default is 1.
     *
     * @return int The number of rows affected by the delete operation.
     */
    protected function delete(string $table, array $where_conditions, int $limit = 1): int
    {
        //collect the values from collection
        $values = array_values($where_conditions);

        //setup where
        $where_details = null;
        $i = 0;
        foreach ($where_conditions as $key => $value) {
            $where_details .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $i++;
        }

        //if limit is a number use a limit on the query
        if (is_numeric($limit)) {
            $limit = "LIMIT $limit";
        }

        $stmt = $this->run("DELETE FROM $table WHERE $where_details $limit", $values);

        return $stmt->rowCount();
    }

    /**
     * Sets the pagination options for the current instance.
     *
     * @param int $current_page The current page number.
     * @param int $records_per_page The number of records to include per page.
     * @return void
     */
    public function setPaginationOptions(int $current_page, int $records_per_page): void
    {
        $this->current_page = $current_page;
        $this->records_per_page = $records_per_page;
    }
}
