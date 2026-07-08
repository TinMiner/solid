<?php

declare(strict_types=1);

/**
 * Loan Functions - Procedural Version
 * 
 * Functions for loan data access and business logic.
 * Demonstrates procedural approach to SOLID principles:
 * 
 * - Single Responsibility: Each function handles ONE loan operation
 * - Open/Closed: Functions accept callbacks for extensibility
 * - Dependency Inversion: Functions accept dependencies as parameters
 * 
 * @package Library\Procedural\Functions
 */

/**
 * Find a loan by ID
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The loan ID
 * @return array|null Loan data or null if not found
 */
function findLoanById(\PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM loans WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    
    return $result ?: null;
}

/**
 * Get all loans
 * 
 * @param \PDO $pdo Database connection
 * @param int $limit Maximum number of results
 * @param int $offset Starting offset
 * @return array List of loans
 */
function findAllLoans(\PDO $pdo, int $limit = 100, int $offset = 0): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM loans ORDER BY loan_date DESC LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Find loans by book ID
 * 
 * @param \PDO $pdo Database connection
 * @param int $bookId The book ID
 * @return array List of loans for the book
 */
function findLoansByBookId(\PDO $pdo, int $bookId): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM loans WHERE book_id = :book_id ORDER BY loan_date DESC'
    );
    $stmt->execute(['book_id' => $bookId]);
    
    return $stmt->fetchAll();
}

/**
 * Find loans by member ID
 * 
 * @param \PDO $pdo Database connection
 * @param int $memberId The member ID
 * @return array List of loans for the member
 */
function findLoansByMemberId(\PDO $pdo, int $memberId): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM loans WHERE member_id = :member_id ORDER BY loan_date DESC'
    );
    $stmt->execute(['member_id' => $memberId]);
    
    return $stmt->fetchAll();
}

/**
 * Find active loans for a member
 * 
 * @param \PDO $pdo Database connection
 * @param int $memberId The member ID
 * @return array List of active loans
 */
function findActiveLoansByMemberId(\PDO $pdo, int $memberId): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM loans WHERE member_id = :member_id AND status = \'active\'
         ORDER BY loan_date DESC'
    );
    $stmt->execute(['member_id' => $memberId]);
    
    return $stmt->fetchAll();
}

/**
 * Find overdue loans
 * 
 * @param \PDO $pdo Database connection
 * @return array List of overdue loans
 */
function findOverdueLoans(\PDO $pdo): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM loans
         WHERE status = \'active\' AND due_date < date(\'now\')
         ORDER BY due_date ASC'
    );
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Create a new loan
 * 
 * @param \PDO $pdo Database connection
 * @param array $data Loan data (book_id, member_id, loan_date, due_date, status)
 * @return int The new loan ID
 * @throws \InvalidArgumentException If required data is missing
 */
function createLoan(\PDO $pdo, array $data): int
{
    $requiredFields = ['book_id', 'member_id', 'loan_date', 'due_date'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new \InvalidArgumentException("Required field missing: {$field}");
        }
    }
    
    $stmt = $pdo->prepare(
        'INSERT INTO loans (book_id, member_id, loan_date, due_date, status)
         VALUES (:book_id, :member_id, :loan_date, :due_date, :status)'
    );
    
    $stmt->execute([
        'book_id' => $data['book_id'],
        'member_id' => $data['member_id'],
        'loan_date' => $data['loan_date'],
        'due_date' => $data['due_date'],
        'status' => $data['status'] ?? 'active',
    ]);
    
    return (int) $pdo->lastInsertId();
}

/**
 * Update a loan
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The loan ID
 * @param array $data Updated loan data
 * @return bool True on success
 */
function updateLoan(\PDO $pdo, int $id, array $data): bool
{
    $fields = [];
    $params = ['id' => $id];
    
    foreach ($data as $field => $value) {
        $fields[] = "{$field} = :{$field}";
        $params[$field] = $value;
    }
    
    $sql = 'UPDATE loans SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute($params);
}

/**
 * Check if a book is currently loaned out
 * 
 * @param \PDO $pdo Database connection
 * @param int $bookId The book ID
 * @return bool True if currently on loan
 */
