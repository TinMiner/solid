<?php

declare(strict_types=1);

/**
 * Library Management System - Object-Oriented Version
 * 
 * This application demonstrates all SOLID principles of PHP development:
 * 
 * 1. Single Responsibility Principle (SRP)
 *    - Each class has one reason to change
 *    - Book model handles only book data
 *    - BookRepository handles only book persistence
 *    - BookService handles only book business logic
 * 
 * 2. Open/Closed Principle (OCP)
 *    - Services are open for extension via interfaces
 *    - New notification types can be added without modifying LoanService
 *    - New repository implementations can be created without changing services
 * 
 * 3. Liskov Substitution Principle (LSP)
 *    - Any LoggerInterface implementation can replace DatabaseLogger
 *    - Any NotificationInterface implementation can replace ConsoleNotificationService
 *    - Any BookRepositoryInterface implementation can replace BookRepository
 * 
 * 4. Interface Segregation Principle (ISP)
 *    - Fine-grained interfaces: BookRepositoryInterface, MemberRepositoryInterface, etc.
 *    - Clients depend only on methods they use
 *    - LoggerInterface is separate from NotificationInterface
 * 
 * 5. Dependency Inversion Principle (DIP)
 *    - High-level modules (services) depend on abstractions (interfaces)
 *    - Low-level modules (repositories) implement abstractions
 *    - Dependencies are injected via constructors
 * 
 * @package Library\OOP
 * @version 1.0.0
 * @author SOLID Principles Demo
 */

echo "=== Library Management System - OOP Version ===\n\n";

// Include autoloader or manual includes
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/interfaces/BookRepositoryInterface.php';
require_once __DIR__ . '/interfaces/MemberRepositoryInterface.php';
require_once __DIR__ . '/interfaces/LoanRepositoryInterface.php';
require_once __DIR__ . '/interfaces/LoggerInterface.php';
require_once __DIR__ . '/interfaces/NotificationInterface.php';
require_once __DIR__ . '/models/Book.php';
require_once __DIR__ . '/models/Member.php';
require_once __DIR__ . '/models/Loan.php';
require_once __DIR__ . '/repositories/BookRepository.php';
require_once __DIR__ . '/repositories/MemberRepository.php';
require_once __DIR__ . '/repositories/LoanRepository.php';
require_once __DIR__ . '/services/BookService.php';
require_once __DIR__ . '/services/LoanService.php';
require_once __DIR__ . '/services/DatabaseLogger.php';
require_once __DIR__ . '/services/ConsoleNotificationService.php';

// ============================================================
// Database Setup
// ============================================================

echo "1. DATABASE SETUP\n";
echo str_repeat('-', 50) . "\n";

$dbPath = __DIR__ . '/../database/library.db';

// Remove existing database for fresh start
if (file_exists($dbPath)) {
    unlink($dbPath);
}

// Initialize database
$db = Database::getInstance($dbPath);
$db->initialize(
    schemaFile: __DIR__ . '/../database/schema.sql',
    seedFile: __DIR__ . '/../database/seed.sql'
);

echo "Database initialized with schema and seed data.\n\n";

// ============================================================
// Dependency Injection Container (Manual)
// ============================================================

// Create concrete implementations
$logger = new DatabaseLogger($db->getConnection());
$notificationService = new ConsoleNotificationService();
$bookRepository = new BookRepository($db->getConnection());
$memberRepository = new MemberRepository($db->getConnection());
$loanRepository = new LoanRepository($db->getConnection());

// Create services with injected dependencies
$bookService = new BookService($bookRepository, $logger);
$loanService = new LoanService(
    loanRepository: $loanRepository,
    bookRepository: $bookRepository,
    memberRepository: $memberRepository,
    logger: $logger,
    notificationService: $notificationService
);

echo "2. DEPENDENCIES INJECTED\n";
echo str_repeat('-', 50) . "\n";
echo "✓ DatabaseLogger injected into services\n";
echo "✓ ConsoleNotificationService injected into LoanService\n";
echo "✓ Repository implementations injected into services\n\n";

// ============================================================
// Demonstrate SOLID Principles
// ============================================================

