<?php

declare(strict_types=1);

/**
 * Member Repository Interface
 * 
 * Defines the contract for member data access operations.
 * Demonstrates Interface Segregation Principle (ISP) - focused on member-specific operations.
 * 
 * @package Library\OOP\Interfaces
 */
interface MemberRepositoryInterface
{
    /**
     * Find a member by their ID
     * 
     * @param int $id The member ID
     * @return array|null Member data or null if not found
     */
    public function findById(int $id): ?array;

    /**
     * Find a member by email
     * 
     * @param string $email The member email
     * @return array|null Member data or null if not found
     */
    public function findByEmail(string $email): ?array;

    /**
     * Get all members
     * 
     * @param int $limit Maximum number of results
     * @param int $offset Starting offset for pagination
     * @return array List of members
     */
    public function findAll(int $limit = 100, int $offset = 0): array;

    /**
     * Find members by membership type
     * 
     * @param string $type The membership type (standard, premium, student)
     * @return array List of members with the specified type
     */
    public function findByType(string $type): array;

    /**
     * Create a new member
     * 
     * @param array $data Member data (first_name, last_name, email, phone, membership_type)
     * @return int The new member ID
     */
    public function create(array $data): int;

    /**
     * Update an existing member
     * 
     * @param int $id The member ID
     * @param array $data Updated member data
     * @return bool True on success
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a member
     * 
     * @param int $id The member ID
     * @return bool True on success
     */
    public function delete(int $id): bool;

    /**
     * Check if a member exists by email
     * 
     * @param string $email The member email
     * @return bool True if exists
     */
    public function existsByEmail(string $email): bool;

    /**
     * Get member's loan count
     * 
     * @param int $id The member ID
     * @param string $status Optional loan status filter
     * @return int Number of loans
     */
    public function getLoanCount(int $id, ?string $status = null): int;
}
