<?php

namespace Survos\KeyValueBundle;

// see https://github.com/bungle/web.php/blob/master/sqlite.php for a wrapper without PDO

use Doctrine\DBAL\DriverManager;
use JetBrains\PhpStorm\NoReturn;
use \PDO;
use Psr\Log\LoggerInterface;
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
    private bool $inTransaction = false;

    /**
     * Initialises a new store connection.
     * @param string $filename The filename that the store is located in.
     * @param array $tablesToCreate The ADDITIONAL tables to create with writing.  Others may already exist.
     */
    #[NoReturn] function __construct(private string                    $filename,
                                     private array                     $tablesToCreate = [],
                                     private ?string                   $currentTable = null,
                                     private string $valueType='json', // eventually jsonb
                                     private readonly ?LoggerInterface $logger=null,
    )
    {
        $path = $this->filename;

        // Enable WAL mode to fix locking issues?
//        $this->beginTransaction();
//        $sqlite3 = new \SQLite3($this->filename);
//        dd($this->filename, $path);
        // HACK: This might not work on some systems, because it depends on the current working directory

        // PDO creates the db if it doesn't exist, so check after
        if (!file_exists($path)) {
            $this->db = new \PDO("sqlite:" . $path);
//            $this->db->query("PRAGMA journal_mode=WAL");
//            $this->db->query("PRAGMA lock_timeout=5");
            $this->db->setAttribute(PDO::ATTR_TIMEOUT, 10);
//            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            $this->db = new \PDO("sqlite:" . $path);
        }

        $sth = $this->db->query($sql = "SELECT name FROM sqlite_master where type='table'");
        $this->tables = $sth->fetchAll(PDO::FETCH_COLUMN); // load the existing tables

        foreach ($this->tablesToCreate as $table=>$indexes) {
            if (!in_array($table, $this->tables)) {
                $this->createTable($table, $indexes, $this->valueType);
                $this->tables[] = $table;
            }
        }
        // defaults to first table
        $this->currentTable = current($this->tablesToCreate)??current($this->tables);
//        $this->commit();
//        $this->beginTransaction();
        assert(!$this->db->inTransaction());
    }

    public function select(string $tableName): self
    {
        assert(in_array($tableName, $this->tables), "Missing $tableName in $this->filename, initialize with tablesToCreate");
        $this->currentTable = $tableName;
        return $this;

    }

    public function inspectSchema(string $filename=null)
    {

        $filename = $filename??$this->getFilename();
        // is it necessary to open it again, or can we get this from the PDO?
        $connectionParams = [
            'path' => $filename,
            'driver' => 'pdo_sqlite',
        ];
        try {
            $conn = DriverManager::getConnection($connectionParams);
            $sm = $conn->createSchemaManager();
            $fromSchema = $sm->introspectSchema();

            $tables = [];
            foreach ($fromSchema->getTables() as $table) {
                $columns = [];
                foreach ($table->getColumns() as $column) {
                    $tables[$table->getName()]['columns'][] = $column->getName();
                }
            }

            dd($fromSchema, $tables);
        } catch (\Exception $exception) {
            dd($exception, $filename);
        }
    }


    public function createTable(string $tableName, string $indexes, string $valueType, string $primaryKeyType='int'): void
    {
        // @todo: improve PK: https://www.sqlitetutorial.net/sqlite-primary-key/
        $primaryKey = 'key TEXT PRIMARY KEY';
        $columns = [
            'value TEXT NOT NULL',
        ];
        foreach (explode(',', $indexes) as $index) {
            if (str_contains($index, '|')) {
                [$index, $type] = explode('|', $index);
            } else {
                $type = 'TEXT';
            }
            // @todo: handle auto-increment
            if (str_starts_with($index, '++'))
            {
                $index = substr($index, 2);
                $primaryKey = "$index $type PRIMARY KEY";
            } else {
//                https://www.sqlite.org/gencol.html 
                $columns[] = "$index $type";
            }
        }
        array_unshift($columns, $primaryKey);
        $sql = sprintf("CREATE TABLE IF NOT EXISTS %s (%s)", $tableName, join(",\n", $columns));
        dd($sql);
        $sth = $this->query($sql);
    }

    private function log($msg)
    {
            $this->logger->warning($msg);
        if ($this->logger) {
        }

    }
    public function close()
    {
        $this->log(__METHOD__);
        // if a transaction, commit it
        if ($this->db->inTransaction()) {
            $this->commit();
        }
         unset($this->db);
    }
    public function beginTransaction()
    {
        $this->log(__METHOD__);
        assert(!$this->db->inTransaction(), "already in a transaction");

        $this->db->beginTransaction();
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
        static $preparedStatements=[];
        // cache prepared statements
        if (empty($preparedStatements[$sql])) {
            $preparedStatements[$sql] = $this->db->prepare($sql);;
        }
        $statement = $preparedStatements[$sql];
        $statement->execute($variables);

        return $statement; // fetchColumn(), fetchAll(), etc. are defined on the statement, not the return value of execute()
    }

    /**
     * Determines if the given key exists in the store or not.
     * @param string $key The key to test.
     * @return    bool    Whether the key exists in the store or not.
     */
    public function has(string $key, string $table=null, bool $preloadKeys=false): bool
    {
        static $keyCache = [];
        $table =  $table??$this->currentTable;

        if ($preloadKeys) {
            if (empty($keyCache[$table])) {
                $keyCache[$table] = $this->query("SELECT key from $table")->fetchAll(PDO::FETCH_COLUMN);
            }
            return in_array($key, $keyCache[$table]);
        }

        //
        // sqlite EXISTS might be faster
        return $this->query(
                "SELECT COUNT(key) FROM $table WHERE key = :key",
                ["key" => $key]
            )->fetchColumn() > 0;
    }

    public function count(string $table=null): int
    {
//        assert(!$this->db->inTransaction(), "already in a transaction");
        $table=$table??$this->currentTable;
        try {
            $count = $this->query($sql = "SELECT COUNT(key) FROM $table")->fetchColumn();
            $this->log("$table has $count");
            return $count;
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
    public function get(string $key, string $table = null, callable $fn = null): string|object|array|null
    {
        if (!$this->has($key)) {
            if ($fn) {
                $value = $fn($key);
                $this->set($key, $value);
                return $value;
            }
            return null; // ? throw exception?
        }
        $results = $this->query(
            sprintf("SELECT value FROM %s WHERE key = :key;", $table??$this->currentTable),
            ["key" => $key]
        )->fetchColumn();
        return json_decode($results, true);
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
    public function set(string $key, string|array|object $value, string $table=null): mixed
    {

        static $preparedStatements=[];
        $table = $table??$this->currentTable;
        if (empty($preparedStatements[$table])) {
            $preparedStatements[$table] =
                $this->db->prepare("
                    INSERT OR REPLACE INTO $table(key, value) 
                        VALUES(:key, :value)
                ");
        }
        $statement = $preparedStatements[$table];
        // @todo: defer these in a batch
        assert($this->db->inTransaction());
        $results = $statement->execute([
            "key" => $key,
            "value" => is_string($value) ? $value : json_encode($value)
        ]);
        if (!$results) {
            dd("Error: " . $statement->errorInfo()[2]);
        }
        return $results;
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

    public function iterate(string $table = null,  ?bool $associative = null, int $depth = 512, int $flags = 0): \Generator
    {
        // https://stackoverflow.com/questions/78623214/using-a-generator-to-loop-through-an-update-a-table-in-pdo
        $sth = $this->query("select key, value from " . ($table??$this->currentTable));
//        return $sth->execute();
        $all = $sth->fetchAll($flags);

        foreach ($all as $idx => $row)
//        foreach ($sth as $idx => $row)
//        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $value = $row['value'];
//            dump($value);
            $value = json_decode($value, $associative, $depth, $flags);;
//            dd($value);
            // check, or is the value always json?
            yield $row['key'] => $value;
        }
    }

    public function iterateOver(string $table = null, callable $callback, ?bool $associative = null, int $depth = 512, int $flags = 0): array
    {
        $results=[];
        foreach ($this->iterate($table, $associative, $depth, $flags) as $key => $value) {
            $results[$key] = $callback($key, $value);
        }
        return $results;
    }

    public function getKeys(string $table = null): array
    {
        $sth = $this->query("select key from " . ($table??$this->currentTable));
        return $sth->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getTables(): array
    {
        $sth = $this->query("SELECT name FROM sqlite_master WHERE type='table';");
        return $sth->fetchAll(PDO::FETCH_COLUMN);
    }

    public function tableExists(string $table): bool
    {
        $sth = $this->query("SELECT * FROM sqlite_master WHERE name='$table' type='table';");
        return count($sth->fetchAll(PDO::FETCH_COLUMN)) > 0;
    }

    public function commit()
    {
        $this->log(__METHOD__);
        // we could check the db, too
        assert($this->db->inTransaction(), "NOT in a transaction");
        $this->db->commit();
        assert(!$this->db->inTransaction(), "STILL in a transaction");
    }

}
