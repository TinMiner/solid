<?php

declare(strict_types=1);

/**
 * Member Model Test Suite
 * 
 * Tests the Member model class for:
 * - Property access
 * - Membership type validation
 * - Loan limit logic
 * - Array conversion
 * 
 * @package Library\Tests\Models
 */
class MemberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Member instantiation with valid data
     */
    public function testMemberCanBeCreatedWithValidData(): void
    {
        $member = new Member(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: '555-0101',
            membershipType: 'standard'
        );

        $this->assertEquals(1, $member->getId());
        $this->assertEquals('John', $member->getFirstName());
        $this->assertEquals('Doe', $member->getLastName());
        $this->assertEquals('john@example.com', $member->getEmail());
        $this->assertEquals('555-0101', $member->getPhone());
        $this->assertEquals('standard', $member->getMembershipType());
    }

    /**
     * Test Member getFullName returns combined name
     */
    public function testGetFullNameReturnsCombinedName(): void
    {
        $member = new Member(1, 'Jane', 'Smith', 'jane@example.com');
        
        $this->assertEquals('Jane Smith', $member->getFullName());
    }

    /**
     * Test Member with invalid membership type throws exception
     */
    public function testMemberWithInvalidMembershipTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Member(1, 'Test', 'User', 'test@example.com', null, 'invalid_type');
    }

    /**
     * Test Member standard loan limit
     */
    public function testStandardMemberLoanLimit(): void
    {
        $member = new Member(1, 'Test', 'User', 'test@example.com', null, 'standard');
        
        $this->assertEquals(3, $member->getLoanLimit());
    }

    /**
     * Test Member premium loan limit
     */
    public function testPremiumMemberLoanLimit(): void
    {
        $member = new Member(1, 'Test', 'User', 'test@example.com', null, 'premium');
        
        $this->assertEquals(10, $member->getLoanLimit());
    }

    /**
     * Test Member student loan limit
     */
    public function testStudentMemberLoanLimit(): void
    {
        $member = new Member(1, 'Test', 'User', 'test@example.com', null, 'student');
        
        $this->assertEquals(5, $member->getLoanLimit());
    }

    /**
     * Test Member canBorrow when under limit
     */
    public function testMemberCanBorrowWhenUnderLimit(): void
    {
        $member = new Member(1, 'Test', 'User', 'test@example.com', null, 'standard');
        
        $this->assertTrue($member->canBorrow(1));
        $this->assertTrue($member->canBorrow(2));
    }

    /**
     * Test Member cannotBorrow when at limit
     */
    public function testMemberCannotBorrowWhenAtLimit(): void
    {
        $member = new Member(1, 'Test', 'User', 'test@example.com', null, 'standard');
        
        $this->assertFalse($member->canBorrow(3));
        $this->assertFalse($member->canBorrow(4));
    }

    /**
     * Test Member toArray returns correct structure
     */
    public function testToArrayReturnsCorrectStructure(): void
    {
        $member = new Member(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: '555-0101',
            membershipType: 'premium',
            createdAt: '2024-01-01'
        );
        
        $array = $member->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(1, $array['id']);
        $this->assertEquals('John', $array['first_name']);
        $this->assertEquals('Doe', $array['last_name']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertEquals('555-0101', $array['phone']);
        $this->assertEquals('premium', $array['membership_type']);
        $this->assertEquals('2024-01-01', $array['created_at']);
    }

    /**
     * Test Member fromArray creates valid instance
     */
    public function testFromArrayCreatesValidInstance(): void
    {
        $data = [
            'id' => 5,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '555-0202',
            'membership_type' => 'student',
            'created_at' => '2024-01-01',
        ];
        
        $member = Member::fromArray($data);
        
        $this->assertInstanceOf(Member::class, $member);
        $this->assertEquals(5, $member->getId());
        $this->assertEquals('Jane', $member->getFirstName());
        $this->assertEquals('Smith', $member->getLastName());
        $this->assertEquals('jane@example.com', $member->getEmail());
        $this->assertEquals('555-0202', $member->getPhone());
        $this->assertEquals('student', $member->getMembershipType());
    }

    /**
     * Test Member handles null phone
     */
    public function testMemberHandlesNullPhone(): void
    {
        $member = new Member(1, 'Test', 'User', 'test@example.com', null);
        
        $this->assertNull($member->getPhone());
    }

    /**
     * Test all valid membership types
     */
    public function testAllValidMembershipTypes(): void
    {
        $validTypes = ['standard', 'premium', 'student'];
        
        foreach ($validTypes as $type) {
            $member = new Member(1, 'Test', 'User', 'test@example.com', null, $type);
            $this->assertEquals($type, $member->getMembershipType());
        }
    }
}
