<?php

declare(strict_types=1);

require_once __DIR__ . '/../interfaces/LoggerInterface.php';
require_once __DIR__ . '/../interfaces/NotificationInterface.php';
require_once __DIR__ . '/../repositories/BookRepository.php';
require_once __DIR__ . '/../repositories/MemberRepository.php';
require_once __DIR__ . '/../repositories/LoanRepository.php';
require_once __DIR__ . '/../models/Loan.php';

/**
 * Loan Service
 * 
 * Handles loan-related business logic and operations.
 * Demonstrates Single Responsibility Principle (SRP) - only handles loan
 * business logic, delegating persistence to repositories.
 * 
 * Demonstrates Dependency Inversion Principle (DIP) - depends on interfaces
 * rather than concrete implementations.
 * 
 * Demonstrates Open/Closed Principle (OCP) - new notification types can be
 * added without modifying existing loan processing code.
 * 
 * @package Library\OOP\Services
 */
class LoanService
{
    /**
     * Constructor
     * 
     * @param LoanRepositoryInterface $loanRepository Loan data access
     * @param BookRepositoryInterface $bookRepository Book data access
     * @param MemberRepositoryInterface $memberRepository Member data access
     * @param LoggerInterface $logger Logging service
     * @param NotificationInterface|null $notificationService Optional notification service
     */
    public function __construct(
        private LoanRepositoryInterface $loanRepository,
        private BookRepositoryInterface $bookRepository,
        private MemberRepositoryInterface $memberRepository,
        private LoggerInterface $logger,
        private ?NotificationInterface $notificationService = null
    ) {}

    /**
     * Create a new loan (checkout a book)
     * 
     * @param int $bookId The book ID to loan
     * @param int $memberId The member ID borrowing the book
     * @param int $loanDurationDays Number of days for the loan (default: 14)
     * @return Loan The newly created loan
     * @throws \InvalidArgumentException If book or member not found
     * @throws \LogicException If business rules are violated
     */
    public function createLoan(int $bookId, int $memberId, int $loanDurationDays = 14): Loan
    {
        // Verify book exists
        $bookData = $this->bookRepository->findById($bookId);
        if ($bookData === null) {
            throw new \InvalidArgumentException("Book not found: {$bookId}");
        }
        
        $book = \Book::fromArray($bookData);
        
        // Verify member exists
        $memberData = $this->memberRepository->findById($memberId);
        if ($memberData === null) {
            throw new \InvalidArgumentException("Member not found: {$memberId}");
        }
        
        $member = \Member::fromArray($memberData);
        
        // Check book availability
        if (!$book->isAvailable()) {
            throw new \LogicException(
                "Book '{$book->getTitle()}' is not available for loan"
            );
        }
        
        // Check member's loan limit
        $currentLoanCount = $this->loanRepository->getLoanCount($memberId, 'active');
        if (!$member->canBorrow($currentLoanCount)) {
            throw new \LogicException(
                "Member '{$member->getFullName()}' has reached their loan limit " .
                "of {$member->getLoanLimit()} books"
            );
        }
        
        // Create the loan
        $loanDate = (new \DateTime())->format('Y-m-d');
        $dueDate = (new \DateTime("+{$loanDurationDays} days"))->format('Y-m-d');
        
        $loanId = $this->loanRepository->create([
            'book_id' => $bookId,
            'member_id' => $memberId,
            'loan_date' => $loanDate,
            'due_date' => $dueDate,
            'status' => 'active',
        ]);
        
        // Decrease available copies
        $this->bookRepository->updateAvailableCopies($bookId, -1);
        
        $this->logger->info("Loan created", [
            'loan_id' => $loanId,
            'book_id' => $bookId,
            'member_id' => $memberId,
            'due_date' => $dueDate,
        ]);
        
        // Send notification if service is available
        if ($this->notificationService !== null) {
            $this->notificationService->send(
                $member->getEmail(),
                "Book Checkout Confirmation",
                "You have checked out '{$book->getTitle()}'. " .
                "Please return it by {$dueDate}."
            );
        }
        
        $loanData = $this->loanRepository->findById($loanId);
        
        return Loan::fromArray($loanData);
    }

    /**
     * Return a loaned book
     * 
     * @param int $loanId The loan ID
     * @return Loan|null Updated loan or null if not found
     * @throws \LogicException If loan is not active
     */
    public function returnBook(int $loanId): ?Loan
    {
        $loanData = $this->loanRepository->findById($loanId);
        
        if ($loanData === null) {
            $this->logger->warning("Loan not found", ['loan_id' => $loanId]);
            return null;
        }
        
        $loan = \Loan::fromArray($loanData);
        
        if (!$loan->isActive()) {
            throw new \LogicException(
                "Cannot return loan #{$loanId} - status is '{$loan->getStatus()}'"
            );
        }
        
        // Mark as returned
        $this->loanRepository->update($loanId, [
            'return_date' => (new \DateTime())->format('Y-m-d'),
            'status' => 'returned',
        ]);
        
        // Increase available copies
        $this->bookRepository->updateAvailableCopies($loan->getBookId(), 1);
        
        $this->logger->info("Book returned", [
            'loan_id' => $loanId,
            'book_id' => $loan->getBookId(),
            'member_id' => $loan->getMemberId(),
        ]);
        
        $updatedData = $this->loanRepository->findById($loanId);
        
        return Loan::fromArray($updatedData);
    }

    /**
     * Get all active loans for a member
     * 
     * @param int $memberId The member ID
     * @return Loan[] Array of active loans
     */
    public function getMemberActiveLoans(int $memberId): array
    {
        $data = $this->loanRepository->findActiveByMemberId($memberId);
        
        return array_map(
            fn(array $item) => \Loan::fromArray($item),
            $data
        );
    }

    /**
     * Find and mark overdue loans
     * 
     * @return Loan[] Array of loans that were marked overdue
     */
    public function processOverdueLoans(): array
    {
        $overdueData = $this->loanRepository->findOverdue();
        $overdueLoans = [];
        
        foreach ($overdueData as $data) {
            $loan = \Loan::fromArray($data);
            
            // Mark as overdue
            $this->loanRepository->update($loan->getId(), ['status' => 'overdue']);
            
            $overdueLoans[] = $loan;
            
            $this->logger->warning("Loan marked overdue", [
                'loan_id' => $loan->getId(),
                'book_id' => $loan->getBookId(),
                'member_id' => $loan->getMemberId(),
                'days_overdue' => $loan->getDaysOverdue(),
            ]);
            
            // Send overdue notification if service is available
            if ($this->notificationService !== null) {
                $memberData = $this->memberRepository->findById($loan->getMemberId());
                $bookData = $this->bookRepository->findById($loan->getBookId());
                
                if ($memberData && $bookData) {
                    $this->notificationService->sendOverdueNotice(
                        $memberData['email'],
                        $bookData['title'],
                        $loan->getDaysOverdue()
                    );
                }
            }
        }
        
        return $overdueLoans;
    }

    /**
     * Get loan statistics
     * 
     * @return array Loan statistics
     */
    public function getLoanStatistics(): array
    {
        $allLoans = $this->loanRepository->findAll(10000);
        
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
}
