-- Seed data for Library Management System
-- Provides sample data to demonstrate SOLID principles in action

-- Insert Authors
INSERT INTO authors (name, email, birth_year) VALUES
('George Orwell', 'orwell@example.com', 1903),
('Jane Austen', 'austen@example.com', 1775),
('Mark Twain', 'twain@example.com', 1835),
('Virginia Woolf', 'woolf@example.com', 1882),
('Ernest Hemingway', 'hemingway@example.com', 1899);

-- Insert Books
INSERT INTO books (title, author_id, isbn, genre, published_year, available_copies) VALUES
('1984', 1, '978-0451524935', 'Dystopian', 1949, 3),
('Animal Farm', 1, '978-0451526342', 'Satire', 1945, 2),
('Pride and Prejudice', 2, '978-0141439518', 'Romance', 1813, 4),
('Sense and Sensibility', 2, '978-0141439778', 'Romance', 1811, 2),
('The Adventures of Tom Sawyer', 3, '978-0486280622', 'Adventure', 1876, 3),
('Adventures of Huckleberry Finn', 3, '978-0486280639', 'Adventure', 1884, 2),
('Mrs Dalloway', 4, '978-0156628709', 'Modernist', 1925, 1),
('To the Lighthouse', 4, '978-0156907392', 'Modernist', 1927, 1),
('The Old Man and the Sea', 5, '978-0684801223', 'Literary Fiction', 1952, 2),
('A Farewell to Arms', 5, '978-0743297332', 'War Novel', 1929, 2);

-- Insert Members
INSERT INTO members (first_name, last_name, email, phone, membership_type) VALUES
('John', 'Smith', 'john.smith@email.com', '555-0101', 'standard'),
('Emily', 'Johnson', 'emily.johnson@email.com', '555-0102', 'premium'),
('Michael', 'Williams', 'michael.williams@email.com', '555-0103', 'student'),
('Sarah', 'Brown', 'sarah.brown@email.com', '555-0104', 'standard'),
('David', 'Jones', 'david.jones@email.com', '555-0105', 'premium');

-- Insert Loans (some active, some returned, some overdue)
INSERT INTO loans (book_id, member_id, loan_date, due_date, return_date, status) VALUES
(1, 1, '2026-06-01', '2026-06-15', NULL, 'active'),
(3, 2, '2026-05-20', '2026-06-03', '2026-06-02', 'returned'),
(5, 3, '2026-05-15', '2026-05-29', NULL, 'overdue'),
(2, 1, '2026-06-10', '2026-06-24', NULL, 'active'),
(9, 4, '2026-06-05', '2026-06-19', NULL, 'active'),
(7, 5, '2026-06-12', '2026-06-26', NULL, 'active');

-- Insert Activity Log entries
INSERT INTO activity_log (action, entity_type, entity_id, details) VALUES
('create', 'book', 1, 'Added "1984" to the library'),
('create', 'book', 3, 'Added "Pride and Prejudice" to the library'),
('loan', 'loan', 1, 'Book loaned to John Smith'),
('return', 'loan', 2, 'Book returned by Emily Johnson'),
('create', 'member', 1, 'New member: John Smith');
