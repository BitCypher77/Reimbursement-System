# Database Setup for Uzima Expense Management System

This directory contains database setup scripts for the Uzima Expense Management System.

## Available Database Options

The system can work with either SQLite (default) or MySQL/MariaDB databases:

### SQLite Setup (Default)

- **File:** `setup_database.sql`
- **Database File:** `uzima.db`
- **Advantages:**
  - No separate database server required
  - Simpler setup for development and testing
  - Pre-configured and ready to use out of the box

### MySQL/MariaDB Setup

- **File:** `mysql_setup.sql`
- **Advantages:**
  - Better performance for multi-user environments
  - More robust for production deployments
  - Advanced features for larger organizations

## Setting Up MySQL/MariaDB

If you prefer to use MySQL instead of the default SQLite:

1. Create a new MySQL database:

   ```sql
   CREATE DATABASE uzima_reimbursement;
   ```

2. Import the MySQL setup script:

   ```bash
   mysql -u username -p uzima_reimbursement < mysql_setup.sql
   ```

3. Update the database configuration in the application:
   - Edit the `config.php` file to use MySQL instead of SQLite
   - Change the connection parameters to match your MySQL server

## MySQL Configuration Example

Here's how to modify the `config.php` file to use MySQL:

```php
// MySQL configuration
$host = 'localhost';
$dbname = 'uzima_reimbursement';
$username = 'your_mysql_username';
$password = 'your_mysql_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
```

## Database Diagrams

### Entity Relationship Diagram

```
users ──┐
        ├── claims ─── claim_approvals
        │      │
departments ┘      └── expense_categories
        │
        ├── approval_workflows ─── approval_steps
        │
projects ┴── clients

notifications

messages ─── message_replies
```

## Note on Compatibility

The SQLite and MySQL scripts are functionally equivalent but use different syntax:

- SQLite uses `INTEGER PRIMARY KEY AUTOINCREMENT`
- MySQL uses `INT PRIMARY KEY AUTO_INCREMENT`

If you encounter SQL syntax errors, make sure you're using the correct setup script for your database system.
