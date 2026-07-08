<?php

declare(strict_types=1);

/**
 * Book Functions Test Suite
 * 
 * Tests the procedural book functions for:
 * - CRUD operations
 * - Query functions
 * - Validation
 * 
 * @package Library\Tests\Procedural
 */
class BookFunctionsTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PDO Database connection */
    private \PDO $pdo;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->pdo = createTestDatabase();
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        $this->pdo = null;
        cleanupTestDatabase();
    }

    /**
     * Test getDatabaseConnection returns PDO
     */
    public function testGetDatabaseConnectionReturnsPdo(): void
    {
        $dbPath = __DIR__ . '/../../database/test_proc_conn.db';
        
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        
        $pdo = getDatabaseConnection($dbPath);
        
        $this->assertInstanceOf(\PDO::class, $pdo);
        
        unset($pdo);
        unlink($dbPath);
    }

    /**
     * Test findBookById returns book when exists
     */
    public function testFindBookByIdReturnsBookWhenExists(): void
    {
        $book = findBookById($this->pdo, 1);
        
        $this->assertIsArray($book);
        $this->assertEquals(1, $book['id']);
        $this->assertEquals('1984', $book['title']);
    }

    /**
     * Test findBookById returns null when not exists
     */
    public function testFindBookByIdReturnsNullWhenNotExists(): void
    {
        $book = findBookById($this->pdo, 999);
        
        $this->assertNull($book);
    }

    /**
     * Test findBookByIsbn returns book when exists
     */
    public function testFindBookByIsbnReturnsBookWhenExists(): void
    {
        $book = findBookByIsbn($this->pdo, '978-0451524935');
        
        $this->assertIsArray($book);
        $this->assertEquals('1984', $book['title']);
    }

    /**
     * Test findAllBooks returns all books
     */
    public function testFindAllBooksReturnsAllBooks(): void
    {
        $books = findAllBooks($this->pdo);
        
        $this->assertIsArray($books);
        $this->assertCount(10, $books);
    }

    /**
     * Test findAllBooks respects limit
     */
    public function testFindAllBooksRespectsLimit(): void
    {
        $books = findAllBooks($this->pdo, 5);
        
        $this->assertCount(5, $books);
    }

    /**
     * Test findBooksByGenre returns books in genre
     */
    public function testFindBooksByGenreReturnsBooksInGenre(): void
    {
        $books = findBooksByGenre($this->pdo, 'Dystopian');
        
        $this->assertIsArray($books);
        $this->assertCount(1, $books);
    }

    /**
     * Test createBook creates new book
     */
    public function testCreateBookCreatesNewBook(): void
    {
        $id = createBook($this->pdo, [
            'title' => 'Procedural Book',
            'author_id' => 1,
            'isbn' => '978-0000000001',
            'genre' => 'Fiction',
            'published_year' => 2024,
            'available_copies' => 2,
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        $book = findBookById($this->pdo, $id);
        $this->assertEquals('Procedural Book', $book['title']);
    }

    /**
     * Test createBook throws exception for missing fields
     */
    public function testCreateBookThrowsExceptionForMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        createBook($this->pdo, [
            'title' => 'Incomplete',
        ]);
    }

    /**
     * Test createBook throws exception for duplicate ISBN
     */
    public function testCreateBookThrowsExceptionForDuplicateIsbn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        createBook($this->pdo, [
            'title' => 'Duplicate',
            'author_id' => 1,
            'isbn' => '978-0451524935', // Already exists
            'genre' => 'Fiction',
        ]);
    }

    /**
     * Test updateBook modifies existing book
     */
    public function testUpdateBookModifiesExistingBook(): void
    {
        $result = updateBook($this->pdo, 1, ['title' => 'Updated']);
        
        $this->assertTrue($result);
        
        $book = findBookById($this->pdo, 1);
        $this->assertEquals('Updated', $book['title']);
    }

    /**
     * Test deleteBook removes book
     */
    public function testDeleteBookRemovesBook(): void
    {
        $result = deleteBook($this->pdo, 1);
        
        $this->assertTrue($result);
        
        $book = findBookById($this->pdo, 1);
        $this->assertNull($book);
    }

    /**
     * Test bookExistsByIsbn returns true when exists
     */
    public function testBookExistsByIsbnReturnsTrueWhenExists(): void
    {
        $this->assertTrue(bookExistsByIsbn($this->pdo, '978-0451524935'));
    }

    /**
     * Test bookExistsByIsbn returns false when not exists
     */
    public function testBookExistsByIsbnReturnsFalseWhenNotExists(): void
    {
        $this->assertFalse(bookExistsByIsbn($this->pdo, '000-0000000000'));
    }

    /**
     * Test getAvailableCopies returns correct count
     */
    public function testGetAvailableCopiesReturnsCorrectCount(): void
    {
        $copies = getAvailableCopies($this->pdo, 1);
        
        $this->assertEquals(3, $copies);
    }

    /**
     * Test updateAvailableCopies changes count
     */
    public function testUpdateAvailableCopiesChangesCount(): void
    {
        $result = updateAvailableCopies($this->pdo, 1, 2);
        
        $this->assertTrue($result);
        
        $copies = getAvailableCopies($this->pdo, 1);
        $this->assertEquals(5, $copies);
    }

    /**
     * Test formatBook returns formatted string
     */
    public function testFormatBookReturnsFormattedString(): void
    {
        $book = ['id' => 1, 'title' => 'Test', 'genre' => 'Fiction', 'available_copies' => 2];
        
        $formatted = formatBook($book);
        
        $this->assertIsString($formatted);
        $this->assertStringContainsString('Test', $formatted);
        $this->assertStringContainsString('Fiction', $formatted);
    }
}
