<?php

declare(strict_types=1);

require_once __DIR__ . '/../interfaces/BookRepositoryInterface.php';

/**
 * SQLite Book Repository
 * 
 * Implements BookRepositoryInterface for SQLite database access.
 * Demonstrates Single Responsibility Principle (SRP) - only handles book
 * data persistence operations.
 * 
 * Demonstrates Dependency Inversion Principle (DIP) - implements an interface,
 * allowing services to depend on the abstraction rather than this concrete class.
 * 
 * @package Library\OOP\Repositories
 */
class BookRepository implements BookRepositoryInterface
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
        $stmt = $this->database->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByIsbn(string $isbn): ?array
    {
        $stmt = $this->database->prepare('SELECT * FROM books WHERE isbn = :isbn');
        $stmt->execute(['isbn' => $isbn]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM books ORDER BY title LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findByAuthorId(int $authorId): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM books WHERE author_id = :author_id ORDER BY title'
        );
        $stmt->execute(['author_id' => $authorId]);
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findByGenre(string $genre): array
    {
        $stmt = $this->database->prepare(
            'SELECT * FROM books WHERE genre = :genre ORDER BY title'
        );
        $stmt->execute(['genre' => $genre]);
        
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): int
    {
        $stmt = $this->database->prepare(
            'INSERT INTO books (title, author_id, isbn, genre, published_year, available_copies)
             VALUES (:title, :author_id, :isbn, :genre, :published_year, :available_copies)'
        );
        
        $stmt->execute([
            'title' => $data['title'],
            'author_id' => $data['author_id'],
            'isbn' => $data['isbn'],
            'genre' => $data['genre'],
            'published_year' => $data['published_year'] ?? null,
            'available_copies' => $data['available_copies'] ?? 1,
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
        
        $sql = 'UPDATE books SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->database->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $stmt = $this->database->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function existsByIsbn(string $isbn): bool
    {
        $stmt = $this->database->prepare(
            'SELECT COUNT(*) FROM books WHERE isbn = :isbn'
        );
        $stmt->execute(['isbn' => $isbn]);
        
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableCopies(int $id): int
    {
        $stmt = $this->database->prepare(
            'SELECT available_copies FROM books WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function updateAvailableCopies(int $id, int $change): bool
    {
        $stmt = $this->database->prepare(
            'UPDATE books SET available_copies = available_copies + :change
             WHERE id = :id AND available_copies + :change >= 0'
        );
        
        return $stmt->execute([
            'id' => $id,
            'change' => $change,
        ]);
    }
}
