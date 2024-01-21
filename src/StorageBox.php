<?php

namespace Survos\KeyValueBundle;

use \PDO;
use Symfony\Component\Cache\CacheItem;

/*
 *
 * https://gist.github.com/sbrl/c3bfbbbb3d1419332e9ece1bac8bb71c
 *
 * a multi-table, multi-lingual Sqlite key-value data store.
 * @license Apache 2.0
 */

class StorageBox
{
    /**
     * The SQLite database connection.
     * @var \PDO
     */
    private \PDO $db;
    private array $tables = [];

    /**
     * Initialises a new store connection.
     * @param string $filename The filename that the store is located in.
     * @param array $tablesToCreate The ADDITIONAL tables to create with writing.  Others may already exist.
     */
    function __construct(private string  $filename,
                         private array   $tablesToCreate = [],
                         private ?string $currentTable = null
    )
    {
        // @todo: bind
        $this->db = new \PDO("sqlite:" . ($path = $filename));
//        $this->db = new \SQLite3($this->filename);
        $path = $this->filename;
//        dd($this->filename, $path);
        // HACK: This might not work on some systems, because it depends on the current working directory
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sth = $this->db->query($sql = "SELECT name FROM sqlite_master where type='table'");
        $this->tables = $sth->fetchAll(PDO::FETCH_COLUMN); // load the existing tables
        foreach ($this->tablesToCreate as $table) {
            if (!in_array($table, $this->tables)) {
                $this->createTable($table);
                $this->tables[] = $table;
            }
        }
        // defaults to first table
        $this->currentTable = current($this->tablesToCreate)??current($this->tables);
    }

    public function select(string $tableName): self
    {
        assert(in_array($tableName, $this->tables), "Missing $tableName in $this->filename, initialize with tablesToCreate");
        $this->currentTable = $tableName;
        return $this;

    }

    public function createTable(string $tableName): void
    {
        $sth = $this->query(sprintf("CREATE TABLE IF NOT EXISTS %s (key TEXT UNIQUE NOT NULL, value TEXT)", $tableName));

    }

    public function getFilename(): string
    {
        return realpath($this->filename);
    }

//    public function getItem(string $key): CacheItem
//    {
//        $item = new CacheItem();
//    }
    /**
     * Makes a query against the database.
     * @param string $sql The (potentially parametised) query to make.
     * @param array $variables Optional. The variables to substitute into the SQL query.
     * @return    \PDOStatement        The result of the query, as a PDOStatement.
     */
    private function query(string $sql, array $variables = []): \PDOStatement
    {
        // FUTURE: Optionally cache prepared statements?
        $statement = $this->db->prepare($sql);
        $statement->execute($variables);

        return $statement; // fetchColumn(), fetchAll(), etc. are defined on the statement, not the return value of execute()
    }

    /**
     * Determines if the given key exists in the store or not.
     * @param string $key The key to test.
     * @return    bool    Whether the key exists in the store or not.
     */
    public function has(string $key, string $table=null): bool
    {
        // sqlite EXISTS might be faster
        return $this->query(
                sprintf("SELECT COUNT(key) FROM %s WHERE key = :key;", $table??$this->currentTable),
                ["key" => $key]
            )->fetchColumn() > 0;
    }

    public function count(string $tableName=null): int
    {
        try {
            return $this->query(
                $sql = "SELECT COUNT(1) FROM " . ($tableName??$this->currentTable),
            )->fetchColumn();
        } catch (\Exception $exception) {
            dd($sql, $exception);
        }
    }

    public function getSelectedTable(): ?string
    {
        return $this->currentTable;

    }

    /**
     * Gets a value from the store.
     * @param string $key The key to store the value under.
     * @return    string    The value to store.
     */
    public function get(string $key, string $table = null, callable $fn = null): ?string
    {
        if (!$this->has($key)) {
            if ($fn) {
                $value = $fn($key);
                $this->set($key, $value);
                return $value;
            }
            return null; // ? throw exception?
        }
        return $this->query(
            sprintf("SELECT value FROM %s WHERE key = :key;", $table??$this->currentTable),
            ["key" => $key]
        )->fetchColumn();
    }
    public function getValueObject(string $key, string $table = null, callable $fn = null): ?object
    {
        return json_decode($this->get($key, $table), true);
    }

    public function setValueObject(string $key, string $table = null, object|array $value): void
    {
        $this->set($key, json_encode($value), $table);
    }

    /**
     * Sets a value in the data store.
     * @param string $key The key to set the value of.
     * @param string $value The value to store.
     */
    public function set(string $key, string|array|object $value, string $table=null): void
    {
        $this->query(
            sprintf("INSERT OR REPLACE INTO %s(key, value) VALUES(:key, :value)", $table??$this->currentTable),
            [
                "key" => $key,
                "value" => is_string($value) ? $value : json_encode($value)
            ]
        );
    }

    /**
     * Deletes an item from the data store.
     * @param string $key The key of the item to delete.
     * @return    bool    Whether it was really deleted or not. Note that if it doesn't exist, then it can't be deleted.
     */
    public function delete(string $key, string $table=null): bool
    {
        return $this->query(
                sprintf("DELETE FROM %s WHERE key = :key;", $table??$this->currentTable),
                ["key" => $key]
            )->rowCount() > 0;
    }

    /**
     * Empties the store.
     */
    public function clear(): void
    {
        $this->query("DELETE FROM $this->currentTable;");
    }

    public function iterate(string $table = null): \Generator
    {
        $sth = $this->query("select key, value from " . ($table??$this->currentTable));
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            yield $row['key'] => $row['value'];
        }
    }

    public function getKeys(string $table = null): array
    {
        $sth = $this->query("select key from " . ($table??$this->currentTable));
        return $sth->fetchAll(PDO::FETCH_COLUMN);
    }

    public function commit()
    {
        $this->db->commit();
    }
}
