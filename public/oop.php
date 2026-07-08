<?php

declare(strict_types=1);

/**
 * Library Management System - OOP Web Interface
 * 
 * Web-based UI demonstrating all SOLID principles with a clean browser interface.
 * 
 * @package Library\OOP\Web
 */

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../oop/interfaces/BookRepositoryInterface.php';
require_once __DIR__ . '/../oop/interfaces/MemberRepositoryInterface.php';
require_once __DIR__ . '/../oop/interfaces/LoanRepositoryInterface.php';
require_once __DIR__ . '/../oop/interfaces/LoggerInterface.php';
require_once __DIR__ . '/../oop/interfaces/NotificationInterface.php';
require_once __DIR__ . '/../oop/models/Book.php';
require_once __DIR__ . '/../oop/models/Member.php';
require_once __DIR__ . '/../oop/models/Loan.php';
require_once __DIR__ . '/../oop/repositories/BookRepository.php';
require_once __DIR__ . '/../oop/repositories/MemberRepository.php';
require_once __DIR__ . '/../oop/repositories/LoanRepository.php';
require_once __DIR__ . '/../oop/services/BookService.php';
require_once __DIR__ . '/../oop/services/LoanService.php';
require_once __DIR__ . '/../oop/services/DatabaseLogger.php';
require_once __DIR__ . '/../oop/services/ConsoleNotificationService.php';

// Initialize database
$dbPath = __DIR__ . '/../database/library.db';
$db = Database::getInstance($dbPath);

// Initialize schema/seed if needed
if (!file_exists($dbPath)) {
    $db->initialize(
        schemaFile: __DIR__ . '/../database/schema.sql',
        seedFile: __DIR__ . '/../database/seed.sql'
    );
}

// Create services
$logger = new DatabaseLogger($db->getConnection());
$notificationService = new ConsoleNotificationService();
$bookRepository = new BookRepository($db->getConnection());
$memberRepository = new MemberRepository($db->getConnection());
$loanRepository = new LoanRepository($db->getConnection());

$bookService = new BookService($bookRepository, $logger);
$loanService = new LoanService(
    loanRepository: $loanRepository,
    bookRepository: $bookRepository,
    memberRepository: $memberRepository,
    logger: $logger,
    notificationService: $notificationService
);

// Collect data for display
$allBooks = $bookService->getAllBooks(100);
$allMembers = $memberRepository->findAll(100);
$allLoans = $loanRepository->findAll(100);
$loanStats = $loanService->getLoanStatistics();
$overdueLoans = $loanRepository->findOverdue();

// Enrich loans with book/member data
$enrichedLoans = array_map(function ($loan) use ($bookRepository, $memberRepository) {
    $book = $bookRepository->findById($loan['book_id']);
    $member = $memberRepository->findById($loan['member_id']);
    return [
        'loan' => $loan,
        'book' => $book,
        'member' => $member,
    ];
}, $allLoans);

// Get notifications from activity log
$notifications = $db->getConnection()->query(
    'SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10'
)->fetchAll();

// Handle form submissions
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_book':
                $bookService->addBook([
                    'title' => $_POST['title'],
                    'author_id' => (int) $_POST['author_id'],
                    'isbn' => $_POST['isbn'],
                    'genre' => $_POST['genre'],
                    'published_year' => !empty($_POST['published_year']) ? (int) $_POST['published_year'] : null,
                    'available_copies' => (int) ($_POST['available_copies'] ?? 1),
                ]);
                $messages[] = ['type' => 'success', 'text' => 'Book added successfully!'];
                break;
                
            case 'create_loan':
                $loanService->createLoan(
                    bookId: (int) $_POST['book_id'],
                    memberId: (int) $_POST['member_id'],
                    loanDurationDays: (int) ($_POST['duration'] ?? 14)
                );
                $messages[] = ['type' => 'success', 'text' => 'Loan created successfully!'];
                break;
                
            case 'return_book':
                $loanService->returnBook((int) $_POST['loan_id']);
                $messages[] = ['type' => 'success', 'text' => 'Book returned successfully!'];
                break;
                
            case 'process_overdue':
                $overdue = $loanService->processOverdueLoans();
                $messages[] = ['type' => 'warning', 'text' => count($overdue) . ' overdue loan(s) processed.'];
                break;
        }
    } catch (\Exception $e) {
        $messages[] = ['type' => 'danger', 'text' => $e->getMessage()];
    }
    
    // Refresh data after operations
    $allBooks = $bookService->getAllBooks(100);
    $allMembers = $memberRepository->findAll(100);
    $allLoans = $loanRepository->findAll(100);
    $loanStats = $loanService->getLoanStatistics();
    $overdueLoans = $loanRepository->findOverdue();
    $enrichedLoans = array_map(function ($loan) use ($bookRepository, $memberRepository) {
        $book = $bookRepository->findById($loan['book_id']);
        $member = $memberRepository->findById($loan['member_id']);
        return ['loan' => $loan, 'book' => $book, 'member' => $member];
    }, $allLoans);
    $notifications = $db->getConnection()->query(
        'SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10'
    )->fetchAll();
}

