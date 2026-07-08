<?php
/**
 * Simple Test Runner
 * 
 * Runs all tests without PHPUnit dependency
 */

echo "=== Library Management System - Test Suite ===\n\n";

// Include files
require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/oop/interfaces/BookRepositoryInterface.php';
require_once __DIR__ . '/oop/interfaces/MemberRepositoryInterface.php';
require_once __DIR__ . '/oop/interfaces/LoanRepositoryInterface.php';
require_once __DIR__ . '/oop/interfaces/LoggerInterface.php';
require_once __DIR__ . '/oop/interfaces/NotificationInterface.php';
require_once __DIR__ . '/oop/models/Book.php';
require_once __DIR__ . '/oop/models/Member.php';
require_once __DIR__ . '/oop/models/Loan.php';
require_once __DIR__ . '/oop/repositories/BookRepository.php';
require_once __DIR__ . '/oop/repositories/MemberRepository.php';
require_once __DIR__ . '/oop/repositories/LoanRepository.php';
require_once __DIR__ . '/oop/services/BookService.php';
require_once __DIR__ . '/oop/services/LoanService.php';
require_once __DIR__ . '/oop/services/DatabaseLogger.php';
require_once __DIR__ . '/oop/services/ConsoleNotificationService.php';
require_once __DIR__ . '/procedural/functions/book_functions.php';
require_once __DIR__ . '/procedural/functions/member_functions.php';
require_once __DIR__ . '/procedural/functions/loan_functions.php';
require_once __DIR__ . '/procedural/functions/notification_functions.php';

$passed = 0;
$failed = 0;
$errors = [];

/**
 * Assert helper
 */
function assert_test(bool $condition, string $message): void {
    global $passed, $failed, $errors;
    if ($condition) {
        $passed++;
        echo "  ✓ {$message}\n";
    } else {
        $failed++;
        $errors[] = $message;
        echo "  ✗ {$message}\n";
    }
}

// ============================================================
// Model Tests
// ============================================================
echo "1. Book Model Tests\n";
echo str_repeat('-', 50) . "\n";

$book = new Book(1, 'Test Book', 1, '978-0000000001', 'Fiction', 2024, 3);
assert_test($book->getId() === 1, 'Book ID is correct');
assert_test($book->getTitle() === 'Test Book', 'Book title is correct');
assert_test($book->getAuthorId() === 1, 'Book author ID is correct');
assert_test($book->getIsbn() === '978-0000000001', 'Book ISBN is correct');
assert_test($book->getGenre() === 'Fiction', 'Book genre is correct');
assert_test($book->getPublishedYear() === 2024, 'Book year is correct');
assert_test($book->getAvailableCopies() === 3, 'Book copies is correct');
assert_test($book->isAvailable() === true, 'Book is available');
assert_test($book->decrementCopies()->getAvailableCopies() === 2, 'Book copies decremented');
assert_test($book->incrementCopies()->getAvailableCopies() === 3, 'Book copies incremented');

$array = $book->toArray();
assert_test(is_array($array), 'Book toArray returns array');
assert_test($array['title'] === 'Test Book', 'Book toArray has correct title');

$fromArray = Book::fromArray($array);
assert_test($fromArray instanceof Book, 'Book fromArray returns Book instance');
assert_test($fromArray->getTitle() === 'Test Book', 'Book fromArray has correct title');

echo "\n";

// ============================================================
echo "2. Member Model Tests\n";
echo str_repeat('-', 50) . "\n";

$member = new Member(1, 'John', 'Doe', 'john@example.com', '555-0101', 'premium');
assert_test($member->getId() === 1, 'Member ID is correct');
assert_test($member->getFirstName() === 'John', 'Member first name is correct');
assert_test($member->getLastName() === 'Doe', 'Member last name is correct');
assert_test($member->getFullName() === 'John Doe', 'Member full name is correct');
assert_test($member->getEmail() === 'john@example.com', 'Member email is correct');
assert_test($member->getPhone() === '555-0101', 'Member phone is correct');
assert_test($member->getMembershipType() === 'premium', 'Member type is correct');
assert_test($member->getLoanLimit() === 10, 'Premium loan limit is 10');
assert_test($member->canBorrow(5) === true, 'Premium member can borrow at 5');
assert_test($member->canBorrow(10) === false, 'Premium member cannot borrow at 10');

