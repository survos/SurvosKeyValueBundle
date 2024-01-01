<?php

namespace Survos\KeyValueBundle;

use \PDO;
/*
 *
 * https://gist.github.com/sbrl/c3bfbbbb3d1419332e9ece1bac8bb71c
 *
 * Represents a Sqlite key-value data store.
 * @license Apache 2.0
 */
class StorageBox {
    /**
     * The SQLite database connection.
     * @var \PDO
     */
    private \PDO $db;

    /**
     * Initialises a new store connection.
     * @param	string	$filename	The filename that the store is located in.
     */
    function __construct(private string $filename,
                         private string $tableName = 'store') {
        $firstrun = !file_exists($filename);
        $this->db = new \PDO("sqlite:" . realpath($filename)); // HACK: This might not work on some systems, because it depends on the current working directory
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if($firstrun) {
            $this->query("CREATE TABLE store (key TEXT UNIQUE NOT NULL, value TEXT)");
        }
    }
    /**
     * Makes a query against the database.
     * @param	string	$sql		The (potentially parametised) query to make.
     * @param	array	$variables	Optional. The variables to substitute into the SQL query.
     * @return	\PDOStatement		The result of the query, as a PDOStatement.
     */
    private function query(string $sql, array $variables = []) {
        // FUTURE: Optionally cache prepared statements?
        $statement = $this->db->prepare($sql);
        $statement->execute($variables);

        return $statement; // fetchColumn(), fetchAll(), etc. are defined on the statement, not the return value of execute()
    }

    /**
     * Determines if the given key exists in the store or not.
     * @param	string	$key	The key to test.
     * @return	bool	Whether the key exists in the store or not.
     */
    public function has(string $key) : bool {
        return $this->query(
                "SELECT COUNT(key) FROM store WHERE key = :key;",
                [ "key" => $key ]
            )->fetchColumn() > 0;
    }

    /**
     * Gets a value from the store.
     * @param	string	$key	The key to store the value under.
     * @return	string	The value to store.
     */
    public function get(string $key) : string {
        return $this->query(
            "SELECT value FROM store WHERE key = :key;",
            [ "key" => $key ]
        )->fetchColumn();
    }

    /**
     * Sets a value in the data store.
     * @param	string	$key	The key to set the value of.
     * @param	string	$value	The value to store.
     */
    public function set(string $key, string $value) : void {
        $this->query(
            "INSERT OR REPLACE INTO store(key, value) VALUES(:key, :value)",
            [
                "key" => $key,
                "value" => $value
            ]
        );
    }

    /**
     * Deletes an item from the data store.
     * @param	string	$key	The key of the item to delete.
     * @return	bool	Whether it was really deleted or not. Note that if it doesn't exist, then it can't be deleted.
     */
    public function delete(string $key) : bool {
        return $this->query(
                "DELETE FROM store WHERE key = :key;",
                [ "key" => $key ]
            )->rowCount() > 0;
    }

    /**
     * Empties the store.
     */
    public function clear() : void {
        $this->query("DELETE FROM store;");
    }
}
