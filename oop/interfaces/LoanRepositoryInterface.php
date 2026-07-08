<?php

declare(strict_types=1);

/**
 * Loan Repository Interface
 * 
 * Defines the contract for loan data access operations.
 * Demonstrates Interface Segregation Principle (ISP) - focused on loan-specific operations.
 * 
 * @package Library\OOP\Interfaces
 */
interface LoanRepositoryInterface
{
    /**
     * Find a loan by its ID
     * 
     * @param int $id The loan ID
     * @return array|null Loan data or null if not found
     */
    public function findById(int $id): ?array;

    /**
     * Get all loans
     * 
     * @param int $limit Maximum number of results
     * @param int $offset Starting offset for pagination
     * @return array List of loans
     */
    public function findAll(int $limit = 100, int $offset = 0): array;

    /**
     * Find loans by book ID
     * 
     * @param int $bookId The book ID
     * @return array List of loans for the book
     */
    public function findByBookId(int $bookId): array;

    /**
     * Find loans by member ID
     * 
     * @param int $memberId The member ID
     * @return array List of loans for the member
     */
    public function findByMemberId(int $memberId): array;

    /**
     * Find active loans for a member
     * 
     * @param int $memberId The member ID
     * @return array List of active loans
     */
    public function findActiveByMemberId(int $memberId): array;

    /**
     * Find overdue loans
     * 
     * @return array List of overdue loans
     */
    public function findOverdue(): array;

    /**
     * Create a new loan
     * 
     * @param array $data Loan data (book_id, member_id, loan_date, due_date)
     * @return int The new loan ID
     */
    public function create(array $data): int;

    /**
     * Update a loan (e.g., mark as returned)
     * 
     * @param int $id The loan ID
     * @param array $data Updated loan data
     * @return bool True on success
     */
    public function update(int $id, array $data): bool;

    /**
     * Check if a book is currently loaned out
     * 
     * @param int $bookId The book ID
     * @return bool True if currently on loan
     */
    public function isBookLoaned(int $bookId): bool;

    /**
     * Get loan count for a book
     * 
     * @param int $bookId The book ID
     * @param string $status Optional status filter
     * @return int Number of loans
     */
    public function getLoanCount(int $bookId, ?string $status = null): int;
}
