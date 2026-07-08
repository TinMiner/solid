<?php

declare(strict_types=1);

/**
 * Loan Model
 * 
 * Represents a book loan entity.
 * Demonstrates Single Responsibility Principle (SRP) - only handles loan
 * data representation and loan-specific business logic.
 * 
 * @package Library\OOP\Models
 */
class Loan
{
    /** @var array Valid loan statuses */
    private const VALID_STATUSES = ['active', 'returned', 'overdue'];

    /**
     * Constructor
     * 
     * @param int $id The loan ID
     * @param int $bookId The book ID
     * @param int $memberId The member ID
     * @param string $loanDate Loan start date
     * @param string $dueDate Due date for return
     * @param string|null $returnDate Actual return date
     * @param string $status Loan status
     * @param string $createdAt Creation timestamp
     */
    public function __construct(
        private int $id,
        private int $bookId,
        private int $memberId,
        private string $loanDate,
        private string $dueDate,
        private ?string $returnDate = null,
        private string $status = 'active',
        private string $createdAt = ''
    ) {
        if (!in_array($this->status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                "Invalid loan status: {$this->status}. " .
                "Valid statuses: " . implode(', ', self::VALID_STATUSES)
            );
        }
    }

    /**
     * Get the loan ID
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the book ID
     * 
     * @return int
     */
    public function getBookId(): int
    {
        return $this->bookId;
    }

    /**
     * Get the member ID
     * 
     * @return int
     */
    public function getMemberId(): int
    {
        return $this->memberId;
    }

    /**
     * Get the loan date
     * 
     * @return string
     */
    public function getLoanDate(): string
    {
        return $this->loanDate;
    }

    /**
     * Get the due date
     * 
     * @return string
     */
    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    /**
     * Get the return date
     * 
     * @return string|null
     */
    public function getReturnDate(): ?string
    {
        return $this->returnDate;
    }

    /**
     * Get the loan status
     * 
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Check if the loan is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the loan is overdue
     * 
     * @return bool
     */
    public function isOverdue(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        return new \DateTime($this->dueDate) < new \DateTime();
    }

    /**
     * Get number of days overdue (0 if not overdue)
     * 
     * @return int
     */
    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        $due = new \DateTime($this->dueDate);
        $now = new \DateTime();
        return (int) $now->diff($due)->days;
    }

    /**
     * Mark the loan as returned
     * 
     * @param string|null $returnDate The return date (defaults to today)
     * @return self
     * @throws \LogicException If loan is not active
     */
    public function markReturned(?string $returnDate = null): self
    {
        if ($this->status !== 'active') {
            throw new \LogicException("Cannot return a loan with status: {$this->status}");
        }
        
        $this->returnDate = $returnDate ?? (new \DateTime())->format('Y-m-d');
        $this->status = 'returned';
        return $this;
    }

    /**
     * Mark the loan as overdue
     * 
     * @return self
     * @throws \LogicException If loan is not active
     */
    public function markOverdue(): self
    {
        if ($this->status !== 'active') {
            throw new \LogicException("Cannot mark as overdue a loan with status: {$this->status}");
        }
        
        $this->status = 'overdue';
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
            'book_id' => $this->bookId,
            'member_id' => $this->memberId,
            'loan_date' => $this->loanDate,
            'due_date' => $this->dueDate,
            'return_date' => $this->returnDate,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Create a Loan instance from an array
     * 
     * @param array $data The loan data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            bookId: (int) ($data['book_id'] ?? 0),
            memberId: (int) ($data['member_id'] ?? 0),
            loanDate: $data['loan_date'] ?? '',
            dueDate: $data['due_date'] ?? '',
            returnDate: $data['return_date'] ?? null,
            status: $data['status'] ?? 'active',
            createdAt: $data['created_at'] ?? ''
        );
    }
}