Database::resetInstance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - OOP Version</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>Library Management System</h1>
            <p class="subtitle">Object-Oriented Programming - SOLID Principles Demo</p>
            <span class="version-badge">OOP Version</span>
        </header>

        <!-- Navigation -->
        <nav class="nav">
            <a href="#solid" class="active">SOLID Principles</a>
            <a href="#books">Books</a>
            <a href="#members">Members</a>
            <a href="#loans">Loans</a>
            <a href="#notifications">Activity Log</a>
        </nav>

        <!-- Messages -->
        <?php foreach ($messages as $msg): ?>
        <div class="section" style="background: var(--<?= $msg['type'] ?>-light); border-left: 4px solid var(--<?= $msg['type'] ?>);">
            <?= htmlspecialchars($msg['text']) ?>
        </div>
        <?php endforeach; ?>

        <!-- SOLID Principles Section -->
        <section id="solid" class="section">
            <h2 class="section-title">
                <span class="icon">S</span>
                SOLID Principles in Action
            </h2>
            
            <div class="solid-grid">
                <!-- SRP -->
                <div class="solid-card srp">
                    <h3><span class="letter">S</span> Single Responsibility Principle</h3>
                    <p>Each class has ONE reason to change.</p>
                    <ul>
                        <li><strong>Book Model:</strong> Represents book data only</li>
                        <li><strong>BookRepository:</strong> Handles ONLY book persistence</li>
                        <li><strong>BookService:</strong> Handles ONLY book business logic</li>
                        <li><strong>DatabaseLogger:</strong> Handles ONLY logging operations</li>
                    </ul>
                </div>

                <!-- OCP -->
                <div class="solid-card ocp">
                    <h3><span class="letter">O</span> Open/Closed Principle</h3>
                    <p>Open for extension, closed for modification.</p>
                    <ul>
                        <li>Services extend via interface implementations</li>
                        <li>New notification types without modifying LoanService</li>
                        <li>New repository implementations work seamlessly</li>
                    </ul>
                </div>

                <!-- LSP -->
                <div class="solid-card lsp">
                    <h3><span class="letter">L</span> Liskov Substitution Principle</h3>
                    <p>Substitutable implementations.</p>
                    <ul>
                        <li>DatabaseLogger can be replaced with FileLogger</li>
                        <li>ConsoleNotification replaced with EmailNotification</li>
                        <li>All implementations are interchangeable</li>
                    </ul>
                </div>

                <!-- ISP -->
                <div class="solid-card isp">
                    <h3><span class="letter">I</span> Interface Segregation Principle</h3>
                    <p>Clients depend only on methods they use.</p>
                    <ul>
                        <li><strong>BookRepositoryInterface:</strong> Book operations only</li>
                        <li><strong>LoggerInterface:</strong> Logging methods only</li>
                        <li><strong>NotificationInterface:</strong> Notification methods only</li>
                    </ul>
                </div>

                <!-- DIP -->
                <div class="solid-card dip">
                    <h3><span class="letter">D</span> Dependency Inversion Principle</h3>
                    <p>High-level modules depend on abstractions.</p>
                    <ul>
                        <li>Services depend on interfaces, not implementations</li>
                        <li>Dependencies injected via constructors</li>
                        <li>Easy to swap implementations</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="section">
            <h2 class="section-title">
                <span class="icon">#</span>
                Library Statistics
            </h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= count($allBooks) ?></div>
                    <div class="stat-label">Total Books</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count($allMembers) ?></div>
                    <div class="stat-label">Members</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $loanStats['active'] ?></div>
                    <div class="stat-label">Active Loans</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--danger);"><?= count($overdueLoans) ?></div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
        </section>

        <!-- Books Section -->
        <section id="books" class="section">
            <h2 class="section-title">
                <span class="icon">B</span>
                Books Collection
            </h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>ISBN</th>
                            <th>Year</th>
                            <th>Copies</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allBooks as $book): ?>
                        <tr>
                            <td><?= $book->getId() ?></td>
                            <td><strong><?= htmlspecialchars($book->getTitle()) ?></strong></td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($book->getGenre()) ?></span></td>
                            <td><code><?= htmlspecialchars($book->getIsbn()) ?></code></td>
                            <td><?= $book->getPublishedYear() ?: '-' ?></td>
                            <td><?= $book->getAvailableCopies() ?></td>
                            <td>
                                <?php if ($book->isAvailable()): ?>
                                    <span class="badge badge-success">Available</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Unavailable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Members Section -->
        <section id="members" class="section">
            <h2 class="section-title">
                <span class="icon">M</span>
                Library Members
            </h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Loan Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allMembers as $memberData): ?>
                        <?php $member = Member::fromArray($memberData); ?>
                        <tr>
                            <td><?= $member->getId() ?></td>
                            <td><strong><?= htmlspecialchars($member->getFullName()) ?></strong></td>
                            <td><?= htmlspecialchars($member->getEmail()) ?></td>
                            <td><?= $member->getPhone() ?: '-' ?></td>
                            <td>
                                <span class="badge badge-<?= $member->getMembershipType() === 'premium' ? 'warning' : ($member->getMembershipType() === 'student' ? 'info' : 'primary') ?>">
                                    <?= ucfirst($member->getMembershipType()) ?>
                                </span>
                            </td>
                            <td><?= $member->getLoanLimit() ?> books</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Loans Section -->
        <section id="loans" class="section">
            <h2 class="section-title">
                <span class="icon">L</span>
                Active Loans
            </h2>
            
            <?php if (!empty($overdueLoans)): ?>
            <div class="section" style="background: var(--danger-light); margin-bottom: 20px;">
                <strong>Warning:</strong> <?= count($overdueLoans) ?> overdue loan(s) detected!
                <form method="post" style="display: inline; margin-left: 10px;">
                    <input type="hidden" name="action" value="process_overdue">
                    <button type="submit" class="btn btn-primary">Process Overdue</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Book</th>
                            <th>Member</th>
                            <th>Loan Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrichedLoans as $item): ?>
                        <?php 
                        $loan = Loan::fromArray($item['loan']);
                        $book = $item['book'] ? Book::fromArray($item['book']) : null;
                        $member = $item['member'] ? Member::fromArray($item['member']) : null;
                        ?>
                        <tr>
                            <td>#<?= $loan->getId() ?></td>
                            <td><?= $book ? htmlspecialchars($book->getTitle()) : "Book #{$loan->getBookId()}" ?></td>
                            <td><?= $member ? htmlspecialchars($member->getFullName()) : "Member #{$loan->getMemberId()}" ?></td>
                            <td><?= $loan->getLoanDate() ?></td>
                            <td><?= $loan->getDueDate() ?></td>
                            <td>
                                <span class="status status-<?= $loan->getStatus() ?>">
                                    <span class="status-dot"></span>
                                    <?= ucfirst($loan->getStatus()) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($loan->isActive()): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="return_book">
                                    <input type="hidden" name="loan_id" value="<?= $loan->getId() ?>">
                                    <button type="submit" class="btn btn-secondary">Return</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Activity Log Section -->
        <section id="notifications" class="section">
            <h2 class="section-title">
                <span class="icon">N</span>
                Activity Log
            </h2>
            
            <div class="notification-list">
                <?php foreach ($notifications as $notif): ?>
                <div class="notification-item">
                    <div class="notification-icon <?= $notif['action'] === 'loan' ? 'warning' : ($notif['action'] === 'return' ? 'success' : 'info') ?>">
                        <?= $notif['action'] === 'loan' ? '📤' : ($notif['action'] === 'return' ? '📥' : ($notif['action'] === 'notify' ? '📧' : '📝')) ?>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?= ucfirst(htmlspecialchars($notif['action'])) ?> - <?= htmlspecialchars($notif['entity_type']) ?></div>
                        <div class="notification-message">
                            <?php 
                            $details = json_decode($notif['details'], true);
                            if ($details && isset($details['message'])) {
                                echo htmlspecialchars($details['message']);
                            } elseif ($details) {
                                echo htmlspecialchars(json_encode($details));
                            } else {
                                echo "Entity ID: {$notif['entity_id']}";
                            }
                            ?>
                        </div>
                        <div class="notification-message" style="font-size: 11px; color: var(--gray-400); margin-top: 4px;">
                            <?= $notif['created_at'] ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <p>Library Management System - OOP Version</p>
            <p>Demonstrating SOLID Principles in PHP 8+</p>
        </footer>
    </div>
</body>
</html>