echo "\n";

// ============================================================
echo "3. Loan Model Tests\n";
echo str_repeat('-', 50) . "\n";

$loan = new Loan(1, 1, 1, '2024-01-01', '2024-01-15', null, 'active');
assert_test($loan->getId() === 1, 'Loan ID is correct');
assert_test($loan->getBookId() === 1, 'Loan book ID is correct');
assert_test($loan->getMemberId() === 1, 'Loan member ID is correct');
assert_test($loan->getStatus() === 'active', 'Loan status is active');
assert_test($loan->isActive() === true, 'Loan isActive returns true');

$loan->markReturned('2024-01-14');
assert_test($loan->getStatus() === 'returned', 'Loan status changed to returned');
assert_test($loan->getReturnDate() === '2024-01-14', 'Loan return date is correct');

echo "\n";

// ============================================================
echo "4. Database Tests\n";
echo str_repeat('-', 50) . "\n";

$dbPath = __DIR__ . '/database/test_simple.db';
if (file_exists($dbPath)) unlink($dbPath);

$db = Database::getInstance($dbPath);
assert_test($db instanceof Database, 'Database instance created');

$pdo = $db->getConnection();
assert_test($pdo instanceof \PDO, 'Database getConnection returns PDO');

$db->initialize(
    schemaFile: __DIR__ . '/database/schema.sql',
    seedFile: __DIR__ . '/database/seed.sql'
);

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
assert_test(in_array('books', $tables), 'Books table exists');
assert_test(in_array('members', $tables), 'Members table exists');
assert_test(in_array('loans', $tables), 'Loans table exists');

$count = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
assert_test($count == 10, 'Seed data has 10 books');

$count = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
assert_test($count == 5, 'Seed data has 5 members');

Database::resetInstance();
unlink($dbPath);

echo "\n";

// ============================================================
echo "5. Repository Tests\n";
echo str_repeat('-', 50) . "\n";

$dbPath = __DIR__ . '/database/test_repo.db';
if (file_exists($dbPath)) unlink($dbPath);

$pdo = new \PDO("sqlite:{$dbPath}");
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(file_get_contents(__DIR__ . '/database/schema.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/database/seed.sql'));

$bookRepo = new BookRepository($pdo);
$memberRepo = new MemberRepository($pdo);
$loanRepo = new LoanRepository($pdo);

// BookRepository tests
$book = $bookRepo->findById(1);
assert_test($book !== null, 'BookRepository findById returns book');
assert_test($book['title'] === '1984', 'BookRepository findById returns correct book');

$book = $bookRepo->findByIsbn('978-0451524935');
assert_test($book !== null, 'BookRepository findByIsbn returns book');

$books = $bookRepo->findAll();
assert_test(count($books) == 10, 'BookRepository findAll returns all books');

$books = $bookRepo->findByGenre('Dystopian');
assert_test(count($books) == 1, 'BookRepository findByGenre returns filtered books');

$id = $bookRepo->create([
    'title' => 'New Book',
    'author_id' => 1,
    'isbn' => '978-0000000099',
    'genre' => 'Fiction',
    'available_copies' => 1,
]);
assert_test($id > 0, 'BookRepository create returns ID');

$result = $bookRepo->update($id, ['title' => 'Updated Book']);
assert_test($result === true, 'BookRepository update returns true');

$book = $bookRepo->findById($id);
assert_test($book['title'] === 'Updated Book', 'BookRepository update modifies book');

$result = $bookRepo->delete($id);
assert_test($result === true, 'BookRepository delete returns true');

assert_test($bookRepo->existsByIsbn('978-0451524935') === true, 'BookRepository existsByIsbn returns true');
assert_test($bookRepo->existsByIsbn('000-0000000000') === false, 'BookRepository existsByIsbn returns false');

$copies = $bookRepo->getAvailableCopies(1);
assert_test($copies === 3, 'BookRepository getAvailableCopies returns correct count');

// MemberRepository tests
$member = $memberRepo->findById(1);
assert_test($member !== null, 'MemberRepository findById returns member');

