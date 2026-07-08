<?php

declare(strict_types=1);

/**
 * Notification Functions - Procedural Version
 * 
 * Functions for sending notifications.
 * Demonstrates procedural approach to Interface Segregation Principle (ISP):
 * Functions are small and focused, each handling ONE notification type.
 * 
 * Demonstrates Dependency Inversion Principle (DIP):
 * Functions accept callback parameters for extensible behavior.
 * 
 * @package Library\Procedural\Functions
 */

/**
 * Send a generic notification
 * 
 * @param string $recipientEmail The recipient's email
 * @param string $subject The notification subject
 * @param string $message The notification message
 * @return bool Always returns true
 */
function sendNotification(string $recipientEmail, string $subject, string $message): bool
{
    echo "\n[NOTIFICATION] To: {$recipientEmail}\n";
    echo "Subject: {$subject}\n";
    echo "Message: {$message}\n";
    echo str_repeat('-', 50) . "\n";
    
    return true;
}

/**
 * Send a loan reminder notification
 * 
 * @param string $recipientEmail The recipient's email
 * @param string $bookTitle The book title
 * @param string $dueDate The due date
 * @return bool Always returns true
 */
function sendLoanReminder(string $recipientEmail, string $bookTitle, string $dueDate): bool
{
    $subject = "Book Return Reminder";
    $message = "Reminder: Please return '{$bookTitle}' by {$dueDate}.";
    
    return sendNotification($recipientEmail, $subject, $message);
}

/**
 * Send an overdue notification
 * 
 * @param string $recipientEmail The recipient's email
 * @param string $bookTitle The book title
 * @param int $daysOverdue Number of days overdue
 * @return bool Always returns true
 */
function sendOverdueNotice(string $recipientEmail, string $bookTitle, int $daysOverdue): bool
{
    $subject = "Overdue Book Notice";
    $message = "URGENT: '{$bookTitle}' is {$daysOverdue} days overdue. " .
               "Please return it immediately to avoid additional fees.";
    
    return sendNotification($recipientEmail, $subject, $message);
}

/**
 * Send a checkout confirmation
 * 
 * @param string $recipientEmail The recipient's email
 * @param string $bookTitle The book title
 * @param string $dueDate The due date
 * @return bool Always returns true
 */
function sendCheckoutConfirmation(string $recipientEmail, string $bookTitle, string $dueDate): bool
{
    $subject = "Book Checkout Confirmation";
    $message = "You have checked out '{$bookTitle}'. Please return it by {$dueDate}.";
    
    return sendNotification($recipientEmail, $subject, $message);
}

/**
 * Send a return confirmation
 * 
 * @param string $recipientEmail The recipient's email
 * @param string $bookTitle The book title
 * @return bool Always returns true
 */
function sendReturnConfirmation(string $recipientEmail, string $bookTitle): bool
{
    $subject = "Book Return Confirmation";
    $message = "You have successfully returned '{$bookTitle}'. Thank you!";
    
    return sendNotification($recipientEmail, $subject, $message);
}

/**
 * Create a notification callback for checkout
 * 
 * This demonstrates the Open/Closed Principle in procedural code:
 * Create reusable callbacks that extend functionality.
 * 
 * @param \PDO $pdo Database connection
 * @return callable Callback function for checkout success
 */
function createCheckoutNotificationCallback(\PDO $pdo): callable
    {
    return function (array $loan, array $book, array $member) use ($pdo): void {
        sendCheckoutConfirmation(
            $member['email'],
            $book['title'],
            $loan['due_date']
        );
        
        logActivity(
            $pdo,
            'notify',
            'loan',
            $loan['id'],
            json_encode([
                'type' => 'checkout_confirmation',
                'recipient' => $member['email'],
            ])
        );
    };
}

/**
 * Create a notification callback for return
 * 
 * @param \PDO $pdo Database connection
 * @return callable Callback function for return success
 */
function createReturnNotificationCallback(\PDO $pdo): callable
{
    return function (array $loan, array $book, array $member) use ($pdo): void {
        sendReturnConfirmation(
            $member['email'],
            $book['title']
        );
        
        logActivity(
            $pdo,
            'notify',
            'loan',
            $loan['id'],
            json_encode([
                'type' => 'return_confirmation',
                'recipient' => $member['email'],
            ])
        );
    };
}

/**
 * Create a notification callback for overdue loans
 * 
 * @param \PDO $pdo Database connection
 * @return callable Callback function for overdue processing
 */
function createOverdueNotificationCallback(\PDO $pdo): callable
{
    return function (array $loan, array $book, array $member) use ($pdo): void {
        $daysOverdue = getDaysOverdue($loan);
        
        sendOverdueNotice(
            $member['email'],
            $book['title'],
            $daysOverdue
        );
        
        logActivity(
            $pdo,
            'notify',
            'loan',
            $loan['id'],
            json_encode([
                'type' => 'overdue_notice',
                'recipient' => $member['email'],
                'days_overdue' => $daysOverdue,
            ])
        );
    };
}
