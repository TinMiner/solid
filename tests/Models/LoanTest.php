<?php

declare(strict_types=1);

/**
 * Loan Model Test Suite
 * 
 * Tests the Loan model class for:
 * - Property access
 * - Status validation
 * - Overdue detection
 * - Business logic methods
 * 
 * @package Library\Tests\Models
 */
class LoanTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Loan instantiation with valid data
     */
    public function testLoanCanBeCreatedWithValidData(): void
    {
        $loan = new Loan(
            id: 1,
            bookId: 1,
            memberId: 1,
            loanDate: '2024-01-01',
            dueDate: '2024-01-15',
            returnDate: null,
            status: 'active'
        );

        $this->assertEquals(1, $loan->getId());
        $this->assertEquals(1, $loan->getBookId());
        $this->assertEquals(1, $loan->getMemberId());
        $this->assertEquals('2024-01-01', $loan->getLoanDate());
        $this->assertEquals('2024-01-15', $loan->getDueDate());
        $this->assertNull($loan->getReturnDate());
        $this->assertEquals('active', $loan->getStatus());
    }

    /**
     * Test Loan with invalid status throws exception
     */
    public function testLoanWithInvalidStatusThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, 'invalid_status');
    }

    /**
     * Test Loan isActive returns true for active loans
     */
    public function testIsActiveReturnsTrueForActiveLoans(): void
    {
        $loan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, 'active');
        
        $this->assertTrue($loan->isActive());
    }

    /**
     * Test Loan isActive returns false for non-active loans
     */
    public function testIsActiveReturnsFalseForNonActiveLoans(): void
    {
        $returnedLoan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', '2024-01-14', 'returned');
        $overdueLoan = new Loan(2, 1, 1, '2024-01-01', '2024-01-15', null, 'overdue');
        
        $this->assertFalse($returnedLoan->isActive());
        $this->assertFalse($overdueLoan->isActive());
    }

    /**
     * Test Loan isOverdue detects overdue active loans
     */
    public function testIsOverdueDetectsOverdueActiveLoans(): void
    {
        // Due date in the past
        $loan = new Loan(1, 1, 1, '2024-01-01', '2020-01-01', null, 'active');
        
        $this->assertTrue($loan->isOverdue());
    }

    /**
     * Test Loan isOverdue returns false for future due date
     */
    public function testIsOverdueReturnsFalseForFutureDueDate(): void
    {
        $futureDate = date('Y-m-d', strtotime('+30 days'));
        $loan = new Loan(1, 1, 1, '2024-01-01', $futureDate, null, 'active');
        
        $this->assertFalse($loan->isOverdue());
    }

    /**
     * Test Loan isOverdue returns false for returned loans
     */
    public function testIsOverdueReturnsFalseForReturnedLoans(): void
    {
        $loan = new Loan(1, 1, 1, '2024-01-01', '2020-01-01', '2024-01-14', 'returned');
        
        $this->assertFalse($loan->isOverdue());
    }

    /**
     * Test Loan markReturned updates status and return date
     */
    public function testMarkReturnedUpdatesStatusAndReturnDate(): void
    {
        $loan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, 'active');
        
        $loan->markReturned('2024-01-14');
        
        $this->assertEquals('returned', $loan->getStatus());
        $this->assertEquals('2024-01-14', $loan->getReturnDate());
    }

    /**
     * Test Loan markReturned uses today's date when none provided
     */
    public function testMarkReturnedUsesTodayDateWhenNoneProvided(): void
    {
        $loan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, 'active');
        
        $loan->markReturned();
        
        $this->assertEquals(date('Y-m-d'), $loan->getReturnDate());
    }

    /**
     * Test Loan markReturned throws exception for non-active loans
     */
    public function testMarkReturnedThrowsExceptionForNonActiveLoans(): void
    {
        $this->expectException(\LogicException::class);
        
        $loan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, 'returned');
        $loan->markReturned();
    }

    /**
     * Test Loan markOverdue updates status
     */
    public function testMarkOverdueUpdatesStatus(): void
    {
        $loan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, 'active');
        
        $loan->markOverdue();
        
        $this->assertEquals('overdue', $loan->getStatus());
    }

    /**
     * Test Loan markOverdue throws exception for non-active loans
     */
    public function testMarkOverdueThrowsExceptionForNonActiveLoans(): void
    {
        $this->expectException(\LogicException::class);
        
        $loan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, 'returned');
        $loan->markOverdue();
    }

    /**
     * Test Loan getDaysOverdue returns correct count
     */
    public function testGetDaysOverdueReturnsCorrectCount(): void
    {
        // Due 10 days ago
        $dueDate = date('Y-m-d', strtotime('-10 days'));
        $loan = new Loan(1, 1, 1, '2024-01-01', $dueDate, null, 'active');
        
        $this->assertEquals(10, $loan->getDaysOverdue());
    }

    /**
     * Test Loan getDaysOverdue returns 0 for non-overdue loans
     */
    public function testGetDaysOverdueReturnsZeroForNonOverdueLoans(): void
    {
        $futureDate = date('Y-m-d', strtotime('+30 days'));
        $loan = new Loan(1, 1, 1, '2024-01-01', $futureDate, null, 'active');
        
        $this->assertEquals(0, $loan->getDaysOverdue());
    }

    /**
     * Test Loan toArray returns correct structure
     */
    public function testToArrayReturnsCorrectStructure(): void
    {
        $loan = new Loan(
            id: 1,
            bookId: 2,
            memberId: 3,
            loanDate: '2024-01-01',
            dueDate: '2024-01-15',
            returnDate: '2024-01-14',
            status: 'returned',
            createdAt: '2024-01-01'
        );
        
        $array = $loan->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals(2, $array['book_id']);
        $this->assertEquals(3, $array['member_id']);
        $this->assertEquals('2024-01-01', $array['loan_date']);
        $this->assertEquals('2024-01-15', $array['due_date']);
        $this->assertEquals('2024-01-14', $array['return_date']);
        $this->assertEquals('returned', $array['status']);
    }

    /**
     * Test Loan fromArray creates valid instance
     */
    public function testFromArrayCreatesValidInstance(): void
    {
        $data = [
            'id' => 5,
            'book_id' => 3,
            'member_id' => 2,
            'loan_date' => '2024-06-01',
            'due_date' => '2024-06-15',
            'return_date' => null,
            'status' => 'active',
            'created_at' => '2024-06-01',
        ];
        
        $loan = Loan::fromArray($data);
        
        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertEquals(5, $loan->getId());
        $this->assertEquals(3, $loan->getBookId());
        $this->assertEquals(2, $loan->getMemberId());
        $this->assertEquals('2024-06-01', $loan->getLoanDate());
        $this->assertEquals('2024-06-15', $loan->getDueDate());
        $this->assertNull($loan->getReturnDate());
        $this->assertEquals('active', $loan->getStatus());
    }

    /**
     * Test all valid loan statuses
     */
    public function testAllValidLoanStatuses(): void
    {
        $validStatuses = ['active', 'returned', 'overdue'];
        
        foreach ($validStatuses as $status) {
            $loan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, $status);
            $this->assertEquals($status, $loan->getStatus());
        }
    }
}