echo "3. SOLID PRINCIPLES IN ACTION\n";
echo str_repeat('-', 50) . "\n\n";

// --- Single Responsibility Principle (SRP) ---
echo "A. Single Responsibility Principle (SRP)\n";
echo "   Each class has ONE reason to change:\n\n";

// Book model - only handles book data
$book = $bookService->getBook(1);
echo "   - Book model: Represents book data (ID: {$book->getId()}, Title: '{$book->getTitle()}')\n";
echo "   - BookRepository: Handles ONLY book persistence\n";
echo "   - BookService: Handles ONLY book business logic\n";
echo "   - DatabaseLogger: Handles ONLY logging operations\n\n";

// --- Open/Closed Principle (OCP) ---
echo "B. Open/Closed Principle (OCP)\n";
echo "   Open for extension, closed for modification:\n\n";

echo "   - BookService can use any BookRepositoryInterface implementation\n";
echo "   - LoanService can use any NotificationInterface implementation\n";
echo "   - To add email notifications, create EmailNotificationService\n";
echo "     (no need to modify LoanService)\n\n";

// --- Liskov Substitution Principle (LSP) ---
echo "C. Liskov Substitution Principle (LSP)\n";
echo "   Substitutable implementations:\n\n";

echo "   - DatabaseLogger can be replaced with FileLogger\n";
echo "   - ConsoleNotificationService can be replaced with EmailNotificationService\n";
echo "   - BookRepository can be replaced with BookRepositoryMySQL\n";
echo "   - All would work without changing dependent code\n\n";

// --- Interface Segregation Principle (ISP) ---
echo "D. Interface Segregation Principle (ISP)\n";
echo "   Clients depend only on methods they use:\n\n";

echo "   - BookRepositoryInterface: Focused on book operations\n";
echo "   - MemberRepositoryInterface: Focused on member operations\n";
echo "   - LoanRepositoryInterface: Focused on loan operations\n";
echo "   - LoggerInterface: Focused on logging\n";
echo "   - NotificationInterface: Focused on notifications\n\n";

// --- Dependency Inversion Principle (DIP) ---
echo "E. Dependency Inversion Principle (DIP)\n";
echo "   High-level modules depend on abstractions:\n\n";

echo "   - BookService depends on BookRepositoryInterface (abstraction)\n";
echo "   - BookService depends on LoggerInterface (abstraction)\n";
echo "   - LoanService depends on multiple interfaces (abstractions)\n";
echo "   - Concrete implementations are injected, not hard-coded\n\n";

// ============================================================
// Demonstrate CRUD Operations
// ============================================================

echo "4. BOOK OPERATIONS\n";
echo str_repeat('-', 50) . "\n\n";

// List all books
echo "   Listing all books:\n";
$allBooks = $bookService->getAllBooks();
foreach ($allBooks as $b) {
    $availability = $b->isAvailable() ? "✓ Available" : "✗ Unavailable";
    echo "   - [{$b->getId()}] '{$b->getTitle()}' ({$b->getGenre()}) - {$availability}\n";
}
echo "\n";

// Search books by genre
echo "   Books in 'Dystopian' genre:\n";
$dystopianBooks = $bookService->getBooksByGenre('Dystopian');
foreach ($dystopianBooks as $b) {
    echo "   - '{$b->getTitle()}' by Author ID {$b->getAuthorId()}\n";
}
echo "\n";

// Add a new book
echo "   Adding new book:\n";
try {
    $newBook = $bookService->addBook([
        'title' => 'The Great Gatsby',
        'author_id' => 1,
        'isbn' => '978-0743273565',
        'genre' => 'Classic',
        'published_year' => 1925,
        'available_copies' => 2,
    ]);
    echo "   ✓ Added: '{$newBook->getTitle()}' (ID: {$newBook->getId()})\n";
} catch (\Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}
echo "\n";

// ============================================================
// Demonstrate Loan Operations
// ============================================================

echo "5. LOAN OPERATIONS\n";
echo str_repeat('-', 50) . "\n\n";

