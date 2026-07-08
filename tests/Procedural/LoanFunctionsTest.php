<?php

declare(strict_types=1);

/**
 * Loan Functions Test Suite
 * 
 * Tests the procedural loan functions for:
 * - CRUD operations
 * - Query functions
 * - Business logic
 * - Callback integration
 * 
 * @package Library\Tests\Procedural
 */
class LoanFunctionsTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PDO Database connection */
    private \PDO $pdo;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->pdo = createTestDatabase();
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
     * Test findLoanById returns loan when exists
     */
    public function testFindLoanByIdReturnsLoanWhenExists(): void
    {
        $loan = findLoanById($this->pdo, 1);
        
        $this->assertIsArray($loan);
        $this->assertEquals(1, $loan['id']);
        $this->assertEquals('active', $loan['status']);
    }

    /**
     * Test findLoanById returns null when not exists
     */
    public function testFindLoanByIdReturnsNullWhenNotExists(): void
    {
        $loan = findLoanById($this->pdo, 999);
        
        $this->assertNull($loan);
    }

    /**
     * Test findAllLoans returns all loans
     */
    public function testFindAllLoansReturnsAllLoans(): void
    {
        $loans = findAllLoans($this->pdo);
        
        $this->assertIsArray($loans);
        $this->assertCount(6, $loans);
    }

    /**
     * Test findLoansByBookId returns loans for book
     */
    public function testFindLoansByBookIdReturnsLoansForBook(): void
    {
        $loans = findLoansByBookId($this->pdo, 1);
        
        $this->assertIsArray($loans);
        $this->assertCount(1, $loans);
    }

    /**
     * Test findLoansByMemberId returns loans for member
     */
    public function testFindLoansByMemberIdReturnsLoansForMember(): void
    {
        $loans = findLoansByMemberId($this->pdo, 1);
        
        $this->assertIsArray($loans);
        $this->assertCount(2, $loans);
    }

    /**
     * Test findActiveLoansByMemberId returns only active loans
     */
    public function testFindActiveLoansByMemberIdReturnsOnlyActiveLoans(): void
    {
        $loans = findActiveLoansByMemberId($this->pdo, 1);
        
        $this->assertIsArray($loans);
        foreach ($loans as $loan) {
            $this->assertEquals('active', $loan['status']);
        }
    }

    /**
     * Test findOverdueLoans returns overdue loans
     */
    public function testFindOverdueLoansReturnsOverdueLoans(): void
    {
        $loans = findOverdueLoans($this->pdo);
        
        $this->assertIsArray($loans);
        $this->assertGreaterThanOrEqual(1, count($loans));
    }

    /**
     * Test createLoan creates new loan
     */
    public function testCreateLoanCreatesNewLoan(): void
    {
        $id = createLoan($this->pdo, [
            'book_id' => 1,
            'member_id' => 1,
            'loan_date' => '2024-06-01',
            'due_date' => '2024-06-15',
            'status' => 'active',
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        $loan = findLoanById($this->pdo, $id);
        $this->assertEquals('active', $loan['status']);
    }

    /**
     * Test createLoan throws exception for missing fields
     */
    public function testCreateLoanThrowsExceptionForMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        createLoan($this->pdo, [
            'book_id' => 1,
        ]);
    }

    /**
     * Test updateLoan modifies existing loan
     */
    public function testUpdateLoanModifiesExistingLoan(): void
    {
        $result = updateLoan($this->pdo, 1, [
            'return_date' => '2024-06-14',
            'status' => 'returned',
        ]);
        
        $this->assertTrue($result);
        
        $loan = findLoanById($this->pdo, 1);
        $this->assertEquals('returned', $loan['status']);
    }

    /**
     * Test isBookLoaned returns true when book is on loan
     */
    public function testIsBookLoanedReturnsTrueWhenBookOnLoan(): void
    {
        $this->assertTrue(isBookLoaned($this->pdo, 1));
    }

    /**
     * Test isBookLoaned returns false when book not on loan
     */
    public function testIsBookLoanedReturnsFalseWhenBookNotOnLoan(): void
    {
        $this->assertFalse(isBookLoaned($this->pdo, 10));
    }

    /**
     * Test getLoanCount returns correct count
     */
    public function testGetLoanCountReturnsCorrectCount(): void
    {
        $count = getLoanCount($this->pdo, 1);
        
        $this->assertEquals(1, $count);
    }

    /**
     * Test isLoanOverdue returns true for overdue loans
     */
    public function testIsLoanOverdueReturnsTrueForOverdueLoans(): void
    {
        $loan = ['status' => 'active', 'due_date' => '2020-01-01'];
        
        $this->assertTrue(isLoanOverdue($loan));
    }

    /**
     * Test isLoanOverdue returns false for non-overdue loans
     */
    public function testIsLoanOverdueReturnsFalseForNonOverdueLoans(): void
    {
        $futureDate = date('Y-m-d', strtotime('+30 days'));
        $loan = ['status' => 'active', 'due_date' => $futureDate];
        
        $this->assertFalse(isLoanOverdue($loan));
    }

    /**
     * Test isLoanOverdue returns false for returned loans
     */
    public function testIsLoanOverdueReturnsFalseForReturnedLoans(): void
    {
        $loan = ['status' => 'returned', 'due_date' => '2020-01-01'];
        
        $this->assertFalse(isLoanOverdue($loan));
    }

    /**
     * Test getDaysOverdue returns correct count
     */
    public function testGetDaysOverdueReturnsCorrectCount(): void
    {
        $loan = ['status' => 'active', 'due_date' => date('Y-m-d', strtotime('-10 days'))];
        
        $this->assertEquals(10, getDaysOverdue($loan));
    }

    /**
     * Test getDaysOverdue returns 0 for non-overdue loans
     */
    public function testGetDaysOverdueReturnsZeroForNonOverdueLoans(): void
    {
        $loan = ['status' => 'active', 'due_date' => date('Y-m-d', strtotime('+30 days'))];
        
        $this->assertEquals(0, getDaysOverdue($loan));
    }

    /**
     * Test processCheckout creates loan successfully
     */
    public function testProcessCheckoutCreatesLoanSuccessfully(): void
    {
        $loan = processCheckout(
            $this->pdo,
            bookId: 1,
            memberId: 1,
            loanDurationDays: 14
        );
        
        $this->assertIsArray($loan);
        $this->assertEquals('active', $loan['status']);
    }

    /**
     * Test processCheckout throws exception for non-existent book
     */
    public function testProcessCheckoutThrowsExceptionForNonExistentBook(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        processCheckout($this->pdo, bookId: 999, memberId: 1);
    }

    /**
     * Test processCheckout accepts success callback
     */
    public function testProcessCheckoutAcceptsSuccessCallback(): void
    {
        $callbackCalled = false;
        
        $loan = processCheckout(
            $this->pdo,
            bookId: 1,
            memberId: 1,
            onSuccess: function ($loan, $book, $member) use (&$callbackCalled) {
                $callbackCalled = true;
                $this->assertIsArray($loan);
                $this->assertIsArray($book);
                $this->assertIsArray($member);
            }
        );
        
        $this->assertTrue($callbackCalled);
    }

    /**
     * Test processReturn returns book successfully
     */
    public function testProcessReturnReturnsBookSuccessfully(): void
    {
        // First create a loan
        $loan = processCheckout($this->pdo, bookId: 1, memberId: 1);
        
        // Then return it
        $returned = processReturn($this->pdo, $loan['id']);
        
        $this->assertIsArray($returned);
        $this->assertEquals('returned', $returned['status']);
        $this->assertNotNull($returned['return_date']);
    }

    /**
     * Test processReturn returns null for non-existent loan
     */
    public function testProcessReturnReturnsNullForNonExistentLoan(): void
    {
        $result = processReturn($this->pdo, 999);
        
        $this->assertNull($result);
    }

    /**
     * Test processReturn throws exception for non-active loan
     */
    public function testProcessReturnThrowsExceptionForNonActiveLoan(): void
    {
        $this->expectException(\LogicException::class);
        
        // Create and return a loan
        $loan = processCheckout($this->pdo, bookId: 1, memberId: 1);
        processReturn($this->pdo, $loan['id']);
        
        // Try to return again
        processReturn($this->pdo, $loan['id']);
    }

    /**
     * Test processOverdueLoans processes overdue loans
     */
    public function testProcessOverdueLoansProcessesOverdueLoans(): void
    {
        $overdueLoans = processOverdueLoans($this->pdo);
        
        $this->assertIsArray($overdueLoans);
        $this->assertGreaterThanOrEqual(1, count($overdueLoans));
    }

    /**
     * Test processOverdueLoans accepts callback
     */
    public function testProcessOverdueLoansAcceptsCallback(): void
    {
        $callbackCount = 0;
        
        processOverdueLoans($this->pdo, function ($loan, $book, $member) use (&$callbackCount) {
            $callbackCount++;
            $this->assertIsArray($loan);
            $this->assertIsArray($book);
            $this->assertIsArray($member);
        });
        
        $this->assertGreaterThanOrEqual(1, $callbackCount);
    }

    /**
     * Test getLoanStatistics returns correct counts
     */
    public function testGetLoanStatisticsReturnsCorrectCounts(): void
    {
        $stats = getLoanStatistics($this->pdo);
        
        $this->assertIsArray($stats);
        $this->assertEquals(6, $stats['total']);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('returned', $stats);
        $this->assertArrayHasKey('overdue', $stats);
    }

    /**
     * Test formatLoan returns formatted string
     */
    public function testFormatLoanReturnsFormattedString(): void
    {
        $loan = [
            'id' => 1,
            'book_id' => 1,
            'member_id' => 1,
            'due_date' => '2024-06-15',
            'status' => 'active',
        ];
        
        $formatted = formatLoan($loan);
        
        $this->assertIsString($formatted);
        $this->assertStringContainsString('#1', $formatted);
    }

    /**
     * Test formatLoan with book and member data
     */
    public function testFormatLoanWithBookAndMemberData(): void
    {
        $loan = [
            'id' => 1,
            'book_id' => 1,
            'member_id' => 1,
            'due_date' => '2024-06-15',
            'status' => 'active',
        ];
        $book = ['title' => 'Test Book'];
        $member = ['first_name' => 'John', 'last_name' => 'Doe'];
        
        $formatted = formatLoan($loan, $book, $member);
        
        $this->assertStringContainsString('Test Book', $formatted);
        $this->assertStringContainsString('John Doe', $formatted);
    }
}
