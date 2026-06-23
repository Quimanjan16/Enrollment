# School Enrollment Management System

A comprehensive PHP-based school enrollment, cashier, and assessment management system designed for Philippine K-12 schools (Grades 7-10). Built with vanilla PHP, MySQL, HTML5, and CSS3.

## 🎯 Features

### 4 User Roles
- **Admin**: System monitor, user management, full analytics
- **Cashier**: Payment processing, enrollment verification, exam eligibility
- **Assessment Personnel**: Scholarship verification, payment confirmation, compliance checks
- **Registrar**: Student profile creation, document management, additional fees

### Core Functionality
- ✅ Student enrollment management (new and continuing students)
- ✅ Dynamic payment scheduling (4 payments per semester with auto-splitting)
- ✅ Scholarship management with deductions and approvals
- ✅ Document verification (Form 137, Form 138, Report Card)
- ✅ Additional fees application with automatic payment distribution
- ✅ Real-time synchronization across all modules
- ✅ Exam eligibility tracking based on payments
- ✅ Complete activity audit log
- ✅ Philippine curriculum (Grade 7-10 subjects)

## 🛠️ System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP or equivalent local server
- Modern web browser (Chrome, Firefox, Safari, Edge)

## 📦 Installation Guide

### 1. Database Setup

1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Click "Import" tab
3. Select `school_enrollment.sql` file from the project root
4. Click "Import"

**Database Details:**
- Database Name: `school_enrollment`
- Username: `root`
- Password: (empty by default in XAMPP)

### 2. File Setup

1. Copy the entire project folder to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\school-enrollment
   ```

2. The folder structure should be:
   ```
   school-enrollment/
   ├── config/
   │   ├── Database.php
   │   ├── Auth.php
   │   └── Helpers.php
   ├── dashboard/
   │   ├── admin/
   │   ├── cashier/
   │   ├── assessment/
   │   └── registrar/
   ├── public/
   │   └── styles.css
   ├── login.php
   ├── logout.php
   ├── init.php
   ├── school_enrollment.sql
   └── README.md
   ```

### 3. Seed Demo Data

After importing the database schema, seed the demo users and sample data:

1. Open browser and navigate to:
   ```
   http://localhost/school-enrollment/seed-database.php
   ```

2. You'll see confirmation that demo users and sample students were created

**Demo Credentials Created:**
- **Admin**: `admin` / `admin123`
- **Cashier**: `cashier` / `cashier123`
- **Registrar**: `registrar` / `registrar123`
- **Assessment**: `assessment` / `assessment123`

### 4. Configure Database Connection (Optional)

Edit `config/Database.php` if using non-standard settings:

```php
private $host = 'localhost';
private $db_name = 'school_enrollment';
private $username = 'root';
private $password = '';
```

### 4. Start the System

1. Start XAMPP (Apache & MySQL)
2. Navigate to: `http://localhost/school-enrollment/login.php`

## 🔐 Default Login Credentials

```
Username: admin
Password: admin123
```

⚠️ **Important**: Change the default admin password immediately after first login!

## 📝 Usage Guide

### Admin Dashboard
- View system statistics and recent activities
- Create new users (Cashier, Assessment, Registrar)
- Monitor all enrollments and payments
- Manage scholarships and fees

### Cashier Dashboard
- Record student payments
- Register new students to the system
- Mark exam eligibility based on payment status
- View student payment history

### Registrar Dashboard
- Create new student profiles
- Upload and manage required documents (Form 137, 138, Report Card)
- Apply additional fees per grade level
- Verify student enrollment requirements

### Assessment Dashboard
- Approve/reject scholarship applications
- Verify payment records
- Track scholarship deductions
- Monitor student compliance

## 💳 Payment Schedule System

### Per Semester (4 Payments):
1. **Prelim** - Beginning of month
2. **Midterm** - Middle of month
3. **Pre-Final** - End of month
4. **Final** - Final week

### Dynamic Payment Distribution:
- If additional fees are added, they are automatically split among remaining unpaid installments
- Example: If Prelim is paid and ₱5,000 is added, the remaining ₱5,000 is split between Midterm, Pre-Final, and Final
- Scholarships are applied as deductions to net amount

## 📚 Philippine Curriculum Subjects

### Grades 7-10 Include:
- English
- Filipino
- Mathematics
- Science
- Araling Panlipunan (Social Studies)
- Physical Education
- MAPEH (Music, Arts, PE, Health)
- Technology and Livelihood Education (TLE)
- Edukasyon sa Pagpapahalaga ng Kalusugan at Kalikasan (Health/Environment)
- Information and Communication Technology (ICT)

## 🎨 Design Features

### Color Palette
- **Primary Purple**: #7c3aed
- **Light Purple**: #9b62fc
- **Soft Purple**: #ede9fe
- **Dark Gray**: #1e1b2e
- **Gray Scale**: #374151 to #f9fafb
- **Accent Colors**: Green, Orange, Red, Blue

### Typography
- Clean, modern sans-serif font stack
- Responsive design (Mobile, Tablet, Desktop)
- 8px spacing system for consistency
- Smooth transitions and hover effects

## 🔄 Data Synchronization

All changes reflect instantly across modules:
- Payment recorded → Assessment sees update → Cashier notifies student
- Scholarship approved → Payment amounts adjust → Assessment confirms
- Additional fee added → Payment schedule auto-distributes → All stakeholders updated

## 📊 Key Tables in Database

1. **users** - System user accounts
2. **students** - Student information
3. **enrollments** - Student enrollment records
4. **payment_schedules** - Payment installment schedule
5. **payments** - Payment transaction log
6. **scholarships** - Scholarship definitions
7. **student_scholarships** - Student scholarship applications
8. **documents** - Student documents (Form 137, 138, Report Card)
9. **subjects** - Philippine curriculum subjects
10. **student_subjects** - Subject enrollment
11. **additional_fees** - Extra charges
12. **exam_eligibility** - Exam readiness tracking
13. **activity_log** - Complete audit trail

## 🔒 Security Features

- Password hashing using BCrypt
- Session-based authentication
- Role-based access control (RBAC)
- Input sanitization and validation
- SQL injection prevention via prepared statements
- Complete activity logging for compliance

## 🐛 Troubleshooting

### Database Connection Error
- Ensure MySQL is running in XAMPP
- Check database credentials in `config/Database.php`
- Verify database `school_enrollment` exists

### Login Issues
- Clear browser cache and cookies
- Ensure session directory is writable
- Check PHP error logs

### Payment Synchronization Issues
- Verify `update_payment_schedules()` triggers in payment recording
- Check database permissions
- Review activity log for error traces

## 📞 Support

For issues or questions:
1. Check the activity log for error details
2. Review recent database changes
3. Verify user permissions for the action
4. Clear session and try again

## 📄 License

School Enrollment System v1.0
Built for Philippine Educational Institutions

---

**Last Updated**: 2024
**Version**: 1.0.0
**Status**: Production Ready
