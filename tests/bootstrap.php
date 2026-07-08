<?php

declare(strict_types=1);

/**
 * PHPUnit Test Bootstrap
 * 
 * Sets up the testing environment including:
 * - Error reporting configuration
 * - Database setup for tests
 * - Class autoloading
 * 
 * @package Library\Tests
 */

// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

// Database path for tests (separate from production)
define('TEST_DB_PATH', __DIR__ . '/../database/test_library.db');

// Include necessary files
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../oop/interfaces/BookRepositoryInterface.php';
require_once __DIR__ . '/../oop/interfaces/MemberRepositoryInterface.php';
require_once __DIR__ . '/../oop/interfaces/LoanRepositoryInterface.php';
require_once __DIR__ . '/../oop/interfaces/LoggerInterface.php';
require_once __DIR__ . '/../oop/interfaces/NotificationInterface.php';
require_once __DIR__ . '/../oop/models/Book.php';
require_once __DIR__ . '/../oop/models/Member.php';
require_once __DIR__ . '/../oop/models/Loan.php';
require_once __DIR__ . '/../oop/repositories/BookRepository.php';
require_once __DIR__ . '/../oop/repositories/MemberRepository.php';
require_once __DIR__ . '/../oop/repositories/LoanRepository.php';
require_once __DIR__ . '/../oop/services/BookService.php';
require_once __DIR__ . '/../oop/services/LoanService.php';
require_once __DIR__ . '/../oop/services/DatabaseLogger.php';
require_once __DIR__ . '/../oop/services/ConsoleNotificationService.php';

// Include procedural functions
require_once __DIR__ . '/../procedural/functions/book_functions.php';
require_once __DIR__ . '/../procedural/functions/member_functions.php';
require_once __DIR__ . '/../procedural/functions/loan_functions.php';
require_once __DIR__ . '/../procedural/functions/notification_functions.php';

/**
 * Set up a fresh test database
 * 
 * @return \PDO Database connection
 */
function createTestDatabase(): \PDO
{
    // Remove existing test database
    if (file_exists(TEST_DB_PATH)) {
        unlink(TEST_DB_PATH);
    }
    
    // Create new connection
    $pdo = new \PDO(
        dsn: "sqlite:" . TEST_DB_PATH,
        options: [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]
    );
    
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Initialize schema
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schema);
    
    // Load seed data
    $seed = file_get_contents(__DIR__ . '/../database/seed.sql');
    $pdo->exec($seed);
    
    return $pdo;
}

/**
 * Clean up test database
 * 
 * @return void
 */
function cleanupTestDatabase(): void
{
    if (file_exists(TEST_DB_PATH)) {
        unlink(TEST_DB_PATH);
    }
}

/**
 * Simple test assertion helper
 * 
 * @param bool $condition The condition to test
 * @param string $message The failure message
 * @return void
 * @throws \AssertionError If condition is false
 */
function assert(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new \AssertionError($message ?: 'Assertion failed');
    }
}
