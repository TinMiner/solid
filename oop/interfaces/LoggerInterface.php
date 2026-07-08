<?php

declare(strict_types=1);

/**
 * Logger Interface
 * 
 * Defines the contract for logging operations.
 * Demonstrates Interface Segregation Principle (ISP) - a focused logging interface.
 * 
 * Also supports Dependency Inversion Principle (DIP) - services depend on this
 * abstraction, allowing different logging implementations.
 * 
 * @package Library\OOP\Interfaces
 */
interface LoggerInterface
{
    /**
     * Log an informational message
     * 
     * @param string $message The log message
     * @param array $context Additional context data
     * @return void
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log an error message
     * 
     * @param string $message The error message
     * @param array $context Additional context data
     * @return void
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log a warning message
     * 
     * @param string $message The warning message
     * @param array $context Additional context data
     * @return void
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log a debug message
     * 
     * @param string $message The debug message
     * @param array $context Additional context data
     * @return void
     */
    public function debug(string $message, array $context = []): void;
}
