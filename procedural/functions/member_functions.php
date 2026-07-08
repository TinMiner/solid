<?php

declare(strict_types=1);

/**
 * Member Functions - Procedural Version
 * 
 * Functions for member data access operations.
 * Demonstrates procedural approach to Single Responsibility Principle (SRP):
 * Each function handles ONE specific task related to member management.
 * 
 * @package Library\Procedural\Functions
 */

/**
 * Find a member by ID
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The member ID
 * @return array|null Member data or null if not found
 */
function findMemberById(\PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    
    return $result ?: null;
}

/**
 * Find a member by email
 * 
 * @param \PDO $pdo Database connection
 * @param string $email The member email
 * @return array|null Member data or null if not found
 */
function findMemberByEmail(\PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM members WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $result = $stmt->fetch();
    
    return $result ?: null;
}

/**
 * Get all members
 * 
 * @param \PDO $pdo Database connection
 * @param int $limit Maximum number of results
 * @param int $offset Starting offset
 * @return array List of members
 */
function findAllMembers(\PDO $pdo, int $limit = 100, int $offset = 0): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM members ORDER BY last_name, first_name LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Find members by membership type
 * 
 * @param \PDO $pdo Database connection
 * @param string $type The membership type
 * @return array List of members with the specified type
 */
function findMembersByType(\PDO $pdo, string $type): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM members WHERE membership_type = :type ORDER BY last_name'
    );
    $stmt->execute(['type' => $type]);
    
    return $stmt->fetchAll();
}

/**
 * Create a new member
 * 
 * @param \PDO $pdo Database connection
 * @param array $data Member data (first_name, last_name, email, phone, membership_type)
 * @return int The new member ID
 * @throws \InvalidArgumentException If required data is missing or email exists
 */
function createMember(\PDO $pdo, array $data): int
{
    $requiredFields = ['first_name', 'last_name', 'email'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new \InvalidArgumentException("Required field missing: {$field}");
        }
    }
    
    // Validate membership type
    $validTypes = ['standard', 'premium', 'student'];
    $membershipType = $data['membership_type'] ?? 'standard';
    if (!in_array($membershipType, $validTypes, true)) {
        throw new \InvalidArgumentException(
            "Invalid membership type: {$membershipType}. " .
            "Valid types: " . implode(', ', $validTypes)
        );
    }
    
    // Check for duplicate email
    if (memberExistsByEmail($pdo, $data['email'])) {
        throw new \InvalidArgumentException("A member with email {$data['email']} already exists");
    }
    
    $stmt = $pdo->prepare(
        'INSERT INTO members (first_name, last_name, email, phone, membership_type)
         VALUES (:first_name, :last_name, :email, :phone, :membership_type)'
    );
    
    $stmt->execute([
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'email' => $data['email'],
        'phone' => $data['phone'] ?? null,
        'membership_type' => $membershipType,
    ]);
    
    return (int) $pdo->lastInsertId();
}

/**
 * Update a member
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The member ID
 * @param array $data Updated member data
 * @return bool True on success
 */
function updateMember(\PDO $pdo, int $id, array $data): bool
{
    $fields = [];
    $params = ['id' => $id];
    
    foreach ($data as $field => $value) {
        $fields[] = "{$field} = :{$field}";
        $params[$field] = $value;
    }
    
    $sql = 'UPDATE members SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute($params);
}

/**
 * Delete a member
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The member ID
 * @return bool True on success
 */
function deleteMember(\PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM members WHERE id = :id');
    $stmt->execute(['id' => $id]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Check if a member exists by email
 * 
 * @param \PDO $pdo Database connection
 * @param string $email The member email
 * @return bool True if exists
 */
function memberExistsByEmail(\PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM members WHERE email = :email');
    $stmt->execute(['email' => $email]);
    
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Get member's loan count
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The member ID
 * @param string|null $status Optional loan status filter
 * @return int Number of loans
 */
function getMemberLoanCount(\PDO $pdo, int $id, ?string $status = null): int
{
    $sql = 'SELECT COUNT(*) FROM loans WHERE member_id = :id';
    $params = ['id' => $id];
    
    if ($status !== null) {
        $sql .= ' AND status = :status';
        $params['status'] = $status;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (int) $stmt->fetchColumn();
}

/**
 * Get loan limit for a membership type
 * 
 * @param string $membershipType The membership type
 * @return int Maximum loans allowed
 */
function getLoanLimit(string $membershipType): int
{
    $limits = [
        'standard' => 3,
        'premium' => 10,
        'student' => 5,
    ];
    
    return $limits[$membershipType] ?? 3;
}

/**
 * Check if a member can borrow more books
 * 
 * @param \PDO $pdo Database connection
 * @param int $memberId The member ID
 * @return bool True if can borrow
 */
function memberCanBorrow(\PDO $pdo, int $memberId): bool
{
    $member = findMemberById($pdo, $memberId);
    
    if ($member === null) {
        return false;
    }
    
    $currentLoans = getMemberLoanCount($pdo, $memberId, 'active');
    $limit = getLoanLimit($member['membership_type']);
    
    return $currentLoans < $limit;
}

/**
 * Format a member for display
 * 
 * @param array $member The member data
 * @return string Formatted member string
 */
function formatMember(array $member): string
{
    $fullName = "{$member['first_name']} {$member['last_name']}";
    return "[{$member['id']}] {$fullName} ({$member['membership_type']}) - {$member['email']}";
}
