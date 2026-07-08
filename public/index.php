<?php

declare(strict_types=1);

/**
 * Library Management System - Main Entry Point
 * 
 * Landing page with links to both OOP and Procedural versions.
 * 
 * @package Library\Web
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - SOLID Principles Demo</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #1e40af 0%, #7c3aed 50%, #db2777 100%);
            color: white;
            padding: 60px 40px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            text-align: center;
        }
        .hero h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
        }
        .hero p {
            font-size: 18px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        .version-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        .version-card {
            background: white;
            border-radius: var(--radius);
            padding: 40px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .version-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        .version-card .icon-large {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 20px;
        }
        .version-card.oop .icon-large {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        .version-card.proc .icon-large {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
        }
        .version-card h2 {
            font-size: 24px;
            margin-bottom: 12px;
            color: var(--gray-800);
        }
        .version-card p {
            color: var(--gray-600);
            margin-bottom: 24px;
        }
        .version-card .btn {
            font-size: 16px;
            padding: 12px 32px;
        }
        .solid-principles {
            margin-top: 50px;
        }
        .solid-principles h2 {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Hero Header -->
        <header class="hero">
            <h1>Library Management System</h1>
            <p>A comprehensive demonstration of SOLID principles in PHP 8+ with both Object-Oriented and Procedural approaches.</p>
            <span class="version-badge">PHP 8+ | SQLite | SOLID</span>
        </header>

        <!-- Version Selection -->
        <div class="version-cards">
            <a href="oop.php" class="version-card oop">
                <div class="icon-large">O</div>
                <h2>Object-Oriented Version</h2>
                <p>Demonstrates SOLID principles using classes, interfaces, dependency injection, and proper OOP architecture.</p>
                <span class="btn btn-primary">Launch OOP Version</span>
            </a>
            
            <a href="procedural.php" class="version-card proc">
                <div class="icon-large">P</div>
                <h2>Procedural Version</h2>
                <p>Demonstrates SOLID-equivalent patterns using functions, callbacks, and parameter-based dependency injection.</p>
                <span class="btn btn-primary" style="background: #059669; border-color: #047857;">Launch Procedural Version</span>
            </a>
        </div>

        <!-- SOLID Principles Overview -->
        <section class="solid-principles section">
            <h2 class="section-title">
                <span class="icon">S</span>
                SOLID Principles Overview
            </h2>
            
            <div class="cards-grid">
                <div class="card" style="border-left: 4px solid var(--success);">
                    <div class="card-header">
                        <div class="card-title">Single Responsibility</div>
                        <span class="badge badge-success">S</span>
                    </div>
                    <div class="card-body">
                        A class/function should have one, and only one, reason to change. Each module handles a single piece of functionality.
                    </div>
                </div>
                
                <div class="card" style="border-left: 4px solid var(--info);">
                    <div class="card-header">
                        <div class="card-title">Open/Closed</div>
                        <span class="badge badge-info">O</span>
                    </div>
                    <div class="card-body">
                        Software entities should be open for extension but closed for modification. Add new features without changing existing code.
                    </div>
                </div>
                
                <div class="card" style="border-left: 4px solid var(--warning);">
                    <div class="card-header">
                        <div class="card-title">Liskov Substitution</div>
                        <span class="badge badge-warning">L</span>
                    </div>
                    <div class="card-body">
                        Objects of a superclass should be replaceable with objects of a subclass without affecting correctness.
                    </div>
                </div>
                
                <div class="card" style="border-left: 4px solid var(--danger);">
                    <div class="card-header">
                        <div class="card-title">Interface Segregation</div>
                        <span class="badge badge-danger">I</span>
                    </div>
                    <div class="card-body">
                        No client should be forced to depend on methods it does not use. Many specific interfaces are better than one general-purpose interface.
                    </div>
                </div>
                
                <div class="card" style="border-left: 4px solid #8b5cf6;">
                    <div class="card-header">
                        <div class="card-title">Dependency Inversion</div>
                        <span class="badge" style="background: #ede9fe; color: #7c3aed;">D</span>
                    </div>
                    <div class="card-body">
                        High-level modules should not depend on low-level modules. Both should depend on abstractions. Dependencies should be injected.
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <p>Library Management System | SOLID Principles Demo</p>
            <p>Built with PHP 8+ | SQLite | Clean Architecture</p>
        </footer>
    </div>
</body>
</html>