$member = $memberRepo->findByEmail('john.smith@email.com');
assert_test($member !== null, 'MemberRepository findByEmail returns member');

$members = $memberRepo->findAll();
assert_test(count($members) == 5, 'MemberRepository findAll returns all members');

$members = $memberRepo->findByType('premium');
assert_test(count($members) == 2, 'MemberRepository findByType returns filtered members');

// LoanRepository tests
$loan = $loanRepo->findById(1);
assert_test($loan !== null, 'LoanRepository findById returns loan');

$loans = $loanRepo->findAll();
assert_test(count($loans) == 6, 'LoanRepository findAll returns all loans');

$loans = $loanRepo->findActiveByMemberId(1);
assert_test(count($loans) >= 1, 'LoanRepository findActiveByMemberId returns active loans');

$overdue = $loanRepo->findOverdue();
assert_test(count($overdue) >= 1, 'LoanRepository findOverdue returns overdue loans');

$loaned = $loanRepo->isBookLoaned(1);
assert_test($loaned === true, 'LoanRepository isBookLoaned returns true');

unset($pdo);
unlink($dbPath);

echo "\n";

// ============================================================
echo "6. Service Tests\n";
echo str_repeat('-', 50) . "\n";

$dbPath = __DIR__ . '/database/test_service.db';
if (file_exists($dbPath)) unlink($dbPath);

$pdo = new \PDO("sqlite:{$dbPath}");
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(file_get_contents(__DIR__ . '/database/schema.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/database/seed.sql'));

$bookRepo = new BookRepository($pdo);
$memberRepo = new MemberRepository($pdo);
$loanRepo = new LoanRepository($pdo);
$logger = new DatabaseLogger($pdo);
$notification = new ConsoleNotificationService();

$bookService = new BookService($bookRepo, $logger);
$loanService = new LoanService($loanRepo, $bookRepo, $memberRepo, $logger, $notification);

// BookService tests
$book = $bookService->getBook(1);
assert_test($book instanceof Book, 'BookService getBook returns Book instance');
assert_test($book->getTitle() === '1984', 'BookService getBook returns correct book');

$book = $bookService->getBook(999);
assert_test($book === null, 'BookService getBook returns null for non-existent book');

$books = $bookService->getAllBooks();
assert_test(count($books) == 10, 'BookService getAllBooks returns all books');

$books = $bookService->getBooksByGenre('Dystopian');
assert_test(count($books) == 1, 'BookService getBooksByGenre returns filtered books');

$newBook = $bookService->addBook([
    'title' => 'Service Book',
    'author_id' => 1,
    'isbn' => '978-0000000088',
    'genre' => 'Fiction',
]);
assert_test($newBook instanceof Book, 'BookService addBook returns Book instance');
assert_test($newBook->getTitle() === 'Service Book', 'BookService addBook creates correct book');

try {
    $bookService->addBook(['title' => 'Missing Fields']);
    assert_test(false, 'BookService addBook throws exception for missing fields');
} catch (\InvalidArgumentException $e) {
    assert_test(true, 'BookService addBook throws exception for missing fields');
}

$updatedBook = $bookService->updateBook(1, ['title' => 'Updated Service Book']);
assert_test($updatedBook->getTitle() === 'Updated Service Book', 'BookService updateBook modifies book');

$result = $bookService->deleteBook(1);
assert_test($result === true, 'BookService deleteBook returns true');

// LoanService tests
$loan = $loanService->createLoan(bookId: 2, memberId: 1, loanDurationDays: 14);
assert_test($loan instanceof Loan, 'LoanService createLoan returns Loan instance');
assert_test($loan->getStatus() === 'active', 'LoanService createLoan creates active loan');

try {
    $loanService->createLoan(bookId: 999, memberId: 1);
    assert_test(false, 'LoanService createLoan throws exception for non-existent book');
} catch (\InvalidArgumentException $e) {
    assert_test(true, 'LoanService createLoan throws exception for non-existent book');
}

$returned = $loanService->returnBook($loan->getId());
assert_test($returned instanceof Loan, 'LoanService returnBook returns Loan instance');
assert_test($returned->getStatus() === 'returned', 'LoanService returnBook marks as returned');

