# Uzima Enterprise Expense Management System

A professional-grade expense management and reimbursement system designed for modern enterprises.

## Overview

Uzima Expense Management System provides a comprehensive solution for managing corporate expenses, reimbursements, and approvals. The system is built with security, scalability, and user experience in mind, making it suitable for businesses of all sizes.

## Key Features

- **Multi-Role Access Control**: Support for Employees, Managers, Finance Officers, and Administrators
- **Advanced Approval Workflows**: Configurable multi-level approval processes based on amount, department, and expense category
- **Comprehensive Reporting**: Generate detailed financial reports with filtering by user, department, date, and category
- **Interactive Dashboards**: Visual analytics and expense tracking with charts and graphs
- **Mobile-Responsive Design**: Access and submit expenses from any device
- **Audit Trails**: Complete tracking of all actions for compliance and accountability
- **Document Management**: Secure receipt and supporting document storage
- **Notifications System**: Real-time alerts for claim status changes and approvals
- **Department Budgeting**: Track and manage departmental expense budgets
- **Client Billing**: Assign expenses to clients and projects for billback

## Technical Specifications

- PHP 7.4+ with PDO for database operations
- MySQL 5.7+ database
- Responsive UI built with Tailwind CSS and modern JavaScript
- Chart.js for data visualization
- AJAX-powered forms and validation
- Comprehensive security features including CSRF protection, XSS prevention, and password hashing

## Installation

### Requirements

- XAMPP, WAMP, MAMP, or any PHP 7.4+ server environment
- MySQL 5.7+ database server
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Setup Instructions

1. **Download and Extract**:

   - Download the latest release
   - Extract to your web server's document root (e.g., htdocs folder for XAMPP)

2. **Database Setup**:

   - Create a new MySQL database named `uzima_reimbursement`
   - Import the provided `setup_database.sql` file:
     ```
     mysql -u username -p uzima_reimbursement < setup_database.sql
     ```
   - Alternatively, you can use phpMyAdmin to import the SQL file

3. **Configuration**:

   - Rename `.env.example` to `.env` and update with your database credentials
   - If using XAMPP with default settings, the database credentials are typically:
     ```
     DB_HOST=localhost
     DB_NAME=uzima_reimbursement
     DB_USER=root
     DB_PASS=
     ```

4. **File Permissions**:

   - Ensure the `uploads` directory has write permissions
   - On Linux/Mac: `chmod -R 755 uploads`

5. **Web Server Configuration**:

   - For production, ensure HTTPS is enabled
   - Optional: Set up URL rewriting via .htaccess (included)

6. **Test Installation**:
   - Navigate to the application URL in your browser
   - You should see the login page

## Default Login Credentials

After installation, you can use these default accounts:

| Type            | Email              | Password     |
| --------------- | ------------------ | ------------ |
| Administrator   | admin@uzima.com    | Admin@123    |
| Finance Officer | finance@uzima.com  | Finance@123  |
| Manager         | manager@uzima.com  | Manager@123  |
| Employee        | employee@uzima.com | Employee@123 |

**IMPORTANT**: Change these default passwords immediately after first login!

## Security Recommendations

- Change default credentials immediately
- Use HTTPS in production
- Set up regular database backups
- Update your PHP installation regularly
- Review system audit logs periodically

## Customization

- Company information can be updated in the Admin > System Settings section
- Custom expense categories can be added or modified
- Approval workflows can be customized for different expense types and amounts
- Email templates can be modified in the templates directory

## Troubleshooting

- **Database Connection Issues**: Verify database credentials in .env file
- **Upload Problems**: Check file permissions on uploads directory
- **Email Not Working**: Configure SMTP settings in System Settings
- **Session Expiring Too Quickly**: Adjust SESSION_TIMEOUT in config.php

## Contributing

We welcome contributions to improve the Uzima Expense Management System. Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For technical support, please contact support@uzima.com or open an issue on our GitHub repository.

---

© 2023 Uzima Corporation. All rights reserved.
