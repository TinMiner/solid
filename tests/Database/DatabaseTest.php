<?php

declare(strict_types=1);

/**
 * Database Test Suite
 * 
 * Tests the Database class for:
 * - Connection management
 * - Schema initialization
 * - Transaction support
 * - Singleton pattern
 * 
 * @package Library\Tests\Database
 */
class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Database getInstance returns singleton
     */
    public function testGetInstanceReturnsSingleton(): void
    {
        $dbPath = __DIR__ . '/../../database/test_singleton.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db1 = Database::getInstance($dbPath);
        $db2 = Database::getInstance($dbPath);
        
        $this->assertSame($db1, $db2);
        
        Database::resetInstance();
        unlink($dbPath);
    }

    /**
     * Test Database getConnection returns PDO
     */
    public function testGetConnectionReturnsPdo(): void
    {
        $dbPath = __DIR__ . '/../../database/test_connection.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db = Database::getInstance($dbPath);
        $connection = $db->getConnection();
        
        $this->assertInstanceOf(\PDO::class, $connection);
        
        Database::resetInstance();
        unlink($dbPath);
    }

    /**
     * Test Database initialize creates tables
     */
    public function testInitializeCreatesTables(): void
    {
        $dbPath = __DIR__ . '/../../database/test_initialize.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db = Database::getInstance($dbPath);
        $db->initialize(
            schemaFile: __DIR__ . '/../../database/schema.sql',
            seedFile: __DIR__ . '/../../database/seed.sql'
        );
        
        // Verify tables exist
        $tables = $db->getConnection()->query(
            "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
        )->fetchAll(\PDO::FETCH_COLUMN);
        
        $this->assertContains('books', $tables);
        $this->assertContains('members', $tables);
        $this->assertContains('loans', $tables);
        $this->assertContains('authors', $tables);
        $this->assertContains('activity_log', $tables);
        
        Database::resetInstance();
        unlink($dbPath);
    }

    /**
     * Test Database executeSqlFile executes SQL
     */
    public function testExecuteSqlFileExecutesSql(): void
    {
        $dbPath = __DIR__ . '/../../database/test_executesql.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db = Database::getInstance($dbPath);
        
        // Create a simple table
        $db->executeSqlFile(__DIR__ . '/../../database/schema.sql');
        
        // Verify books table exists
        $result = $db->getConnection()->query(
            "SELECT COUNT(*) FROM books"
        )->fetchColumn();
        
        $this->assertIsNumeric($result);
        
        Database::resetInstance();
        unlink($dbPath);
    }

    /**
     * Test Database executeSqlFile throws exception for missing file
     */
    public function testExecuteSqlFileThrowsExceptionForMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        
        $dbPath = __DIR__ . '/../../database/test_missing.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db = Database::getInstance($dbPath);
        $db->executeSqlFile('/nonexistent/file.sql');
        
        Database::resetInstance();
        unlink($dbPath);
    }

    /**
     * Test Database lastInsertId returns integer
     */
    public function testLastInsertIdReturnsInteger(): void
    {
        $dbPath = __DIR__ . '/../../database/test_lastinsert.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db = Database::getInstance($dbPath);
        $db->executeSqlFile(__DIR__ . '/../../database/schema.sql');
        
        // Insert a test record
        $db->getConnection()->exec(
            "INSERT INTO authors (name, email) VALUES ('Test Author', 'test@example.com')"
        );
        
        $lastId = $db->lastInsertId();
        
        $this->assertIsInt($lastId);
        $this->assertGreaterThan(0, $lastId);
        
        Database::resetInstance();
        unlink($dbPath);
    }

    /**
     * Test Database transaction methods work
     */
    public function testTransactionMethodsWork(): void
    {
        $dbPath = __DIR__ . '/../../database/test_transaction.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db = Database::getInstance($dbPath);
        $db->executeSqlFile(__DIR__ . '/../../database/schema.sql');
        
        // Test begin transaction
        $this->assertTrue($db->beginTransaction());
        
        // Insert in transaction
        $db->getConnection()->exec(
            "INSERT INTO authors (name, email) VALUES ('Transaction Author', 'trans@example.com')"
        );
        
        // Test commit
        $this->assertTrue($db->commit());
        
        // Verify data persists
        $result = $db->getConnection()->query(
            "SELECT COUNT(*) FROM authors WHERE name = 'Transaction Author'"
        )->fetchColumn();
        
        $this->assertEquals(1, $result);
        
        Database::resetInstance();
        unlink($dbPath);
    }

    /**
     * Test Database rollback reverts changes
     */
    public function testRollbackRevertsChanges(): void
    {
        $dbPath = __DIR__ . '/../../database/test_rollback.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db = Database::getInstance($dbPath);
        $db->executeSqlFile(__DIR__ . '/../../database/schema.sql');
        
        // Start transaction
        $db->beginTransaction();
        
        // Insert in transaction
        $db->getConnection()->exec(
            "INSERT INTO authors (name, email) VALUES ('Rollback Author', 'rollback@example.com')"
        );
        
        // Rollback
        $this->assertTrue($db->rollback());
        
        // Verify data is gone
        $result = $db->getConnection()->query(
            "SELECT COUNT(*) FROM authors WHERE name = 'Rollback Author'"
        )->fetchColumn();
        
        $this->assertEquals(0, $result);
        
        Database::resetInstance();
        unlink($dbPath);
    }

    /**
     * Test Database resetInstance clears singleton
     */
    public function testResetInstanceClearsSingleton(): void
    {
        $dbPath = __DIR__ . '/../../database/test_reset.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $db1 = Database::getInstance($dbPath);
        Database::resetInstance();
        $db2 = Database::getInstance($dbPath);
        
        $this->assertNotSame($db1, $db2);
        
        Database::resetInstance();
        unlink($dbPath);
    }
}
