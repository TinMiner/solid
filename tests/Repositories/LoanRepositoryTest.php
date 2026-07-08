<?php

declare(strict_types=1);

/**
 * LoanRepository Test Suite
 * 
 * Tests the LoanRepository class for:
 * - CRUD operations
 * - Query methods
 * - Overdue detection
 * - Loan status tracking
 * 
 * @package Library\Tests\Repositories
 */
class LoanRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PDO Database connection */
    private \PDO $pdo;
    
    /** @var LoanRepository Repository instance */
    private LoanRepository $repository;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->pdo = createTestDatabase();
        $this->repository = new LoanRepository($this->pdo);
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
     * Test findById returns loan when exists
     */
    public function testFindByIdReturnsLoanWhenExists(): void
    {
        $loan = $this->repository->findById(1);
        
        $this->assertIsArray($loan);
        $this->assertEquals(1, $loan['id']);
        $this->assertEquals('active', $loan['status']);
    }

    /**
     * Test findById returns null when not exists
     */
    public function testFindByIdReturnsNullWhenNotExists(): void
    {
        $loan = $this->repository->findById(999);
        
        $this->assertNull($loan);
    }

    /**
     * Test findAll returns all loans
     */
    public function testFindAllReturnsAllLoans(): void
    {
        $loans = $this->repository->findAll();
        
        $this->assertIsArray($loans);
        $this->assertCount(6, $loans); // Seed data has 6 loans
    }

    /**
     * Test findByBookId returns loans for book
     */
    public function testFindByBookIdReturnsLoansForBook(): void
    {
        $loans = $this->repository->findByBookId(1); // Book 1 has loan #1
        
        $this->assertIsArray($loans);
        $this->assertCount(1, $loans);
    }

    /**
     * Test findByMemberId returns loans for member
     */
    public function testFindByMemberIdReturnsLoansForMember(): void
    {
        $loans = $this->repository->findByMemberId(1); // Member 1 has loans #1 and #4
        
        $this->assertIsArray($loans);
        $this->assertCount(2, $loans);
    }

    /**
     * Test findActiveByMemberId returns only active loans
     */
    public function testFindActiveByMemberIdReturnsOnlyActiveLoans(): void
    {
        $loans = $this->repository->findActiveByMemberId(1);
        
        $this->assertIsArray($loans);
        foreach ($loans as $loan) {
            $this->assertEquals('active', $loan['status']);
        }
    }

    /**
     * Test findOverdue returns overdue loans
     */
    public function testFindOverdueReturnsOverdueLoans(): void
    {
        $loans = $this->repository->findOverdue();
        
        $this->assertIsArray($loans);
        // Loan #5 is overdue (due date 2026-05-29 is in the past)
        $this->assertGreaterThanOrEqual(1, count($loans));
    }

    /**
     * Test create adds new loan and returns ID
     */
    public function testCreateAddsNewLoanAndReturnsId(): void
    {
        $id = $this->repository->create([
            'book_id' => 1,
            'member_id' => 1,
            'loan_date' => '2024-06-01',
            'due_date' => '2024-06-15',
            'status' => 'active',
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        $loan = $this->repository->findById($id);
        $this->assertEquals('active', $loan['status']);
    }

    /**
     * Test update modifies existing loan
     */
    public function testUpdateModifiesExistingLoan(): void
    {
        $result = $this->repository->update(1, [
            'return_date' => '2024-06-14',
            'status' => 'returned',
        ]);
        
        $this->assertTrue($result);
        
        $loan = $this->repository->findById(1);
        $this->assertEquals('returned', $loan['status']);
        $this->assertEquals('2024-06-14', $loan['return_date']);
    }

    /**
     * Test isBookLoaned returns true when book is on loan
     */
    public function testIsBookLoanedReturnsTrueWhenBookOnLoan(): void
    {
        // Book 1 has an active loan (#1)
        $this->assertTrue($this->repository->isBookLoaned(1));
    }

    /**
     * Test isBookLoaned returns false when book not on loan
     */
    public function testIsBookLoanedReturnsFalseWhenBookNotOnLoan(): void
    {
        // Book 10 has no active loans
        $this->assertFalse($this->repository->isBookLoaned(10));
    }

    /**
     * Test getLoanCount returns correct count
     */
    public function testGetLoanCountReturnsCorrectCount(): void
    {
        $count = $this->repository->getLoanCount(1); // Book 1 has 1 loan
        
        $this->assertEquals(1, $count);
    }

    /**
     * Test getLoanCount with status filter
     */
    public function testGetLoanCountWithStatusFilter(): void
    {
        $activeCount = $this->repository->getLoanCount(1, 'active');
        $returnedCount = $this->repository->getLoanCount(1, 'returned');
        
        $this->assertEquals(1, $activeCount); // Loan #1 is active
        $this->assertEquals(0, $returnedCount); // No returned loans for book 1
    }
}
