<?php

declare(strict_types=1);

/**
 * Database Connection Handler
 * 
 * Provides a singleton-like SQLite database connection with schema and seed management.
 * Follows the Single Responsibility Principle - only handles database connectivity.
 * 
 * @package Library\Database
 */
class Database
{
    /** @var Database|null Singleton instance */
    private static ?Database $instance = null;

    /** @var \PDO SQLite connection */
    private \PDO $connection;

    /** @var string Database file path */
    private string $dbPath;

    /**
     * Private constructor for singleton pattern
     * 
     * @param string $dbPath Path to SQLite database file
     * @throws \RuntimeException If connection cannot be established
     */
    private function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
        
        try {
            $this->connection = new \PDO(
                dsn: "sqlite:{$dbPath}",
                options: [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]
            );
            
            // Enable foreign key support
            $this->connection->exec('PRAGMA foreign_keys = ON');
            $this->connection->exec('PRAGMA journal_mode = WAL');
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                message: "Database connection failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Get database instance (singleton)
     * 
     * @param string $dbPath Path to SQLite database file
     * @return Database
     */
    public static function getInstance(string $dbPath = __DIR__ . '/library.db'): Database
    {
        if (self::$instance === null) {
            self::$instance = new self($dbPath);
        }

        return self::$instance;
    }

    /**
     * Get the PDO connection
     * 
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * Execute SQL from a file
     * 
     * @param string $sqlFile Path to SQL file
     * @return bool True on success
     * @throws \RuntimeException If file cannot be read or executed
     */
    public function executeSqlFile(string $sqlFile): bool
    {
        if (!file_exists($sqlFile)) {
            throw new \RuntimeException("SQL file not found: {$sqlFile}");
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new \RuntimeException("Cannot read SQL file: {$sqlFile}");
        }

        try {
            $this->connection->exec($sql);
            return true;
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                message: "SQL execution failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Initialize database with schema and seed data
     * 
     * @param string $schemaFile Path to schema SQL file
     * @param string $seedFile Path to seed SQL file (optional)
     * @return void
     */
    public function initialize(string $schemaFile, ?string $seedFile = null): void
    {
        $this->executeSqlFile($schemaFile);
        
        if ($seedFile !== null && file_exists($seedFile)) {
            $this->executeSqlFile($seedFile);
        }
    }

    /**
     * Get the last inserted row ID
     * 
     * @return int
     */
    public function lastInsertId(): int
    {
        return (int) $this->connection->lastInsertId();
    }

    /**
     * Begin a database transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit the current transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback the current transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Reset singleton instance (useful for testing)
     * 
     * @return void
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize singleton");
    }
}
