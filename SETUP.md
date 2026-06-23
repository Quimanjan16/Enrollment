# 🚀 Complete Setup & Installation Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [XAMPP Setup](#xampp-setup)
3. [Database Setup](#database-setup)
4. [Project Installation](#project-installation)
5. [Configuration](#configuration)
6. [First Login](#first-login)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### System Requirements
- **Windows, macOS, or Linux**
- **Disk Space**: Minimum 500MB
- **RAM**: 4GB recommended
- **Modern Browser**: Chrome, Firefox, Safari, or Edge (latest version)

### Required Software
- **XAMPP** (Apache + MySQL + PHP): https://www.apachefriends.org/download.html
  - PHP 7.4 or higher
  - MySQL 5.7 or higher

---

## XAMPP Setup

### Step 1: Download XAMPP
1. Visit https://www.apachefriends.org/download.html
2. Choose version for your operating system
3. Download the installer

### Step 2: Install XAMPP
**Windows:**
1. Run the downloaded `.exe` file
2. Click "Next" through the installation wizard
3. Choose installation location (default: `C:\xampp`)
4. Accept the defaults and click "Install"
5. Finish the installation

**macOS:**
1. Open the downloaded `.dmg` file
2. Drag XAMPP icon to Applications folder
3. Wait for copying to complete

**Linux:**
1. Extract the downloaded file:
   ```bash
   tar xvfz xampp-linux-*.tar.gz -C /opt/
   ```

### Step 3: Start XAMPP
**Windows:**
- Open XAMPP Control Panel from Start Menu
- Click "Start" for Apache and MySQL

**macOS:**
- Open XAMPP folder in Applications
- Right-click XAMPP and select "Open"
- Start Apache and MySQL

**Linux:**
```bash
sudo /opt/lampp/lampp start
```

### Step 4: Verify Installation
1. Open your browser
2. Navigate to `http://localhost`
3. You should see the XAMPP welcome page

---

## Database Setup

### Step 1: Access phpMyAdmin
1. Open your browser
2. Go to `http://localhost/phpmyadmin`
3. Login with:
   - **Username**: `root`
   - **Password**: (leave empty)

### Step 2: Import Database
1. Click the **"Import"** tab at the top
2. Click **"Choose File"** button
3. Navigate to and select `school_enrollment.sql` from the project folder
4. Click **"Import"** button
5. Wait for the import to complete

**Success Message:**
```
The following queries have been executed successfully: ... 
Database: school_enrollment - Object count = 280
```

### Step 3: Verify Database
1. In left sidebar, click on **"school_enrollment"**
2. You should see 13 tables listed:
   - users
   - students
   - enrollments
   - payment_schedules
   - payments
   - scholarships
   - student_scholarships
   - documents
   - subjects
   - student_subjects
   - additional_fees
   - exam_eligibility
   - activity_log

---

## Project Installation

### Step 1: Download Project Files
1. Extract/copy the entire project folder
2. Navigate to your XAMPP `htdocs` directory:
   - **Windows**: `C:\xampp\htdocs\`
   - **macOS**: `/Applications/xampp/htdocs/`
   - **Linux**: `/opt/lampp/htdocs/`

3. Paste the entire project folder there
4. Your structure should be:
   ```
   htdocs/
   └── school-enrollment/
       ├── config/
       ├── dashboard/
       ├── api/
       ├── public/
       ├── login.php
       ├── logout.php
       ├── init.php
       ├── index.php
       ├── school_enrollment.sql
       ├── README.md
       └── SETUP.md
   ```

### Step 2: Set Folder Permissions
**Windows:** 
- Right-click folder → Properties → Security → Edit
- Select SYSTEM and give Full Control

**macOS/Linux:**
```bash
chmod 755 /path/to/school-enrollment
chmod 755 /path/to/school-enrollment/config
```

---

## Configuration

### Step 1: Update Database Credentials (if needed)
1. Open `config/Database.php`
2. If you're using non-default MySQL settings, update:
   ```php
   private $host = 'localhost';      // MySQL host
   private $db_name = 'school_enrollment'; // Database name
   private $username = 'root';        // MySQL username
   private $password = '';            // MySQL password
   ```

### Step 2: Verify PHP Settings
1. Create a new file `test.php` in your project root:
   ```php
   <?php
   phpinfo();
   ?>
   ```

2. Navigate to `http://localhost/school-enrollment/test.php`
3. Verify:
   - PHP version is 7.4 or higher
   - MySQL extension is enabled
   - Session support is enabled

4. Delete `test.php` after verification

---

## First Login

### Step 1: Access the System
1. Open your browser
2. Navigate to: `http://localhost/school-enrollment/`
3. You'll be redirected to the login page

### Step 2: Login with Default Credentials
```
Username: admin
Password: admin123
```

### Step 3: Create Additional Users
1. As Admin, go to **Manage Users**
2. Click **"Add New User"**
3. Create accounts for:
   - **Cashier** - Handles payments
   - **Registrar** - Manages student profiles
   - **Assessment** - Verifies payments and scholarships

### Step 4: Change Default Admin Password
1. **IMPORTANT**: Change the default admin password immediately
2. You can do this by:
   - Editing through admin panel
   - Or directly in phpMyAdmin (hash the new password with bcrypt)

---

## System Walkthrough

### For Administrators
1. Monitor all system activities
2. View statistics and reports
3. Manage user accounts
4. Access all modules

### For Registrar
1. Create new student profiles
2. Verify and approve documents (Form 137, 138, Report Card)
3. Add additional fees by grade level
4. Manage student requirements

### For Cashier
1. Register new students
2. Record student payments
3. Mark exam eligibility
4. Generate payment reports

### For Assessment Personnel
1. Verify payment records
2. Approve/reject scholarship applications
3. Apply scholarship deductions
4. Monitor compliance

---

## Troubleshooting

### Issue: "Cannot connect to database"
**Solution:**
1. Ensure MySQL is running in XAMPP Control Panel
2. Check `config/Database.php` credentials
3. Verify database `school_enrollment` exists in phpMyAdmin

### Issue: "Login fails with correct credentials"
**Solution:**
1. Clear browser cookies and cache
2. Check that sessions directory is writable
3. Verify PHP session settings in `php.ini`

### Issue: "404 Page Not Found"
**Solution:**
1. Verify project folder is in `htdocs`
2. Check URL format: `http://localhost/school-enrollment/`
3. Ensure Apache is running

### Issue: "Blank page or white screen"
**Solution:**
1. Check PHP error log: `C:\xampp\apache\logs\error.log`
2. Enable error reporting in `init.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
3. Check `config/Database.php` for typos

### Issue: "CSS not loading (unstyled pages)"
**Solution:**
1. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
2. Check file permissions on `public/styles.css`
3. Verify file path in HTML

### Issue: "Payment split not working correctly"
**Solution:**
1. Verify `additional_fees` API is being called
2. Check database triggers are installed
3. Review activity log for errors

### Issue: "Scholarship deduction not applied"
**Solution:**
1. Ensure scholarship is approved
2. Check payment schedules updated in database
3. Verify enrollment net_amount recalculated

---

## Database Backup

### Automatic Backup
Regular backups are recommended using phpMyAdmin:
1. Open phpMyAdmin
2. Select `school_enrollment` database
3. Click "Export"
4. Choose "SQL" format
5. Click "Go" to download backup

### Command Line Backup (Linux/macOS)
```bash
mysqldump -u root school_enrollment > backup.sql
```

### Restore from Backup
1. Open phpMyAdmin
2. Click "Import"
3. Select backup file
4. Click "Import"

---

## Performance Tips

### 1. Regular Maintenance
- Clear old activity logs (keep last 6 months)
- Archive completed enrollments
- Optimize database tables

### 2. Security Best Practices
- Change default passwords
- Use strong admin credentials
- Enable HTTPS in production
- Regular backups

### 3. Optimization
- Clear browser cache regularly
- Enable compression in Apache
- Use indexed queries
- Monitor database size

---

## Next Steps

1. ✅ System is now installed and ready to use
2. 📚 Read the README.md for feature overview
3. 👥 Create user accounts for staff
4. 🎓 Start registering students
5. 💳 Set up payment schedules
6. 📋 Configure scholarships

---

## Support Resources

- **Official Website**: https://www.apachefriends.org/
- **MySQL Documentation**: https://dev.mysql.com/doc/
- **PHP Documentation**: https://www.php.net/manual/
- **XAMPP FAQ**: https://www.apachefriends.org/faq.html

---

## Important Security Note

⚠️ **This system is designed for local/intranet use in XAMPP**

For production deployment:
1. Move to a professional hosting provider
2. Enable HTTPS (SSL certificate)
3. Use environment variables for sensitive data
4. Implement additional security measures
5. Regular security audits
6. Comply with data protection regulations

---

**Setup Date**: 2024
**Version**: 1.0.0
**Last Updated**: 2024
