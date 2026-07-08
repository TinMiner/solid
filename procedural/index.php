<?php

declare(strict_types=1);

/**
 * Library Management System - Procedural Version
 * 
 * This application demonstrates SOLID principles using procedural programming patterns.
 * 
 * 1. Single Responsibility Principle (SRP) - Each function has ONE specific task
 * 2. Open/Closed Principle (OCP) - Functions accept callbacks for extensibility
 * 3. Liskov Substitution Principle (LSP) - Consistent function signatures
 * 4. Interface Segregation Principle (ISP) - Small, focused functions
 * 5. Dependency Inversion Principle (DIP) - Functions accept dependencies as parameters
 * 
 * @package Library\Procedural
 * @version 1.0.0
 */

echo "=== Library Management System - Procedural Version ===\n\n";

require_once __DIR__ . '/functions/book_functions.php';
require_once __DIR__ . '/functions/member_functions.php';
require_once __DIR__ . '/functions/loan_functions.php';
require_once __DIR__ . '/functions/notification_functions.php';

// Database Setup
echo "1. DATABASE SETUP\n";
echo str_repeat('-', 50) . "\n";

$dbPath = __DIR__ . '/../database/library.db';

if (file_exists($dbPath)) {
    unlink($dbPath);
}

$pdo = getDatabaseConnection($dbPath);
initializeDatabase(
    $pdo,
    schemaFile: __DIR__ . '/../database/schema.sql',
    seedFile: __DIR__ . '/../database/seed.sql'
);

echo "Database initialized with schema and seed data.\n\n";

// SOLID Principles
echo "2. SOLID PRINCIPLES IN PROCEDURAL CODE\n";
echo str_repeat('-', 50) . "\n\n";

echo "A. Single Responsibility Principle (SRP)\n";
echo "   Each function has ONE specific task:\n\n";
echo "   - findBookById(): Only finds a book by ID\n";
echo "   - createBook(): Only creates a new book\n";
echo "   - processCheckout(): Only handles checkout logic\n";
echo "   - sendNotification(): Only sends notifications\n\n";

echo "B. Open/Closed Principle (OCP)\n";
echo "   Functions accept callbacks for extensibility:\n\n";
echo "   - processCheckout() accepts onSuccess and onError callbacks\n";
echo "   - processReturn() accepts onSuccess callback\n";
echo "   - processOverdueLoans() accepts onOverdue callback\n\n";

echo "C. Liskov Substitution Principle (LSP)\n";
echo "   Consistent function signatures and return types:\n\n";
echo "   - find* functions return array|null or array\n";
echo "   - create* functions return int (new ID)\n";
echo "   - update/delete functions return bool\n\n";

echo "D. Interface Segregation Principle (ISP)\n";
echo "   Functions are small and focused:\n\n";
echo "   - book_functions.php: Focused book functions\n";
echo "   - member_functions.php: Focused member functions\n";
echo "   - loan_functions.php: Focused loan functions\n";
echo "   - notification_functions.php: Focused notification functions\n\n";

echo "E. Dependency Inversion Principle (DIP)\n";
echo "   Functions accept dependencies as parameters:\n\n";
echo "   - All functions accept \$pdo as first parameter\n";
echo "   - Higher-order functions accept callbacks\n";
echo "   - No hard-coded database connections\n\n";

// Book Operations
echo "3. BOOK OPERATIONS\n";
echo str_repeat('-', 50) . "\n\n";

echo "   Listing all books:\n";
$allBooks = findAllBooks($pdo);
foreach ($allBooks as $book) {
    echo "   " . formatBook($book) . "\n";
}
echo "\n";

echo "   Books in 'Dystopian' genre:\n";
$dystopianBooks = findBooksByGenre($pdo, 'Dystopian');
foreach ($dystopianBooks as $book) {
    echo "   - '{$book['title']}' by Author ID {$book['author_id']}\n";
}
echo "\n";

