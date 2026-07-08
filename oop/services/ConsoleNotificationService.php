<?php

declare(strict_types=1);

require_once __DIR__ . '/../interfaces/NotificationInterface.php';

/**
 * Console Notification Service
 * 
 * Implements NotificationInterface by outputting to console.
 * Demonstrates Dependency Inversion Principle (DIP) - provides a concrete
 * implementation of the NotificationInterface abstraction.
 * 
 * Demonstrates Interface Segregation Principle (ISP) - implements only
 * the notification methods, nothing else.
 * 
 * Demonstrates Liskov Substitution Principle (LSP) - can be substituted
 * for any NotificationInterface implementation without breaking functionality.
 * 
 * @package Library\OOP\Services
 */
class ConsoleNotificationService implements NotificationInterface
{
    /**
     * Send a generic notification
     * 
     * @param string $recipientEmail The recipient's email address
     * @param string $subject The notification subject
     * @param string $message The notification message
     * @return bool Always returns true (console output always succeeds)
     */
    public function send(string $recipientEmail, string $subject, string $message): bool
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
     * @param string $recipientEmail The recipient's email address
     * @param string $bookTitle The book title
     * @param string $dueDate The due date
     * @return bool Always returns true
     */
    public function sendLoanReminder(string $recipientEmail, string $bookTitle, string $dueDate): bool
    {
        $subject = "Book Return Reminder";
        $message = "Reminder: Please return '{$bookTitle}' by {$dueDate}.";
        
        return $this->send($recipientEmail, $subject, $message);
    }

    /**
     * Send an overdue notification
     * 
     * @param string $recipientEmail The recipient's email address
     * @param string $bookTitle The book title
     * @param int $daysOverdue Number of days overdue
     * @return bool Always returns true
     */
    public function sendOverdueNotice(string $recipientEmail, string $bookTitle, int $daysOverdue): bool
    {
        $subject = "Overdue Book Notice";
        $message = "URGENT: '{$bookTitle}' is {$daysOverdue} days overdue. " .
                   "Please return it immediately to avoid additional fees.";
        
        return $this->send($recipientEmail, $subject, $message);
    }
}
