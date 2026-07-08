<?php

declare(strict_types=1);

/**
 * Library Management System - Procedural Web Interface
 * 
 * Web-based UI demonstrating SOLID principles using procedural programming patterns.
 * 
 * @package Library\Procedural\Web
 */

require_once __DIR__ . '/../procedural/functions/book_functions.php';
require_once __DIR__ . '/../procedural/functions/member_functions.php';
require_once __DIR__ . '/../procedural/functions/loan_functions.php';
require_once __DIR__ . '/../procedural/functions/notification_functions.php';

// Initialize database
$dbPath = __DIR__ . '/../database/library.db';

if (!file_exists($dbPath)) {
    $pdo = getDatabaseConnection($dbPath);
    initializeDatabase(
        $pdo,
        schemaFile: __DIR__ . '/../database/schema.sql',
        seedFile: __DIR__ . '/../database/seed.sql'
    );
    $pdo = null;
}

$pdo = getDatabaseConnection($dbPath);

// Collect data for display
$allBooks = findAllBooks($pdo, 100);
$allMembers = findAllMembers($pdo, 100);
$allLoans = findAllLoans($pdo, 100);
$loanStats = getLoanStatistics($pdo);
$overdueLoans = findOverdueLoans($pdo);

// Enrich loans with book/member data
$enrichedLoans = array_map(function ($loan) use ($pdo) {
    $book = findBookById($pdo, $loan['book_id']);
    $member = findMemberById($pdo, $loan['member_id']);
    return [
        'loan' => $loan,
        'book' => $book,
        'member' => $member,
    ];
}, $allLoans);

// Get notifications from activity log
$notifications = $pdo->query(
    'SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10'
)->fetchAll();

// Handle form submissions
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_book':
                createBook($pdo, [
                    'title' => $_POST['title'],
                    'author_id' => (int) $_POST['author_id'],
                    'isbn' => $_POST['isbn'],
                    'genre' => $_POST['genre'],
                    'published_year' => !empty($_POST['published_year']) ? (int) $_POST['published_year'] : null,
                    'available_copies' => (int) ($_POST['available_copies'] ?? 1),
                ]);
                logActivity($pdo, 'create', 'book', null, json_encode(['message' => 'Book added']));
                $messages[] = ['type' => 'success', 'text' => 'Book added successfully!'];
                break;
                
            case 'create_loan':
                $onSuccess = createCheckoutNotificationCallback($pdo);
                processCheckout(
                    pdo: $pdo,
                    bookId: (int) $_POST['book_id'],
                    memberId: (int) $_POST['member_id'],
                    loanDurationDays: (int) ($_POST['duration'] ?? 14),
                    onSuccess: $onSuccess
                );
                $messages[] = ['type' => 'success', 'text' => 'Loan created successfully!'];
                break;
                
            case 'return_book':
                $onSuccess = createReturnNotificationCallback($pdo);
                processReturn(
                    pdo: $pdo,
                    loanId: (int) $_POST['loan_id'],
                    onSuccess: $onSuccess
                );
                $messages[] = ['type' => 'success', 'text' => 'Book returned successfully!'];
                break;
                
            case 'process_overdue':
                $onOverdue = createOverdueNotificationCallback($pdo);
                $overdue = processOverdueLoans($pdo, $onOverdue);
                $messages[] = ['type' => 'warning', 'text' => count($overdue) . ' overdue loan(s) processed.'];
                break;
        }
    } catch (\Exception $e) {
        $messages[] = ['type' => 'danger', 'text' => $e->getMessage()];
    }
    
    // Refresh data after operations
    $allBooks = findAllBooks($pdo, 100);
    $allMembers = findAllMembers($pdo, 100);
    $allLoans = findAllLoans($pdo, 100);
    $loanStats = getLoanStatistics($pdo);
    $overdueLoans = findOverdueLoans($pdo);
    $enrichedLoans = array_map(function ($loan) use ($pdo) {
        $book = findBookById($pdo, $loan['book_id']);
        $member = findMemberById($pdo, $loan['member_id']);
        return ['loan' => $loan, 'book' => $book, 'member' => $member];
    }, $allLoans);
    $notifications = $pdo->query(
        'SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10'
    )->fetchAll();
}

