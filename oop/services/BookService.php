<?php

declare(strict_types=1);

require_once __DIR__ . '/../interfaces/LoggerInterface.php';
require_once __DIR__ . '/../repositories/BookRepository.php';
require_once __DIR__ . '/../models/Book.php';

/**
 * Book Service
 * 
 * Handles book-related business logic and operations.
 * Demonstrates Single Responsibility Principle (SRP) - only handles book
 * business logic, delegating persistence to BookRepository.
 * 
 * Demonstrates Dependency Inversion Principle (DIP) - depends on interfaces
 * (BookRepositoryInterface, LoggerInterface) rather than concrete implementations.
 * 
 * Demonstrates Open/Closed Principle (OCP) - can be extended through new
 * notification handlers without modifying existing code.
 * 
 * @package Library\OOP\Services
 */
class BookService
{
    /**
     * Constructor
     * 
     * @param BookRepositoryInterface $bookRepository Book data access
     * @param LoggerInterface $logger Logging service
     */
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Get a book by ID
     * 
     * @param int $id The book ID
     * @return Book|null Book instance or null if not found
     */
    public function getBook(int $id): ?Book
    {
        $data = $this->bookRepository->findById($id);
        
        if ($data === null) {
            $this->logger->debug("Book not found", ['id' => $id]);
            return null;
        }
        
        return Book::fromArray($data);
    }

    /**
     * Get all books
     * 
     * @param int $limit Maximum number of results
     * @param int $offset Starting offset
     * @return Book[] Array of Book instances
     */
    public function getAllBooks(int $limit = 100, int $offset = 0): array
    {
        $data = $this->bookRepository->findAll($limit, $offset);
        
        return array_map(
            fn(array $item) => Book::fromArray($item),
            $data
        );
    }

    /**
     * Find books by genre
     * 
     * @param string $genre The genre to search for
     * @return Book[] Array of Book instances
     */
    public function getBooksByGenre(string $genre): array
    {
        $data = $this->bookRepository->findByGenre($genre);
        
        return array_map(
            fn(array $item) => Book::fromArray($item),
            $data
        );
    }

    /**
     * Find books by author
     * 
     * @param int $authorId The author ID
     * @return Book[] Array of Book instances
     */
    public function getBooksByAuthor(int $authorId): array
    {
        $data = $this->bookRepository->findByAuthorId($authorId);
        
        return array_map(
            fn(array $item) => Book::fromArray($item),
            $data
        );
    }

    /**
     * Add a new book
     * 
     * @param array $data Book data
     * @return Book The newly created book
     * @throws \InvalidArgumentException If required data is missing
     * @throws \RuntimeException If book creation fails
     */
    public function addBook(array $data): Book
    {
        // Validate required fields
        $requiredFields = ['title', 'author_id', 'isbn', 'genre'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Required field missing: {$field}");
            }
        }
        
        // Check for duplicate ISBN
        if ($this->bookRepository->existsByIsbn($data['isbn'])) {
            throw new \InvalidArgumentException(
                "A book with ISBN {$data['isbn']} already exists"
            );
        }
        
        // Create the book
        $id = $this->bookRepository->create($data);
        
        $this->logger->info("Book created", [
            'id' => $id,
            'title' => $data['title'],
            'isbn' => $data['isbn'],
        ]);
        
        $bookData = $this->bookRepository->findById($id);
        if ($bookData === null) {
            throw new \RuntimeException("Failed to retrieve created book");
        }
        
        return Book::fromArray($bookData);
    }

    /**
     * Update an existing book
     * 
     * @param int $id The book ID
     * @param array $data Updated data
     * @return Book|null Updated book or null if not found
     */
    public function updateBook(int $id, array $data): ?Book
    {
        $existing = $this->bookRepository->findById($id);
        
        if ($existing === null) {
            $this->logger->warning("Attempted to update non-existent book", ['id' => $id]);
            return null;
        }
        
        $this->bookRepository->update($id, $data);
        
        $this->logger->info("Book updated", ['id' => $id]);
        
        $updatedData = $this->bookRepository->findById($id);
        
        return Book::fromArray($updatedData);
    }

    /**
     * Delete a book
     * 
     * @param int $id The book ID
     * @return bool True on success
     */
    public function deleteBook(int $id): bool
    {
        $book = $this->bookRepository->findById($id);
        
        if ($book === null) {
            return false;
        }
        
        $result = $this->bookRepository->delete($id);
        
        if ($result) {
            $this->logger->info("Book deleted", [
                'id' => $id,
                'title' => $book['title'],
            ]);
        }
        
        return $result;
    }

    /**
     * Search books by title
     * 
     * @param string $searchTerm The search term
     * @return Book[] Array of matching books
     */
    public function searchBooks(string $searchTerm): array
    {
        $allBooks = $this->bookRepository->findAll(1000);
        $results = [];
        
        foreach ($allBooks as $data) {
            if (stripos($data['title'], $searchTerm) !== false) {
                $results[] = Book::fromArray($data);
            }
        }
        
        return $results;
    }

    /**
     * Get book availability summary
     * 
     * @param int $id The book ID
     * @return array|null Book availability info or null if not found
     */
    public function getBookAvailability(int $id): ?array
    {
        $book = $this->bookRepository->findById($id);
        
        if ($book === null) {
            return null;
        }
        
        return [
            'book_id' => $id,
            'title' => $book['title'],
            'available_copies' => $book['available_copies'],
            'is_available' => $book['available_copies'] > 0,
        ];
    }
}
