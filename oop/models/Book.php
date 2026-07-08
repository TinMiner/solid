<?php

declare(strict_types=1);

/**
 * Book Model
 * 
 * Represents a book entity in the library system.
 * Demonstrates Single Responsibility Principle (SRP) - this class is only
 * responsible for book data representation and validation.
 * 
 * @package Library\OOP\Models
 */
class Book
{
    /**
     * Constructor
     * 
     * @param int $id The book ID
     * @param string $title The book title
     * @param int $authorId The author ID
     * @param string $isbn The book ISBN
     * @param string $genre The book genre
     * @param int|null $publishedYear The publication year
     * @param int $availableCopies Number of available copies
     * @param string $createdAt Creation timestamp
     */
    public function __construct(
        private int $id,
        private string $title,
        private int $authorId,
        private string $isbn,
        private string $genre,
        private ?int $publishedYear = null,
        private int $availableCopies = 1,
        private string $createdAt = ''
    ) {}

    /**
     * Get the book ID
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the book title
     * 
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the book title
     * 
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the author ID
     * 
     * @return int
     */
    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    /**
     * Get the ISBN
     * 
     * @return string
     */
    public function getIsbn(): string
    {
        return $this->isbn;
    }

    /**
     * Get the genre
     * 
     * @return string
     */
    public function getGenre(): string
    {
        return $this->genre;
    }

    /**
     * Get the publication year
     * 
     * @return int|null
     */
    public function getPublishedYear(): ?int
    {
        return $this->publishedYear;
    }

    /**
     * Get available copies count
     * 
     * @return int
     */
    public function getAvailableCopies(): int
    {
        return $this->availableCopies;
    }

    /**
     * Check if book is available for loan
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->availableCopies > 0;
    }

    /**
     * Decrement available copies
     * 
     * @return self
     * @throws \LogicException If no copies available
     */
    public function decrementCopies(): self
    {
        if ($this->availableCopies <= 0) {
            throw new \LogicException("No copies available for book: {$this->title}");
        }
        $this->availableCopies--;
        return $this;
    }

    /**
     * Increment available copies
     * 
     * @return self
     */
    public function incrementCopies(): self
    {
        $this->availableCopies++;
        return $this;
    }

    /**
     * Get creation timestamp
     * 
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author_id' => $this->authorId,
            'isbn' => $this->isbn,
            'genre' => $this->genre,
            'published_year' => $this->publishedYear,
            'available_copies' => $this->availableCopies,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Create a Book instance from an array
     * 
     * @param array $data The book data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            title: $data['title'] ?? '',
            authorId: (int) ($data['author_id'] ?? 0),
            isbn: $data['isbn'] ?? '',
            genre: $data['genre'] ?? '',
            publishedYear: isset($data['published_year']) ? (int) $data['published_year'] : null,
            availableCopies: (int) ($data['available_copies'] ?? 1),
            createdAt: $data['created_at'] ?? ''
        );
    }
}
