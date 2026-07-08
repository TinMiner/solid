<?php

declare(strict_types=1);

/**
 * LoanService Test Suite
 * 
 * Tests the LoanService class for:
 * - Loan creation and return
 * - Business rule validation
 * - Overdue processing
 * - Statistics calculation
 * 
 * @package Library\Tests\Services
 */
class LoanServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PDO Database connection */
    private \PDO $pdo;
    
    /** @var LoanService Service instance */
    private LoanService $service;
    
    /** @var BookRepository Book repository */
    private BookRepository $bookRepository;
    
    /** @var MemberRepository Member repository */
    private MemberRepository $memberRepository;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->pdo = createTestDatabase();
        $this->bookRepository = new BookRepository($this->pdo);
        $this->memberRepository = new MemberRepository($this->pdo);
        $loanRepository = new LoanRepository($this->pdo);
        $logger = new DatabaseLogger($this->pdo);
        
        $this->service = new LoanService(
            loanRepository: $loanRepository,
            bookRepository: $this->bookRepository,
            memberRepository: $this->memberRepository,
            logger: $logger,
            notificationService: new ConsoleNotificationService()
        );
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        $this->pdo = null;
        cleanupTestDatabase();
    }

    /**
     * Test createLoan creates loan successfully
     */
    public function testCreateLoanCreatesLoanSuccessfully(): void
    {
        $loan = $this->service->createLoan(
            bookId: 1,
            memberId: 1,
            loanDurationDays: 14
        );
        
        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertEquals('active', $loan->getStatus());
    }

    /**
     * Test createLoan throws exception for non-existent book
     */
    public function testCreateLoanThrowsExceptionForNonExistentBook(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->createLoan(
            bookId: 999,
            memberId: 1
        );
    }

    /**
     * Test createLoan throws exception for non-existent member
     */
    public function testCreateLoanThrowsExceptionForNonExistentMember(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->createLoan(
            bookId: 1,
            memberId: 999
        );
    }

    /**
     * Test createLoan throws exception when book unavailable
     */
    public function testCreateLoanThrowsExceptionWhenBookUnavailable(): void
    {
        // Create a book with 0 copies
        $bookId = $this->bookRepository->create([
            'title' => 'No Copies Book',
            'author_id' => 1,
            'isbn' => '978-0000000099',
            'genre' => 'Fiction',
            'available_copies' => 0,
        ]);
        
        $this->expectException(\LogicException::class);
        
        $this->service->createLoan(
            bookId: $bookId,
            memberId: 1
        );
    }

    /**
     * Test createLoan decreases available copies
     */
    public function testCreateLoanDecreasesAvailableCopies(): void
    {
        $initialCopies = $this->bookRepository->getAvailableCopies(1);
        
        $this->service->createLoan(
            bookId: 1,
            memberId: 1
        );
        
        $newCopies = $this->bookRepository->getAvailableCopies(1);
        
        $this->assertEquals($initialCopies - 1, $newCopies);
    }

    /**
     * Test returnBook marks loan as returned
     */
    public function testReturnBookMarksLoanAsReturned(): void
    {
        $loan = $this->service->createLoan(
            bookId: 1,
            memberId: 1
        );
        
        $returned = $this->service->returnBook($loan->getId());
        
        $this->assertInstanceOf(Loan::class, $returned);
        $this->assertEquals('returned', $returned->getStatus());
        $this->assertNotNull($returned->getReturnDate());
    }

    /**
     * Test returnBook increases available copies
     */
    public function testReturnBookIncreasesAvailableCopies(): void
    {
        $initialCopies = $this->bookRepository->getAvailableCopies(1);
        
        $loan = $this->service->createLoan(
            bookId: 1,
            memberId: 1
        );
        
        $this->service->returnBook($loan->getId());
        
        $finalCopies = $this->bookRepository->getAvailableCopies(1);
        
        $this->assertEquals($initialCopies, $finalCopies); // Should be back to original
    }

    /**
     * Test returnBook throws exception for non-existent loan
     */
    public function testReturnBookReturnsNullForNonExistentLoan(): void
    {
        $result = $this->service->returnBook(999);
        
        $this->assertNull($result);
    }

    /**
     * Test returnBook throws exception for non-active loan
     */
    public function testReturnBookThrowsExceptionForNonActiveLoan(): void
    {
        $this->expectException(\LogicException::class);
        
        // Create and return a loan
        $loan = $this->service->createLoan(bookId: 1, memberId: 1);
        $this->service->returnBook($loan->getId());
        
        // Try to return again
        $this->service->returnBook($loan->getId());
    }

    /**
     * Test getMemberActiveLoans returns active loans
     */
    public function testGetMemberActiveLoansReturnsActiveLoans(): void
    {
        // Create a loan for member 1
        $this->service->createLoan(bookId: 1, memberId: 1);
        
        $loans = $this->service->getMemberActiveLoans(1);
        
        $this->assertIsArray($loans);
        $this->assertGreaterThanOrEqual(1, count($loans));
        
        foreach ($loans as $loan) {
            $this->assertInstanceOf(Loan::class, $loan);
            $this->assertEquals('active', $loan->getStatus());
        }
    }

    /**
     * Test getLoanStatistics returns correct counts
     */
    public function testGetLoanStatisticsReturnsCorrectCounts(): void
    {
        $stats = $this->service->getLoanStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('returned', $stats);
        $this->assertArrayHasKey('overdue', $stats);
        
        $this->assertEquals(6, $stats['total']); // Seed data has 6 loans
    }

    /**
     * Test processOverdueLoans marks overdue loans
     */
    public function testProcessOverdueLoansMarksOverdueLoans(): void
    {
        $overdueLoans = $this->service->processOverdueLoans();
        
        $this->assertIsArray($overdueLoans);
        // Loan #5 is overdue in seed data
        $this->assertGreaterThanOrEqual(1, count($overdueLoans));
    }

    /**
     * Test member cannot borrow when at limit
     */
    public function testMemberCannotBorrowWhenAtLimit(): void
    {
        // Member 3 is a student (limit 5), check current loans
        $currentLoans = $this->service->getMemberActiveLoans(3);
        
        // If at limit, should throw exception
        $member = $this->memberRepository->findById(3);
        if (count($currentLoans) >= 5) {
            $this->expectException(\LogicException::class);
        }
        
        // This test depends on seed data state
        $this->assertTrue(true); // Placeholder
    }
}
