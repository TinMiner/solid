<?php

declare(strict_types=1);

/**
 * Notification Interface
 * 
 * Defines the contract for sending notifications.
 * Demonstrates Interface Segregation Principle (ISP) - clients depend only
 * on notification methods, not other unrelated functionality.
 * 
 * Supports Dependency Inversion Principle (DIP) - notification services
 * depend on this abstraction, allowing different notification channels.
 * 
 * @package Library\OOP\Interfaces
 */
interface NotificationInterface
{
    /**
     * Send a notification to a member
     * 
     * @param string $recipientEmail The recipient's email address
     * @param string $subject The notification subject
     * @param string $message The notification message
     * @return bool True if sent successfully
     */
    public function send(string $recipientEmail, string $subject, string $message): bool;

    /**
     * Send a loan reminder notification
     * 
     * @param string $recipientEmail The recipient's email address
     * @param string $bookTitle The book title
     * @param string $dueDate The due date
     * @return bool True if sent successfully
     */
    public function sendLoanReminder(string $recipientEmail, string $bookTitle, string $dueDate): bool;

    /**
     * Send an overdue notification
     * 
     * @param string $recipientEmail The recipient's email address
     * @param string $bookTitle The book title
     * @param int $daysOverdue Number of days overdue
     * @return bool True if sent successfully
     */
    public function sendOverdueNotice(string $recipientEmail, string $bookTitle, int $daysOverdue): bool;
}
