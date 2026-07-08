<?php

declare(strict_types=1);

require_once __DIR__ . '/../interfaces/LoanRepositoryInterface.php';

/**
 * SQLite Loan Repository
 * 
 * Implements LoanRepositoryInterface for SQLite database access.
 * Demonstrates Single Responsibility Principle (SRP) - only handles loan
 * data persistence operations.
 * 
 * @package Library\OOP\Repositories
 */
class LoanRepository implements LoanRepositoryInterface
{
    /**
     * Constructor
     * 
     * @param \PDO $database The database connection
     */
    public function __construct(
        private \PDO $database
    ) {}

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->database->prepare('SELECT * FROM loans WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM loans ORDER BY loan_date DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findByBookId(int $bookId): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM loans WHERE book_id = :book_id ORDER BY loan_date DESC'
        );
        $stmt->execute(['book_id' => $bookId]);
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findByMemberId(int $memberId): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM loans WHERE member_id = :member_id ORDER BY loan_date DESC'
        );
        $stmt->execute(['member_id' => $memberId]);
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveByMemberId(int $memberId): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM loans WHERE member_id = :member_id AND status = \'active\'
             ORDER BY loan_date DESC'
        );
        $stmt->execute(['member_id' => $memberId]);
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findOverdue(): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM loans
             WHERE status = \'active\' AND due_date < date(\'now\')
             ORDER BY due_date ASC'
        );
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): int
    {
        $stmt = $this->database->prepare(
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
        
        return (int) $this->database->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $sql = 'UPDATE loans SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->database->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * {@inheritdoc}
     */
    public function isBookLoaned(int $bookId): bool
    {
        $stmt = $this->database->prepare(
            'SELECT COUNT(*) FROM loans WHERE book_id = :book_id AND status = \'active\''
        );
        $stmt->execute(['book_id' => $bookId]);
        
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoanCount(int $bookId, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM loans WHERE book_id = :book_id';
        $params = ['book_id' => $bookId];
        
        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
}
