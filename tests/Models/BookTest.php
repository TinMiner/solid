<?php

declare(strict_types=1);

/**
 * Book Model Test Suite
 * 
 * Tests the Book model class for:
 * - Property access and mutators
 * - Array conversion
 * - Static factory method
 * - Business logic methods
 * 
 * @package Library\Tests\Models
 */
class BookTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Book instantiation with valid data
     */
    public function testBookCanBeCreatedWithValidData(): void
    {
        $book = new Book(
            id: 1,
            title: 'Test Book',
            authorId: 1,
            isbn: '978-0000000001',
            genre: 'Fiction',
            publishedYear: 2024,
            availableCopies: 3
        );

        $this->assertEquals(1, $book->getId());
        $this->assertEquals('Test Book', $book->getTitle());
        $this->assertEquals(1, $book->getAuthorId());
        $this->assertEquals('978-0000000001', $book->getIsbn());
        $this->assertEquals('Fiction', $book->getGenre());
        $this->assertEquals(2024, $book->getPublishedYear());
        $this->assertEquals(3, $book->getAvailableCopies());
    }

    /**
     * Test Book isAvailable when copies exist
     */
    public function testBookIsAvailableWhenCopiesExist(): void
    {
        $book = new Book(1, 'Test', 1, '123', 'Genre', null, 2);
        
        $this->assertTrue($book->isAvailable());
    }

    /**
     * Test Book isAvailable when no copies exist
     */
    public function testBookIsNotAvailableWhenNoCopies(): void
    {
        $book = new Book(1, 'Test', 1, '123', 'Genre', null, 0);
        
        $this->assertFalse($book->isAvailable());
    }

    /**
     * Test Book decrementCopies reduces available count
     */
    public function testDecrementCopiesReducesAvailableCount(): void
    {
        $book = new Book(1, 'Test', 1, '123', 'Genre', null, 3);
        
        $book->decrementCopies();
        
        $this->assertEquals(2, $book->getAvailableCopies());
    }

    /**
     * Test Book decrementCopies throws exception when no copies
     */
    public function testDecrementCopiesThrowsExceptionWhenNoCopies(): void
    {
        $this->expectException(\LogicException::class);
        
        $book = new Book(1, 'Test', 1, '123', 'Genre', null, 0);
        $book->decrementCopies();
    }

    /**
     * Test Book incrementCopies increases available count
     */
    public function testIncrementCopiesIncreasesAvailableCount(): void
    {
        $book = new Book(1, 'Test', 1, '123', 'Genre', null, 1);
        
        $book->incrementCopies();
        
        $this->assertEquals(2, $book->getAvailableCopies());
    }

    /**
     * Test Book toArray returns correct structure
     */
    public function testToArrayReturnsCorrectStructure(): void
    {
        $book = new Book(1, 'Test Book', 1, '123', 'Fiction', 2024, 2, '2024-01-01');
        
        $array = $book->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Test Book', $array['title']);
        $this->assertEquals(1, $array['author_id']);
        $this->assertEquals('123', $array['isbn']);
        $this->assertEquals('Fiction', $array['genre']);
        $this->assertEquals(2024, $array['published_year']);
        $this->assertEquals(2, $array['available_copies']);
        $this->assertEquals('2024-01-01', $array['created_at']);
    }

    /**
     * Test Book fromArray creates valid instance
     */
    public function testFromArrayCreatesValidInstance(): void
    {
        $data = [
            'id' => 5,
            'title' => 'From Array Book',
            'author_id' => 2,
            'isbn' => '978-1111111111',
            'genre' => 'Mystery',
            'published_year' => 2020,
            'available_copies' => 4,
            'created_at' => '2024-01-01',
        ];
        
        $book = Book::fromArray($data);
        
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals(5, $book->getId());
        $this->assertEquals('From Array Book', $book->getTitle());
        $this->assertEquals(2, $book->getAuthorId());
        $this->assertEquals('978-1111111111', $book->getIsbn());
        $this->assertEquals('Mystery', $book->getGenre());
        $this->assertEquals(2020, $book->getPublishedYear());
        $this->assertEquals(4, $book->getAvailableCopies());
    }

    /**
     * Test Book setTitle updates title
     */
    public function testSetTitleUpdatesTitle(): void
    {
        $book = new Book(1, 'Original Title', 1, '123', 'Genre');
        
        $result = $book->setTitle('New Title');
        
        $this->assertEquals('New Title', $book->getTitle());
        $this->assertSame($book, $result); // Returns self for chaining
    }

    /**
     * Test Book handles null published year
     */
    public function testBookHandlesNullPublishedYear(): void
    {
        $book = new Book(1, 'Test', 1, '123', 'Genre', null);
        
        $this->assertNull($book->getPublishedYear());
    }
}
