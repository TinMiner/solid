<?php

declare(strict_types=1);

/**
 * BookRepository Test Suite
 * 
 * Tests the BookRepository class for:
 * - CRUD operations
 * - Query methods
 * - ISBN validation
 * - Available copies management
 * 
 * @package Library\Tests\Repositories
 */
class BookRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PDO Database connection */
    private \PDO $pdo;
    
    /** @var BookRepository Repository instance */
    private BookRepository $repository;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->pdo = createTestDatabase();
        $this->repository = new BookRepository($this->pdo);
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
     * Test findById returns book when exists
     */
    public function testFindByIdReturnsBookWhenExists(): void
    {
        $book = $this->repository->findById(1);
        
        $this->assertIsArray($book);
        $this->assertEquals(1, $book['id']);
        $this->assertEquals('1984', $book['title']);
    }

    /**
     * Test findById returns null when not exists
     */
    public function testFindByIdReturnsNullWhenNotExists(): void
    {
        $book = $this->repository->findById(999);
        
        $this->assertNull($book);
    }

    /**
     * Test findByIsbn returns book when exists
     */
    public function testFindByIsbnReturnsBookWhenExists(): void
    {
        $book = $this->repository->findByIsbn('978-0451524935');
        
        $this->assertIsArray($book);
        $this->assertEquals('1984', $book['title']);
    }

    /**
     * Test findByIsbn returns null when not exists
     */
    public function testFindByIsbnReturnsNullWhenNotExists(): void
    {
        $book = $this->repository->findByIsbn('000-0000000000');
        
        $this->assertNull($book);
    }

    /**
     * Test findAll returns all books
     */
    public function testFindAllReturnsAllBooks(): void
    {
        $books = $this->repository->findAll();
        
        $this->assertIsArray($books);
        $this->assertCount(10, $books); // Seed data has 10 books
    }

    /**
     * Test findAll respects limit
     */
    public function testFindAllRespectsLimit(): void
    {
        $books = $this->repository->findAll(5);
        
        $this->assertCount(5, $books);
    }

    /**
     * Test findByAuthorId returns books by author
     */
    public function testFindByAuthorIdReturnsBooksByAuthor(): void
    {
        $books = $this->repository->findByAuthorId(1); // George Orwell
        
        $this->assertIsArray($books);
        $this->assertCount(2, $books); // 1984 and Animal Farm
    }

    /**
     * Test findByGenre returns books in genre
     */
    public function testFindByGenreReturnsBooksInGenre(): void
    {
        $books = $this->repository->findByGenre('Dystopian');
        
        $this->assertIsArray($books);
        $this->assertCount(1, $books);
        $this->assertEquals('1984', $books[0]['title']);
    }

    /**
     * Test create adds new book and returns ID
     */
    public function testCreateAddsNewBookAndReturnsId(): void
    {
        $id = $this->repository->create([
            'title' => 'New Book',
            'author_id' => 1,
            'isbn' => '978-0000000001',
            'genre' => 'Fiction',
            'published_year' => 2024,
            'available_copies' => 2,
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        // Verify book was created
        $book = $this->repository->findById($id);
        $this->assertEquals('New Book', $book['title']);
    }

    /**
     * Test update modifies existing book
     */
    public function testUpdateModifiesExistingBook(): void
    {
        $result = $this->repository->update(1, [
            'title' => 'Updated Title',
        ]);
        
        $this->assertTrue($result);
        
        $book = $this->repository->findById(1);
        $this->assertEquals('Updated Title', $book['title']);
    }

    /**
     * Test delete removes book
     */
    public function testDeleteRemovesBook(): void
    {
        $result = $this->repository->delete(1);
        
        $this->assertTrue($result);
        
        $book = $this->repository->findById(1);
        $this->assertNull($book);
    }

    /**
     * Test existsByIsbn returns true when exists
     */
    public function testExistsByIsbnReturnsTrueWhenExists(): void
    {
        $this->assertTrue($this->repository->existsByIsbn('978-0451524935'));
    }

    /**
     * Test existsByIsbn returns false when not exists
     */
    public function testExistsByIsbnReturnsFalseWhenNotExists(): void
    {
        $this->assertFalse($this->repository->existsByIsbn('000-0000000000'));
    }

    /**
     * Test getAvailableCopies returns correct count
     */
    public function testGetAvailableCopiesReturnsCorrectCount(): void
    {
        $copies = $this->repository->getAvailableCopies(1);
        
        $this->assertEquals(3, $copies); // Seed data: 1984 has 3 copies
    }

    /**
     * Test updateAvailableCopies increases count
     */
    public function testUpdateAvailableCopiesIncreasesCount(): void
    {
        $result = $this->repository->updateAvailableCopies(1, 2);
        
        $this->assertTrue($result);
        
        $copies = $this->repository->getAvailableCopies(1);
        $this->assertEquals(5, $copies); // 3 + 2 = 5
    }

    /**
     * Test updateAvailableCopies decreases count
     */
    public function testUpdateAvailableCopiesDecreasesCount(): void
    {
        $result = $this->repository->updateAvailableCopies(1, -1);
        
        $this->assertTrue($result);
        
        $copies = $this->repository->getAvailableCopies(1);
        $this->assertEquals(2, $copies); // 3 - 1 = 2
    }

    /**
     * Test updateAvailableCopies prevents negative count
     */
    public function testUpdateAvailableCopiesPreventsNegativeCount(): void
    {
        $result = $this->repository->updateAvailableCopies(1, -100);
        
        $this->assertTrue($result); // Query succeeds but no rows affected
        
        $copies = $this->repository->getAvailableCopies(1);
        $this->assertEquals(3, $copies); // Unchanged
    }
}