function isBookLoaned(\PDO $pdo, int $bookId): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM loans WHERE book_id = :book_id AND status = \'active\''
    );
    $stmt->execute(['book_id' => $bookId]);
    
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Get loan count for a book
 * 
 * @param \PDO $pdo Database connection
 * @param int $bookId The book ID
 * @param string|null $status Optional status filter
 * @return int Number of loans
 */
function getLoanCount(\PDO $pdo, int $bookId, ?string $status = null): int
{
    $sql = 'SELECT COUNT(*) FROM loans WHERE book_id = :book_id';
    $params = ['book_id' => $bookId];
    
    if ($status !== null) {
        $sql .= ' AND status = :status';
        $params['status'] = $status;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (int) $stmt->fetchColumn();
}

/**
 * Check if a loan is overdue
 * 
 * @param array $loan The loan data
 * @return bool True if overdue
 */
function isLoanOverdue(array $loan): bool
{
    if (($loan['status'] ?? '') !== 'active') {
        return false;
    }
    
    $dueDate = new \DateTime($loan['due_date']);
    $now = new \DateTime();
    
    return $dueDate < $now;
}

/**
 * Get number of days a loan is overdue
 * 
 * @param array $loan The loan data
 * @return int Days overdue (0 if not overdue)
 */
function getDaysOverdue(array $loan): int
{
    if (!isLoanOverdue($loan)) {
        return 0;
    }
    
    $dueDate = new \DateTime($loan['due_date']);
    $now = new \DateTime();
    
    return (int) $now->diff($dueDate)->days;
}

/**
 * Process a book checkout
 * 
 * This function demonstrates Dependency Inversion Principle (DIP) in procedural code:
 * It accepts callback functions for notification, making it extensible.
 * 
 * @param \PDO $pdo Database connection
 * @param int $bookId The book ID to checkout
 * @param int $memberId The member ID
 * @param int $loanDurationDays Loan duration in days
 * @param callable|null $onSuccess Callback for successful checkout
 * @param callable|null $onError Callback for errors
 * @return array The created loan data
 * @throws \InvalidArgumentException If book or member not found
 * @throws \LogicException If business rules are violated
 */
function processCheckout(
    \PDO $pdo,
    int $bookId,
    int $memberId,
    int $loanDurationDays = 14,
    ?callable $onSuccess = null,
    ?callable $onError = null
): array {
    try {
        // Verify book exists and is available
        $book = findBookById($pdo, $bookId);
        if ($book === null) {
            throw new \InvalidArgumentException("Book not found: {$bookId}");
        }
        
        if (($book['available_copies'] ?? 0) <= 0) {
            throw new \LogicException("Book '{$book['title']}' is not available for loan");
        }
        
        // Verify member exists
        $member = findMemberById($pdo, $memberId);
        if ($member === null) {
            throw new \InvalidArgumentException("Member not found: {$memberId}");
        }
        
        // Check member's loan limit
        if (!memberCanBorrow($pdo, $memberId)) {
            $limit = getLoanLimit($member['membership_type']);
            throw new \LogicException(
                "Member '{$member['first_name']} {$member['last_name']}' has reached their loan limit " .
                "of {$limit} books"
            );
        }
        
        // Create the loan
        $loanDate = (new \DateTime())->format('Y-m-d');
        $dueDate = (new \DateTime("+{$loanDurationDays} days"))->format('Y-m-d');
        
        $loanId = createLoan($pdo, [
            'book_id' => $bookId,
            'member_id' => $memberId,
            'loan_date' => $loanDate,
            'due_date' => $dueDate,
            'status' => 'active',
        ]);
        
        // Decrease available copies
        updateAvailableCopies($pdo, $bookId, -1);
        
        // Log the activity
        logActivity(
            $pdo,
            'loan',
            'loan',
            $loanId,
            json_encode([
                'book_id' => $bookId,
                'member_id' => $memberId,
                'due_date' => $dueDate,
            ])
        );
        
        $loan = findLoanById($pdo, $loanId);
        
        // Call success callback if provided (Open/Closed Principle)
        if ($onSuccess !== null) {
            $onSuccess($loan, $book, $member);
        }
        
        return $loan;
    } catch (\Exception $e) {
        // Call error callback if provided (Open/Closed Principle)
        if ($onError !== null) {
            $onError($e);
        }
        throw $e;
    }
}

/**
 * Process a book return
 * 
 * @param \PDO $pdo Database connection
 * @param int $loanId The loan ID
 * @param callable|null $onSuccess Callback for successful return
 * @return array|null Updated loan data or null if not found
 * @throws \LogicException If loan is not active
 */
function processReturn(
    \PDO $pdo,
    int $loanId,
    ?callable $onSuccess = null
): ?array {
    $loan = findLoanById($pdo, $loanId);
    
    if ($loan === null) {
        return null;
    }
    
    if ($loan['status'] !== 'active') {
        throw new \LogicException(
            "Cannot return loan #{$loanId} - status is '{$loan['status']}'"
        );
    }
    
    // Mark as returned
    updateLoan($pdo, $loanId, [
        'return_date' => (new \DateTime())->format('Y-m-d'),
        'status' => 'returned',
    ]);
    
    // Increase available copies
    updateAvailableCopies($pdo, $loan['book_id'], 1);
    
    // Log the activity
    logActivity(
        $pdo,
        'return',
        'loan',
        $loanId,
        json_encode([
            'book_id' => $loan['book_id'],
            'member_id' => $loan['member_id'],
        ])
    );
    
    $updatedLoan = findLoanById($pdo, $loanId);
    
    // Call success callback if provided (Open/Closed Principle)
    if ($onSuccess !== null) {
        $book = findBookById($pdo, $loan['book_id']);
        $member = findMemberById($pdo, $loan['member_id']);
        $onSuccess($updatedLoan, $book, $member);
    }
    
    return $updatedLoan;
}

/**
 * Process overdue loans
 * 
 * This function demonstrates the Open/Closed Principle through callbacks:
 * You can extend functionality by providing different callbacks.
 * 
 * @param \PDO $pdo Database connection
 * @param callable|null $onOverdue Callback for each overdue loan
 * @return array List of overdue loans
 */
function processOverdueLoans(\PDO $pdo, ?callable $onOverdue = null): array
{
    $overdueLoans = findOverdueLoans($pdo);
    $processed = [];
    
    foreach ($overdueLoans as $loan) {
        // Mark as overdue
        updateLoan($pdo, $loan['id'], ['status' => 'overdue']);
        
        $processed[] = $loan;
        
        // Call callback if provided
        if ($onOverdue !== null) {
            $book = findBookById($pdo, $loan['book_id']);
            $member = findMemberById($pdo, $loan['member_id']);
            $onOverdue($loan, $book, $member);
        }
    }
    
    return $processed;
}

/**
 * Get loan statistics
 * 
 * @param \PDO $pdo Database connection
 * @return array Loan statistics
 */
function getLoanStatistics(\PDO $pdo): array
{
    $allLoans = findAllLoans($pdo, 10000);
    
    $stats = [
        'total' => count($allLoans),
        'active' => 0,
        'returned' => 0,
        'overdue' => 0,
    ];
    
    foreach ($allLoans as $loan) {
        $status = $loan['status'] ?? 'unknown';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }
    
    return $stats;
}

/**
 * Format a loan for display
 * 
 * @param array $loan The loan data
 * @param array|null $book Optional book data for display
 * @param array|null $member Optional member data for display
 * @return string Formatted loan string
 */
function formatLoan(array $loan, ?array $book = null, ?array $member = null): string
{
    $bookTitle = $book['title'] ?? "Book #{$loan['book_id']}";
    $memberName = $member
        ? "{$member['first_name']} {$member['last_name']}"
        : "Member #{$loan['member_id']}";
    
    $statusIcon = match ($loan['status']) {
        'active' => '✓',
        'returned' => '←',
        'overdue' => '✗',
        default => '?',
    };
    
    return "{$statusIcon} [{$loan['id']}] '{$bookTitle}' → {$memberName} (Due: {$loan['due_date']})";
}
