<?php

declare(strict_types=1);

/**
 * Book Repository Interface
 * 
 * Defines the contract for book data access operations.
 * Demonstrates Interface Segregation Principle (ISP) - clients depend only
 * on the methods they use, rather than a monolithic repository.
 * 
 * Also supports Dependency Inversion Principle (DIP) - high-level modules
 * depend on this abstraction, not concrete implementations.
 * 
 * @package Library\OOP\Interfaces
 */
interface BookRepositoryInterface
{
    /**
     * Find a book by its ID
     * 
     * @param int $id The book ID
     * @return array|null Book data or null if not found
     */
    public function findById(int $id): ?array;

    /**
     * Find a book by ISBN
     * 
     * @param string $isbn The book ISBN
     * @return array|null Book data or null if not found
     */
    public function findByIsbn(string $isbn): ?array;

    /**
     * Get all books
     * 
     * @param int $limit Maximum number of results
     * @param int $offset Starting offset for pagination
     * @return array List of books
     */
    public function findAll(int $limit = 100, int $offset = 0): array;

    /**
     * Find books by author ID
     * 
     * @param int $authorId The author ID
     * @return array List of books by the author
     */
    public function findByAuthorId(int $authorId): array;

    /**
     * Find books by genre
     * 
     * @param string $genre The genre name
     * @return array List of books in the genre
     */
    public function findByGenre(string $genre): array;

    /**
     * Create a new book
     * 
     * @param array $data Book data (title, author_id, isbn, genre, published_year, available_copies)
     * @return int The new book ID
     */
    public function create(array $data): int;

    /**
     * Update an existing book
     * 
     * @param int $id The book ID
     * @param array $data Updated book data
     * @return bool True on success
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a book
     * 
     * @param int $id The book ID
     * @return bool True on success
     */
    public function delete(int $id): bool;

    /**
     * Check if a book exists by ISBN
     * 
     * @param string $isbn The book ISBN
     * @return bool True if exists
     */
    public function existsByIsbn(string $isbn): bool;

    /**
     * Get available copies count for a book
     * 
     * @param int $id The book ID
     * @return int Number of available copies
     */
    public function getAvailableCopies(int $id): int;

    /**
     * Update available copies count
     * 
     * @param int $id The book ID
     * @param int $change Change in copies (positive to add, negative to remove)
     * @return bool True on success
     */
    public function updateAvailableCopies(int $id, int $change): bool;
}
