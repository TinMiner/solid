<?php

declare(strict_types=1);

/**
 * Book Functions - Procedural Version
 * 
 * Functions for book data access operations.
 * Demonstrates procedural approach to Single Responsibility Principle (SRP):
 * Each function handles ONE specific task.
 * 
 * Demonstrates Interface Segregation Principle (ISP) equivalent:
 * Functions are small and focused, not monolithic.
 * 
 * @package Library\Procedural\Functions
 */

/**
 * Get database connection
 * 
 * @param string $dbPath Path to SQLite database
 * @return \PDO Database connection
 * @throws \RuntimeException If connection fails
 */
function getDatabaseConnection(string $dbPath): \PDO
{
    try {
        $pdo = new \PDO(
            dsn: "sqlite:{$dbPath}",
            options: [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
        
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        
        return $pdo;
    } catch (\PDOException $e) {
        throw new \RuntimeException("Database connection failed: {$e->getMessage()}");
    }
}

/**
 * Initialize database with schema and seed data
 * 
 * @param \PDO $pdo Database connection
 * @param string $schemaFile Path to schema SQL file
 * @param string|null $seedFile Path to seed SQL file
 * @return void
 * @throws \RuntimeException If SQL execution fails
 */
function initializeDatabase(\PDO $pdo, string $schemaFile, ?string $seedFile = null): void
{
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new \RuntimeException("Cannot read schema file: {$schemaFile}");
    }
    
    $pdo->exec($sql);
    
    if ($seedFile !== null && file_exists($seedFile)) {
        $seedSql = file_get_contents($seedFile);
        if ($seedSql !== false) {
            $pdo->exec($seedSql);
        }
    }
}

/**
 * Log an activity to the database
 * 
 * @param \PDO $pdo Database connection
 * @param string $action The action performed
 * @param string $entityType The entity type
 * @param int|null $entityId The entity ID
 * @param string|null $details Additional details
 * @return void
 */
function logActivity(
    \PDO $pdo,
    string $action,
    string $entityType,
    ?int $entityId = null,
    ?string $details = null
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO activity_log (action, entity_type, entity_id, details)
         VALUES (:action, :entity_type, :entity_id, :details)'
    );
    
    $stmt->execute([
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'details' => $details,
    ]);
}

/**
 * Find a book by ID
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The book ID
 * @return array|null Book data or null if not found
 */
function findBookById(\PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch();
    
    return $result ?: null;
}

/**
 * Find a book by ISBN
 * 
 * @param \PDO $pdo Database connection
 * @param string $isbn The book ISBN
 * @return array|null Book data or null if not found
 */
function findBookByIsbn(\PDO $pdo, string $isbn): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM books WHERE isbn = :isbn');
    $stmt->execute(['isbn' => $isbn]);
    $result = $stmt->fetch();
    
    return $result ?: null;
}

/**
 * Get all books
 * 
 * @param \PDO $pdo Database connection
 * @param int $limit Maximum number of results
 * @param int $offset Starting offset
 * @return array List of books
 */
function findAllBooks(\PDO $pdo, int $limit = 100, int $offset = 0): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM books ORDER BY title LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Find books by genre
 * 
 * @param \PDO $pdo Database connection
 * @param string $genre The genre name
 * @return array List of books in the genre
 */
function findBooksByGenre(\PDO $pdo, string $genre): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM books WHERE genre = :genre ORDER BY title'
    );
    $stmt->execute(['genre' => $genre]);
    
    return $stmt->fetchAll();
}

/**
 * Create a new book
 * 
 * @param \PDO $pdo Database connection
 * @param array $data Book data (title, author_id, isbn, genre, published_year, available_copies)
 * @return int The new book ID
 * @throws \InvalidArgumentException If required data is missing
 */
function createBook(\PDO $pdo, array $data): int
{
    $requiredFields = ['title', 'author_id', 'isbn', 'genre'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new \InvalidArgumentException("Required field missing: {$field}");
        }
    }
    
    // Check for duplicate ISBN
    if (bookExistsByIsbn($pdo, $data['isbn'])) {
        throw new \InvalidArgumentException("A book with ISBN {$data['isbn']} already exists");
    }
    
    $stmt = $pdo->prepare(
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
    
    return (int) $pdo->lastInsertId();
}

/**
 * Update a book
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The book ID
 * @param array $data Updated book data
 * @return bool True on success
 */
function updateBook(\PDO $pdo, int $id, array $data): bool
{
    $fields = [];
    $params = ['id' => $id];
    
    foreach ($data as $field => $value) {
        $fields[] = "{$field} = :{$field}";
        $params[$field] = $value;
    }
    
    $sql = 'UPDATE books SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute($params);
}

/**
 * Delete a book
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The book ID
 * @return bool True on success
 */
function deleteBook(\PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
    $stmt->execute(['id' => $id]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Check if a book exists by ISBN
 * 
 * @param \PDO $pdo Database connection
 * @param string $isbn The book ISBN
 * @return bool True if exists
 */
function bookExistsByIsbn(\PDO $pdo, string $isbn): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM books WHERE isbn = :isbn');
    $stmt->execute(['isbn' => $isbn]);
    
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Get available copies count for a book
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The book ID
 * @return int Number of available copies
 */
function getAvailableCopies(\PDO $pdo, int $id): int
{
    $stmt = $pdo->prepare('SELECT available_copies FROM books WHERE id = :id');
    $stmt->execute(['id' => $id]);
    
    return (int) $stmt->fetchColumn();
}

/**
 * Update available copies count
 * 
 * @param \PDO $pdo Database connection
 * @param int $id The book ID
 * @param int $change Change in copies (positive to add, negative to remove)
 * @return bool True on success
 */
function updateAvailableCopies(\PDO $pdo, int $id, int $change): bool
{
    $stmt = $pdo->prepare(
        'UPDATE books SET available_copies = available_copies + :change
         WHERE id = :id AND available_copies + :change >= 0'
    );
    
    return $stmt->execute([
        'id' => $id,
        'change' => $change,
    ]);
}

/**
 * Format a book for display
 * 
 * @param array $book The book data
 * @return string Formatted book string
 */
function formatBook(array $book): string
{
    $availability = ($book['available_copies'] ?? 0) > 0 ? '✓ Available' : '✗ Unavailable';
    return "[{$book['id']}] '{$book['title']}' ({$book['genre']}) - {$availability}";
}
