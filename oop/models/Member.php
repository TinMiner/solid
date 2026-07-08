<?php

declare(strict_types=1);

/**
 * Member Model
 * 
 * Represents a library member entity.
 * Demonstrates Single Responsibility Principle (SRP) - only handles member
 * data representation and member-specific validation.
 * 
 * @package Library\OOP\Models
 */
class Member
{
    /** @var array Valid membership types */
    private const VALID_TYPES = ['standard', 'premium', 'student'];

    /** @var array Maximum loans per membership type */
    private const LOAN_LIMITS = [
        'standard' => 3,
        'premium' => 10,
        'student' => 5,
    ];

    /**
     * Constructor
     * 
     * @param int $id The member ID
     * @param string $firstName First name
     * @param string $lastName Last name
     * @param string $email Email address
     * @param string|null $phone Phone number
     * @param string $membershipType Membership type
     * @param string $createdAt Creation timestamp
     */
    public function __construct(
        private int $id,
        private string $firstName,
        private string $lastName,
        private string $email,
        private ?string $phone = null,
        private string $membershipType = 'standard',
        private string $createdAt = ''
    ) {
        if (!in_array($this->membershipType, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                "Invalid membership type: {$this->membershipType}. " .
                "Valid types: " . implode(', ', self::VALID_TYPES)
            );
        }
    }

    /**
     * Get the member ID
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get first name
     * 
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Get last name
     * 
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Get full name
     * 
     * @return string
     */
    public function getFullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    /**
     * Get email address
     * 
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get phone number
     * 
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Get membership type
     * 
     * @return string
     */
    public function getMembershipType(): string
    {
        return $this->membershipType;
    }

    /**
     * Get maximum loan limit based on membership type
     * 
     * @return int
     */
    public function getLoanLimit(): int
    {
        return self::LOAN_LIMITS[$this->membershipType] ?? 3;
    }

    /**
     * Check if member can borrow more books
     * 
     * @param int $currentLoanCount Number of current active loans
     * @return bool
     */
    public function canBorrow(int $currentLoanCount): bool
    {
        return $currentLoanCount < $this->getLoanLimit();
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
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'membership_type' => $this->membershipType,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Create a Member instance from an array
     * 
     * @param array $data The member data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            firstName: $data['first_name'] ?? '',
            lastName: $data['last_name'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? null,
            membershipType: $data['membership_type'] ?? 'standard',
            createdAt: $data['created_at'] ?? ''
        );
    }
}