$activeLoans = $loanService->getMemberActiveLoans(1);
assert_test(is_array($activeLoans), 'LoanService getMemberActiveLoans returns array');

$stats = $loanService->getLoanStatistics();
assert_test(is_array($stats), 'LoanService getLoanStatistics returns array');
assert_test(array_key_exists('total', $stats), 'LoanService getLoanStatistics has total');
assert_test(array_key_exists('active', $stats), 'LoanService getLoanStatistics has active');

unset($pdo);
unlink($dbPath);

echo "\n";

// ============================================================
echo "7. Procedural Function Tests\n";
echo str_repeat('-', 50) . "\n";

$dbPath = __DIR__ . '/database/test_proc.db';
if (file_exists($dbPath)) unlink($dbPath);

$pdo = new \PDO("sqlite:{$dbPath}");
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(file_get_contents(__DIR__ . '/database/schema.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/database/seed.sql'));

// Book functions
$book = findBookById($pdo, 1);
assert_test($book !== null, 'findBookById returns book');
assert_test($book['title'] === '1984', 'findBookById returns correct book');

$book = findBookByIsbn($pdo, '978-0451524935');
assert_test($book !== null, 'findBookByIsbn returns book');

$books = findAllBooks($pdo);
assert_test(count($books) == 10, 'findAllBooks returns all books');

$books = findBooksByGenre($pdo, 'Dystopian');
assert_test(count($books) == 1, 'findBooksByGenre returns filtered books');

$id = createBook($pdo, [
    'title' => 'Proc Book',
    'author_id' => 1,
    'isbn' => '978-0000000077',
    'genre' => 'Fiction',
]);
assert_test($id > 0, 'createBook returns ID');

$result = updateBook($pdo, $id, ['title' => 'Updated Proc Book']);
assert_test($result === true, 'updateBook returns true');

$result = deleteBook($pdo, $id);
assert_test($result === true, 'deleteBook returns true');

assert_test(bookExistsByIsbn($pdo, '978-0451524935') === true, 'bookExistsByIsbn returns true');
assert_test(bookExistsByIsbn($pdo, '000-0000000000') === false, 'bookExistsByIsbn returns false');

$copies = getAvailableCopies($pdo, 1);
assert_test($copies === 3, 'getAvailableCopies returns correct count');

// Member functions
$member = findMemberById($pdo, 1);
assert_test($member !== null, 'findMemberById returns member');
assert_test($member['first_name'] === 'John', 'findMemberById returns correct member');

$member = findMemberByEmail($pdo, 'john.smith@email.com');
assert_test($member !== null, 'findMemberByEmail returns member');

$members = findAllMembers($pdo);
assert_test(count($members) == 5, 'findAllMembers returns all members');

$count = getMemberLoanCount($pdo, 1);
assert_test($count >= 1, 'getMemberLoanCount returns count');

// Loan functions
$loan = findLoanById($pdo, 1);
assert_test($loan !== null, 'findLoanById returns loan');
assert_test($loan['status'] === 'active', 'findLoanById returns correct loan');

$loans = findAllLoans($pdo);
assert_test(count($loans) == 6, 'findAllLoans returns all loans');

$overdue = findOverdueLoans($pdo);
assert_test(count($overdue) >= 1, 'findOverdueLoans returns overdue loans');

$loaned = isBookLoaned($pdo, 1);
assert_test($loaned === true, 'isBookLoaned returns true');

$overdueLoan = ['status' => 'active', 'due_date' => '2020-01-01'];
assert_test(isLoanOverdue($overdueLoan) === true, 'isLoanOverdue returns true for overdue');
assert_test(getDaysOverdue($overdueLoan) > 0, 'getDaysOverdue returns positive for overdue');

$stats = getLoanStatistics($pdo);
assert_test($stats['total'] == 6, 'getLoanStatistics returns correct total');

unset($pdo);
unlink($dbPath);

echo "\n";

// ============================================================
// Summary
// ============================================================
echo "=== Test Summary ===\n";
echo str_repeat('-', 50) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n\n";

if ($failed > 0) {
    echo "Failed Tests:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}

echo "All tests passed!\n";
exit(0);