// Create a loan
echo "   Creating new loan:\n";
try {
    $loan = $loanService->createLoan(
        bookId: 2,
        memberId: 1,
        loanDurationDays: 14
    );
    echo "   ✓ Loan created: ID {$loan->getId()}\n";
    echo "     - Book ID: {$loan->getBookId()}\n";
    echo "     - Member ID: {$loan->getMemberId()}\n";
    echo "     - Due Date: {$loan->getDueDate()}\n";
    echo "     - Status: {$loan->getStatus()}\n";
} catch (\Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}
echo "\n";

// Get member's active loans
echo "   Member 1 active loans:\n";
$activeLoans = $loanService->getMemberActiveLoans(1);
foreach ($activeLoans as $l) {
    $bookData = $bookRepository->findById($l->getBookId());
    $bookTitle = $bookData['title'] ?? 'Unknown';
    echo "   - Loan #{$l->getId()}: '{$bookTitle}' (Due: {$l->getDueDate()})\n";
}
echo "\n";

// Return a book
echo "   Returning book from loan #1:\n";
try {
    $returnedLoan = $loanService->returnBook(1);
    if ($returnedLoan) {
        echo "   ✓ Book returned successfully\n";
        echo "     - Return Date: {$returnedLoan->getReturnDate()}\n";
        echo "     - Status: {$returnedLoan->getStatus()}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}
echo "\n";

// Get loan statistics
echo "   Loan Statistics:\n";
$stats = $loanService->getLoanStatistics();
echo "   - Total loans: {$stats['total']}\n";
echo "   - Active: {$stats['active']}\n";
echo "   - Returned: {$stats['returned']}\n";
echo "   - Overdue: {$stats['overdue']}\n\n";

// Process overdue loans
echo "   Processing overdue loans:\n";
$overdueLoans = $loanService->processOverdueLoans();
if (empty($overdueLoans)) {
    echo "   - No overdue loans found\n";
} else {
    foreach ($overdueLoans as $l) {
        echo "   - Loan #{$l->getId()}: {$l->getDaysOverdue()} days overdue\n";
    }
}
echo "\n";

// ============================================================
// Demonstrate Model Business Logic
// ============================================================

echo "6. MODEL BUSINESS LOGIC\n";
echo str_repeat('-', 50) . "\n\n";

// Book availability check
echo "   Book availability check:\n";
$testBook = $bookService->getBook(1);
echo "   - '{$testBook->getTitle()}': Available = " . ($testBook->isAvailable() ? 'Yes' : 'No') . "\n";
echo "   - Available copies: {$testBook->getAvailableCopies()}\n\n";

// Member loan limit check
echo "   Member loan limits:\n";
$memberData = $memberRepository->findById(1);
$member = \Member::fromArray($memberData);
$loanCount = $loanRepository->getLoanCount(1, 'active');
echo "   - {$member->getFullName()} ({$member->getMembershipType()}):\n";
echo "     Current loans: {$loanCount}\n";
echo "     Loan limit: {$member->getLoanLimit()}\n";
echo "     Can borrow more: " . ($member->canBorrow($loanCount) ? 'Yes' : 'No') . "\n\n";

// Loan overdue check
echo "   Loan overdue check:\n";
$testLoan = $loanRepository->findById(5);
if ($testLoan) {
    $loan = \Loan::fromArray($testLoan);
    echo "   - Loan #{$loan->getId()}: Status = {$loan->getStatus()}\n";
    echo "     Is overdue: " . ($loan->isOverdue() ? 'Yes' : 'No') . "\n";
    if ($loan->isOverdue()) {
        echo "     Days overdue: {$loan->getDaysOverdue()}\n";
    }
}
echo "\n";

// ============================================================
// Cleanup
// ============================================================

echo "7. CLEANUP\n";
echo str_repeat('-', 50) . "\n";
Database::resetInstance();
echo "✓ Database connection reset\n\n";

echo "=== OOP Version Complete ===\n";
echo "\nThe code demonstrates:\n";
echo "- Proper class hierarchy and separation of concerns\n";
echo "- Interface-based programming for flexibility\n";
echo "- Dependency injection for loose coupling\n";
echo "- PHPDoc documentation throughout\n";
echo "- Typed properties and return types\n";
echo "- Named arguments for clarity\n";
