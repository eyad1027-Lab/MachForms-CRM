# Machform CRM - Advanced Pure PHP CRM for Machform

A comprehensive Customer Relationship Management system built with pure PHP for managing Machform forms and entries.

## Features

### Dashboard
- Real-time statistics and analytics
- Entry trend charts (last 7/30 days)
- Form status breakdown
- System health monitoring
- Quick actions shortcuts

### Forms Management
- List all forms with entry counts
- Create new forms
- Activate/Deactivate forms
- Search and filter forms
- Delete forms
- View form details

### Entries Management
- View all form submissions
- Filter by status, date range, and search
- Pagination support
- Export to CSV
- Delete entries
- Update entry status

### Reports & Analytics
- Comprehensive dashboard statistics
- Entry trends and patterns
- Payment statistics
- Approval workflow stats
- Export reports (JSON/CSV)

### Additional Modules
- Approval management
- Email templates
- Webhooks configuration
- System settings

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO extension enabled

### Setup Steps

1. **Copy files to your web server**
   ```bash
   cp -r machform_crm /var/www/html/
   ```

2. **Configure database connection**
   Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'jmaheryc_forms');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('APP_URL', 'http://your-domain.com/machform_crm');
   ```

3. **Create users table** (for authentication)
   ```sql
   CREATE TABLE IF NOT EXISTS users (
       id INT AUTO_INCREMENT PRIMARY KEY,
       username VARCHAR(50) UNIQUE NOT NULL,
       email VARCHAR(100) UNIQUE NOT NULL,
       password VARCHAR(255) NOT NULL,
       role VARCHAR(20) DEFAULT 'user',
       created_at DATETIME DEFAULT CURRENT_TIMESTAMP
   );
   
   -- Create default admin user (password: admin123)
   INSERT INTO users (username, email, password, role) VALUES
   ('admin', 'admin@example.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIxF.0KLK.', 'admin');
   ```

4. **Set permissions**
   ```bash
   chmod -R 755 /var/www/html/machform_crm
   chmod -R 777 /var/www/html/machform_crm/logs
   chmod -R 777 /var/www/html/machform_crm/uploads
   ```

5. **Access the application**
   Navigate to `http://your-domain.com/machform_crm`

## Directory Structure

```
machform_crm/
├── config/
│   └── database.php          # Configuration settings
├── includes/
│   ├── Database.php          # Database connection class
│   └── Auth.php              # Authentication class
├── modules/
│   ├── forms/
│   │   ├── FormModel.php     # Forms data model
│   │   └── forms.php         # Forms management page
│   ├── entries/
│   │   ├── EntryModel.php    # Entries data model
│   │   └── entries.php       # Entries management page
│   ├── reports/
│   │   ├── ReportModel.php   # Reports data model
│   │   └── reports.php       # Reports page
│   ├── approval/             # Approval module
│   ├── emails/               # Email templates module
│   ├── webhooks/             # Webhooks module
│   └── settings/             # Settings module
├── templates/
│   └── header.php            # Main template
├── assets/
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   ├── js/
│   │   └── main.js           # JavaScript functions
│   └── images/
├── uploads/                   # File uploads directory
├── logs/                      # Log files directory
├── index.php                  # Dashboard
├── login.php                  # Login page
└── logout.php                 # Logout handler
```

## Database Tables Used

The CRM works with your existing Machform database tables:

- `ap_forms` - Forms configuration
- `ap_form_{id}` - Form entries (dynamic tables)
- `ap_form_{id}_log` - Entry logs
- `ap_approval_settings` - Approval workflow settings
- `ap_approvers` - Form approvers
- `ap_email_logic` - Email notification rules
- `ap_element_options` - Form element options
- And more...

## Security Features

- Password hashing with bcrypt
- CSRF token protection
- SQL injection prevention (prepared statements)
- XSS protection (htmlspecialchars)
- Session-based authentication
- Role-based access control

## Default Credentials

```
Username: admin
Password: admin123
```

**Important:** Change these credentials immediately after first login!

## Customization

### Changing Theme Colors
Edit CSS variables in `assets/css/style.css`:
```css
:root {
    --primary-color: #4CAF50;
    --secondary-color: #2196F3;
    /* ... */
}
```

### Adding New Modules
1. Create folder in `modules/`
2. Add Model class for database operations
3. Create main PHP page
4. Add navigation link in `templates/header.php`

## API Endpoints (Future)

The CRM is designed to support API endpoints for:
- Forms CRUD operations
- Entries management
- Reports generation
- User management

## Troubleshooting

### Database Connection Error
- Verify database credentials in `config/database.php`
- Ensure MySQL server is running
- Check database exists

### Permission Denied
- Set correct permissions on logs and uploads folders
- Check web server user ownership

### Blank Page
- Check error logs in `logs/error.log`
- Enable error reporting in `config/database.php`

## Support

For issues and feature requests, please contact the development team.

## License

This project is proprietary software. All rights reserved.

---

**Version:** 1.0.0  
**Last Updated:** 2024
