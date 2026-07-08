<?php

declare(strict_types=1);

/**
 * MemberRepository Test Suite
 * 
 * Tests the MemberRepository class for:
 * - CRUD operations
 * - Query methods
 * - Email validation
 * - Loan count tracking
 * 
 * @package Library\Tests\Repositories
 */
class MemberRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PDO Database connection */
    private \PDO $pdo;
    
    /** @var MemberRepository Repository instance */
    private MemberRepository $repository;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        $this->pdo = createTestDatabase();
        $this->repository = new MemberRepository($this->pdo);
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
     * Test findById returns member when exists
     */
    public function testFindByIdReturnsMemberWhenExists(): void
    {
        $member = $this->repository->findById(1);
        
        $this->assertIsArray($member);
        $this->assertEquals(1, $member['id']);
        $this->assertEquals('John', $member['first_name']);
    }

    /**
     * Test findById returns null when not exists
     */
    public function testFindByIdReturnsNullWhenNotExists(): void
    {
        $member = $this->repository->findById(999);
        
        $this->assertNull($member);
    }

    /**
     * Test findByEmail returns member when exists
     */
    public function testFindByEmailReturnsMemberWhenExists(): void
    {
        $member = $this->repository->findByEmail('john.smith@email.com');
        
        $this->assertIsArray($member);
        $this->assertEquals('John', $member['first_name']);
    }

    /**
     * Test findByEmail returns null when not exists
     */
    public function testFindByEmailReturnsNullWhenNotExists(): void
    {
        $member = $this->repository->findByEmail('nonexistent@email.com');
        
        $this->assertNull($member);
    }

    /**
     * Test findAll returns all members
     */
    public function testFindAllReturnsAllMembers(): void
    {
        $members = $this->repository->findAll();
        
        $this->assertIsArray($members);
        $this->assertCount(5, $members); // Seed data has 5 members
    }

    /**
     * Test findByType returns members of type
     */
    public function testFindByTypeReturnsMembersOfType(): void
    {
        $members = $this->repository->findByType('premium');
        
        $this->assertIsArray($members);
        $this->assertCount(2, $members); // Emily and David are premium
    }

    /**
     * Test create adds new member and returns ID
     */
    public function testCreateAddsNewMemberAndReturnsId(): void
    {
        $id = $this->repository->create([
            'first_name' => 'New',
            'last_name' => 'Member',
            'email' => 'new@example.com',
            'phone' => '555-0199',
            'membership_type' => 'standard',
        ]);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        $member = $this->repository->findById($id);
        $this->assertEquals('New', $member['first_name']);
    }

    /**
     * Test update modifies existing member
     */
    public function testUpdateModifiesExistingMember(): void
    {
        $result = $this->repository->update(1, [
            'first_name' => 'Updated',
        ]);
        
        $this->assertTrue($result);
        
        $member = $this->repository->findById(1);
        $this->assertEquals('Updated', $member['first_name']);
    }

    /**
     * Test delete removes member
     */
    public function testDeleteRemovesMember(): void
    {
        $result = $this->repository->delete(1);
        
        $this->assertTrue($result);
        
        $member = $this->repository->findById(1);
        $this->assertNull($member);
    }

    /**
     * Test existsByEmail returns true when exists
     */
    public function testExistsByEmailReturnsTrueWhenExists(): void
    {
        $this->assertTrue($this->repository->existsByEmail('john.smith@email.com'));
    }

    /**
     * Test existsByEmail returns false when not exists
     */
    public function testExistsByEmailReturnsFalseWhenNotExists(): void
    {
        $this->assertFalse($this->repository->existsByEmail('nonexistent@email.com'));
    }

    /**
     * Test getLoanCount returns correct count
     */
    public function testGetLoanCountReturnsCorrectCount(): void
    {
        $count = $this->repository->getLoanCount(1, 'active');
        
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    /**
     * Test getLoanCount returns 0 when no loans
     */
    public function testGetLoanCountReturnsZeroWhenNoLoans(): void
    {
        // Member 999 doesn't exist, so should have 0 loans
        $count = $this->repository->getLoanCount(999);
        
        $this->assertEquals(0, $count);
    }
}
