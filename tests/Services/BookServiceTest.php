<?php

declare(strict_types=1);

/**
 * BookService Test Suite
 * 
 * Tests the BookService class for:
 * - Business logic operations
 * - Validation rules
 * - Error handling
 * - Integration with repositories
 * 
 * @package Library\Tests\Services
 */
class BookServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PDO Database connection */
    private \PDO $pdo;
    
    /** @var BookService Service instance */
    private BookService $service;
    
    /** @var DatabaseLogger Logger instance */
    private DatabaseLogger $logger;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->pdo = createTestDatabase();
        $bookRepository = new BookRepository($this->pdo);
        $this->logger = new DatabaseLogger($this->pdo);
        $this->service = new BookService($bookRepository, $this->logger);
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
     * Test getBook returns Book instance when exists
     */
    public function testGetBookReturnsBookInstanceWhenExists(): void
    {
        $book = $this->service->getBook(1);
        
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(1, $book->getId());
        $this->assertEquals('1984', $book->getTitle());
    }

    /**
     * Test getBook returns null when not exists
     */
    public function testGetBookReturnsNullWhenNotExists(): void
    {
        $book = $this->service->getBook(999);
        
        $this->assertNull($book);
    }

    /**
     * Test getAllBooks returns array of Book instances
     */
    public function testGetAllBooksReturnsArrayOfBookInstances(): void
    {
        $books = $this->service->getAllBooks();
        
        $this->assertIsArray($books);
        $this->assertCount(10, $books);
        
        foreach ($books as $book) {
            $this->assertInstanceOf(Book::class, $book);
        }
    }

    /**
     * Test getBooksByGenre returns filtered books
     */
    public function testGetBooksByGenreReturnsFilteredBooks(): void
    {
        $books = $this->service->getBooksByGenre('Dystopian');
        
        $this->assertIsArray($books);
        $this->assertCount(1, $books);
        $this->assertEquals('Dystopian', $books[0]->getGenre());
    }

    /**
     * Test getBooksByAuthor returns books by author
     */
    public function testGetBooksByAuthorReturnsBooksByAuthor(): void
    {
        $books = $this->service->getBooksByAuthor(1); // George Orwell
        
        $this->assertIsArray($books);
        $this->assertCount(2, $books);
    }

    /**
     * Test addBook creates new book successfully
     */
    public function testAddBookCreatesNewBookSuccessfully(): void
    {
        $book = $this->service->addBook([
            'title' => 'New Book',
            'author_id' => 1,
            'isbn' => '978-0000000001',
            'genre' => 'Fiction',
            'published_year' => 2024,
            'available_copies' => 2,
        ]);
        
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('New Book', $book->getTitle());
        $this->assertEquals('978-0000000001', $book->getIsbn());
    }

    /**
     * Test addBook throws exception for missing required fields
     */
    public function testAddBookThrowsExceptionForMissingRequiredFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->addBook([
            'title' => 'Incomplete Book',
            // Missing author_id, isbn, genre
        ]);
    }

    /**
     * Test addBook throws exception for duplicate ISBN
     */
    public function testAddBookThrowsExceptionForDuplicateIsbn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->addBook([
            'title' => 'Duplicate ISBN Book',
            'author_id' => 1,
            'isbn' => '978-0451524935', // Already exists (1984)
            'genre' => 'Fiction',
        ]);
    }

    /**
     * Test updateBook modifies existing book
     */
    public function testUpdateBookModifiesExistingBook(): void
    {
        $book = $this->service->updateBook(1, [
            'title' => 'Updated Title',
        ]);
        
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals('Updated Title', $book->getTitle());
    }

    /**
     * Test updateBook returns null for non-existent book
     */
    public function testUpdateBookReturnsNullForNonExistentBook(): void
    {
        $book = $this->service->updateBook(999, [
            'title' => 'Will Not Work',
        ]);
        
        $this->assertNull($book);
    }

    /**
     * Test deleteBook removes book successfully
     */
    public function testDeleteBookRemovesBookSuccessfully(): void
    {
        $result = $this->service->deleteBook(1);
        
        $this->assertTrue($result);
        
        $book = $this->service->getBook(1);
        $this->assertNull($book);
    }

    /**
     * Test deleteBook returns false for non-existent book
     */
    public function testDeleteBookReturnsFalseForNonExistentBook(): void
    {
        $result = $this->service->deleteBook(999);
        
        $this->assertFalse($result);
    }

    /**
     * Test searchBooks finds matching books
     */
    public function testSearchBooksFindsMatchingBooks(): void
    {
        $books = $this->service->searchBooks('1984');
        
        $this->assertIsArray($books);
        $this->assertGreaterThanOrEqual(1, count($books));
    }

    /**
     * Test getBookAvailability returns availability info
     */
    public function testGetBookAvailabilityReturnsAvailabilityInfo(): void
    {
        $info = $this->service->getBookAvailability(1);
        
        $this->assertIsArray($info);
        $this->assertEquals(1, $info['book_id']);
        $this->assertArrayHasKey('available_copies', $info);
        $this->assertArrayHasKey('is_available', $info);
    }

    /**
     * Test getBookAvailability returns null for non-existent book
     */
    public function testGetBookAvailabilityReturnsNullForNonExistentBook(): void
    {
        $info = $this->service->getBookAvailability(999);
        
        $this->assertNull($info);
    }
}