$pdo = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Procedural Version</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
            <h1>Library Management System</h1>
            <p class="subtitle">Procedural Programming - SOLID Principles Demo</p>
            <span class="version-badge">Procedural Version</span>
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
                <span class="icon" style="background: #dcfce7;">P</span>
                SOLID Principles in Procedural Code
            </h2>
            
            <div class="solid-grid">
                <!-- SRP -->
                <div class="solid-card srp">
                    <h3><span class="letter">S</span> Single Responsibility Principle</h3>
                    <p>Each function has ONE specific task.</p>
                    <ul>
                        <li><strong>findBookById():</strong> Only finds a book by ID</li>
                        <li><strong>createBook():</strong> Only creates a new book</li>
                        <li><strong>processCheckout():</strong> Only handles checkout logic</li>
                        <li><strong>sendNotification():</strong> Only sends notifications</li>
                        <li><strong>logActivity():</strong> Only logs activities</li>
                    </ul>
                </div>

                <!-- OCP -->
                <div class="solid-card ocp">
                    <h3><span class="letter">O</span> Open/Closed Principle</h3>
                    <p>Functions accept callbacks for extensibility.</p>
                    <ul>
                        <li><strong>processCheckout()</strong> accepts onSuccess, onError callbacks</li>
                        <li><strong>processReturn()</strong> accepts onSuccess callback</li>
                        <li><strong>processOverdueLoans()</strong> accepts onOverdue callback</li>
                        <li>New behavior added via callbacks, not modifying functions</li>
                    </ul>
                </div>

                <!-- LSP -->
                <div class="solid-card lsp">
                    <h3><span class="letter">L</span> Liskov Substitution Principle</h3>
                    <p>Consistent function signatures and return types.</p>
                    <ul>
                        <li><strong>find* functions</strong> return array|null or array</li>
                        <li><strong>create* functions</strong> return int (new ID)</li>
                        <li><strong>update/delete functions</strong> return bool</li>
                        <li>All functions follow predictable patterns</li>
                    </ul>
                </div>

                <!-- ISP -->
                <div class="solid-card isp">
                    <h3><span class="letter">I</span> Interface Segregation Principle</h3>
                    <p>Functions are small and focused.</p>
                    <ul>
                        <li><strong>book_functions.php:</strong> 12 focused book functions</li>
                        <li><strong>member_functions.php:</strong> 12 focused member functions</li>
                        <li><strong>loan_functions.php:</strong> 16 focused loan functions</li>
                        <li><strong>notification_functions.php:</strong> 9 focused notification functions</li>
                    </ul>
                </div>

                <!-- DIP -->
                <div class="solid-card dip">
                    <h3><span class="letter">D</span> Dependency Inversion Principle</h3>
                    <p>Functions accept dependencies as parameters.</p>
                    <ul>
                        <li>All functions accept $pdo as first parameter</li>
                        <li>Higher-order functions accept callbacks</li>
                        <li>No hard-coded database connections</li>
                        <li>Easy to test with mock dependencies</li>
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
                            <td><?= $book['id'] ?></td>
                            <td><strong><?= htmlspecialchars($book['title']) ?></strong></td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($book['genre']) ?></span></td>
                            <td><code><?= htmlspecialchars($book['isbn']) ?></code></td>
                            <td><?= $book['published_year'] ?? '-' ?></td>
                            <td><?= $book['available_copies'] ?></td>
                            <td>
                                <?php if ($book['available_copies'] > 0): ?>
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
                        <?php foreach ($allMembers as $member): ?>
                        <tr>
                            <td><?= $member['id'] ?></td>
                            <td><strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong></td>
                            <td><?= htmlspecialchars($member['email']) ?></td>
                            <td><?= $member['phone'] ?? '-' ?></td>
                            <td>
                                <span class="badge badge-<?= $member['membership_type'] === 'premium' ? 'warning' : ($member['membership_type'] === 'student' ? 'info' : 'primary') ?>">
                                    <?= ucfirst($member['membership_type']) ?>
                                </span>
                            </td>
                            <td><?= getLoanLimit($member['membership_type']) ?> books</td>
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
                        <tr>
                            <td>#<?= $item['loan']['id'] ?></td>
                            <td><?= $item['book'] ? htmlspecialchars($item['book']['title']) : "Book #{$item['loan']['book_id']}" ?></td>
                            <td><?= $item['member'] ? htmlspecialchars($item['member']['first_name'] . ' ' . $item['member']['last_name']) : "Member #{$item['loan']['member_id']}" ?></td>
                            <td><?= $item['loan']['loan_date'] ?></td>
                            <td><?= $item['loan']['due_date'] ?></td>
                            <td>
                                <span class="status status-<?= $item['loan']['status'] ?>">
                                    <span class="status-dot"></span>
                                    <?= ucfirst($item['loan']['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item['loan']['status'] === 'active'): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="return_book">
                                    <input type="hidden" name="loan_id" value="<?= $item['loan']['id'] ?>">
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
            <p>Library Management System - Procedural Version</p>
            <p>Demonstrating SOLID Principles in PHP 8+ (Procedural Style)</p>
        </footer>
    </div>
</body>
</html>
