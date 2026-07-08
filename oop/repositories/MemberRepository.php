<?php

declare(strict_types=1);

require_once __DIR__ . '/../interfaces/MemberRepositoryInterface.php';

/**
 * SQLite Member Repository
 * 
 * Implements MemberRepositoryInterface for SQLite database access.
 * Demonstrates Single Responsibility Principle (SRP) - only handles member
 * data persistence operations.
 * 
 * @package Library\OOP\Repositories
 */
class MemberRepository implements MemberRepositoryInterface
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
        $stmt = $this->database->prepare('SELECT * FROM members WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->database->prepare('SELECT * FROM members WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM members ORDER BY last_name, first_name LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findByType(string $type): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM members WHERE membership_type = :type ORDER BY last_name'
        );
        $stmt->execute(['type' => $type]);
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): int
    {
        $stmt = $this->database->prepare(
            'INSERT INTO members (first_name, last_name, email, phone, membership_type)
             VALUES (:first_name, :last_name, :email, :phone, :membership_type)'
        );
        
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'membership_type' => $data['membership_type'] ?? 'standard',
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
        
        $sql = 'UPDATE members SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->database->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $stmt = $this->database->prepare('DELETE FROM members WHERE id = :id');
        $stmt->execute(['id' => $id]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function existsByEmail(string $email): bool
    {
        $stmt = $this->database->prepare(
            'SELECT COUNT(*) FROM members WHERE email = :email'
        );
        $stmt->execute(['email' => $email]);
        
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoanCount(int $id, ?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM loans WHERE member_id = :id';
        $params = ['id' => $id];
        
        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
}