echo "   Adding new book:\n";
try {
    $newBookId = createBook($pdo, [
        'title' => 'The Great Gatsby',
        'author_id' => 1,
        'isbn' => '978-0743273565',
        'genre' => 'Classic',
        'published_year' => 1925,
        'available_copies' => 2,
    ]);
    $newBook = findBookById($pdo, $newBookId);
    echo "   Added: '{$newBook['title']}' (ID: {$newBookId})\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Loan Operations with Callbacks
echo "4. LOAN OPERATIONS (with Callbacks for Extensibility)\n";
echo str_repeat('-', 50) . "\n\n";

echo "   Creating new loan (with checkout notification):\n";
try {
    $checkoutCallback = createCheckoutNotificationCallback($pdo);
    
    $loan = processCheckout(
        pdo: $pdo,
        bookId: 2,
        memberId: 1,
        loanDurationDays: 14,
        onSuccess: $checkoutCallback,
        onError: function (\Exception $e) {
            echo "   Checkout failed: {$e->getMessage()}\n";
        }
    );
    
    echo "   Loan created: ID {$loan['id']}\n";
    echo "     - Book ID: {$loan['book_id']}\n";
    echo "     - Member ID: {$loan['member_id']}\n";
    echo "     - Due Date: {$loan['due_date']}\n";
    echo "     - Status: {$loan['status']}\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

echo "   Member 1 active loans:\n";
$activeLoans = findActiveLoansByMemberId($pdo, 1);
foreach ($activeLoans as $l) {
    $book = findBookById($pdo, $l['book_id']);
    echo "   " . formatLoan($l, $book) . "\n";
}
echo "\n";

echo "   Returning book from loan #1 (with return notification):\n";
try {
    $returnCallback = createReturnNotificationCallback($pdo);
    
    $returnedLoan = processReturn(
        pdo: $pdo,
        loanId: 1,
        onSuccess: $returnCallback
    );
    
    if ($returnedLoan) {
        echo "   Book returned successfully\n";
        echo "     - Return Date: {$returnedLoan['return_date']}\n";
        echo "     - Status: {$returnedLoan['status']}\n";
    }
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

echo "   Loan Statistics:\n";
$stats = getLoanStatistics($pdo);
echo "   - Total loans: {$stats['total']}\n";
echo "   - Active: {$stats['active']}\n";
echo "   - Returned: {$stats['returned']}\n";
echo "   - Overdue: {$stats['overdue']}\n\n";

echo "   Processing overdue loans (with overdue notifications):\n";
$overdueCallback = createOverdueNotificationCallback($pdo);
$overdueLoans = processOverdueLoans($pdo, $overdueCallback);

if (empty($overdueLoans)) {
    echo "   - No overdue loans found\n";
} else {
    foreach ($overdueLoans as $l) {
        $days = getDaysOverdue($l);
        echo "   - Loan #{$l['id']}: {$days} days overdue\n";
    }
}
echo "\n";

// Business Logic Functions
echo "5. BUSINESS LOGIC FUNCTIONS\n";
echo str_repeat('-', 50) . "\n\n";

echo "   Book availability check:\n";
$book = findBookById($pdo, 1);
$isAvailable = ($book['available_copies'] ?? 0) > 0;
echo "   - '{$book['title']}': Available = " . ($isAvailable ? 'Yes' : 'No') . "\n";
echo "   - Available copies: {$book['available_copies']}\n\n";

echo "   Member loan limits:\n";
$member = findMemberById($pdo, 1);
$loanCount = getMemberLoanCount($pdo, 1, 'active');
$limit = getLoanLimit($member['membership_type']);
$canBorrow = memberCanBorrow($pdo, 1);
echo "   - {$member['first_name']} {$member['last_name']} ({$member['membership_type']}):\n";
echo "     Current loans: {$loanCount}\n";
echo "     Loan limit: {$limit}\n";
echo "     Can borrow more: " . ($canBorrow ? 'Yes' : 'No') . "\n\n";

echo "   Loan overdue check:\n";
$testLoan = findLoanById($pdo, 5);
if ($testLoan) {
    $overdue = isLoanOverdue($testLoan);
    echo "   - Loan #{$testLoan['id']}: Status = {$testLoan['status']}\n";
    echo "     Is overdue: " . ($overdue ? 'Yes' : 'No') . "\n";
    if ($overdue) {
        $days = getDaysOverdue($testLoan);
        echo "     Days overdue: {$days}\n";
    }
}
echo "\n";

// Cleanup
echo "6. COMPLETE\n";
echo str_repeat('-', 50) . "\n";
echo "Database connection closed.\n\n";

echo "=== Procedural Version Complete ===\n";
echo "\nThe code demonstrates:\n";
echo "- Functions organized by responsibility\n";
echo "- Callback-based extensibility (OCP)\n";
echo "- Dependency injection via parameters (DIP)\n";
echo "- PHPDoc documentation throughout\n";
echo "- Typed parameters and return types\n";
echo "- Named arguments for clarity\n";
