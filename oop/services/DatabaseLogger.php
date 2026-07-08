<?php

declare(strict_types=1);

require_once __DIR__ . '/../interfaces/LoggerInterface.php';
require_once __DIR__ . '/../interfaces/NotificationInterface.php';

/**
 * Database Logger
 * 
 * Implements LoggerInterface using database storage.
 * Demonstrates Dependency Inversion Principle (DIP) - provides a concrete
 * implementation of the LoggerInterface abstraction.
 * 
 * Demonstrates Single Responsibility Principle (SRP) - only handles logging
 * operations to the database.
 * 
 * @package Library\OOP\Services
 */
class DatabaseLogger implements LoggerInterface
{
    /**
     * Constructor
     * 
     * @param \PDO $database The database connection
     */
    public function __construct(
        private \PDO $database
    ) {}

    /**
     * {@inheritdoc}
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Write a log entry to the database
     * 
     * @param string $level The log level
     * @param string $message The log message
     * @param array $context Additional context
     * @return void
     */
    private function log(string $level, string $message, array $context): void
    {
        $details = json_encode([
            'level' => $level,
            'context' => $context,
            'message' => $message,
        ]);
        
        $stmt = $this->database->prepare(
            'INSERT INTO activity_log (action, entity_type, details) VALUES (:action, :entity_type, :details)'
        );
        
        $stmt->execute([
            'action' => strtolower($level),
            'entity_type' => 'system',
            'details' => $details,
        ]);
    }
}
