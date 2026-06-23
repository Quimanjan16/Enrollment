# Project Structure & Architecture

## 📁 Directory Tree

```
school-enrollment/
│
├── 📄 README.md                 # Project overview and features
├── 📄 SETUP.md                  # Installation and setup guide
├── 📄 API_REFERENCE.md          # API documentation
├── 📄 PROJECT_STRUCTURE.md      # This file
├── 📄 school_enrollment.sql     # Database schema
│
├── 📁 config/                   # Configuration files
│   ├── Database.php             # Database connection
│   ├── Auth.php                 # Authentication & session management
│   └── Helpers.php              # Utility functions
│
├── 📁 public/                   # Public assets
│   └── styles.css               # Master stylesheet (860 lines)
│
├── 📁 dashboard/                # Dashboard controllers
│   ├── index.php                # Router to role-specific dashboards
│   │
│   ├── 📁 admin/                # Admin panel
│   │   ├── dashboard.php        # Admin dashboard view
│   │   ├── users.php            # User management
│   │   ├── students.php         # Student listing
│   │   ├── enrollments.php      # Enrollment management
│   │   ├── payments.php         # Payment tracking
│   │   ├── scholarships.php     # Scholarship management
│   │   └── activity.php         # Activity audit log
│   │
│   ├── 📁 cashier/              # Cashier operations
│   │   ├── dashboard.php        # Cashier dashboard
│   │   ├── new-enrollee.php     # New student enrollment
│   │   ├── students.php         # Student records
│   │   ├── exam-eligibility.php # Exam readiness check
│   │   ├── payment-records.php  # Payment history
│   │   ├── enroll-student.php   # Enrollment processing
│   │   ├── record-payment.php   # Payment entry form
│   │   └── view-student.php     # Student profile view
│   │
│   ├── 📁 registrar/            # Registrar operations
│   │   ├── dashboard.php        # Registrar dashboard
│   │   ├── new-student.php      # New student profile creation
│   │   ├── students.php         # Student management
│   │   ├── documents.php        # Document verification
│   │   ├── additional-fees.php  # Fee management
│   │   ├── edit-student.php     # Student profile editor
│   │   └── verify-document.php  # Document review
│   │
│   └── 📁 assessment/           # Assessment operations
│       ├── dashboard.php        # Assessment dashboard
│       ├── scholarships.php     # Scholarship approvals
│       ├── verify-payments.php  # Payment verification
│       ├── students.php         # Student compliance check
│       ├── verification-log.php # Verification history
│       └── approve-scholarship.php # Scholarship review
│
├── 📁 api/                      # API endpoints
│   ├── record-payment.php       # Payment recording
│   ├── create-enrollment.php    # Enrollment creation
│   ├── add-additional-fee.php   # Fee application
│   └── approve-scholarship.php  # Scholarship approval
│
├── 📄 init.php                  # Session initialization
├── 📄 index.php                 # Root router
├── 📄 login.php                 # Login page (200+ lines)
└── 📄 logout.php                # Logout handler
```

---

## 🏗️ Architecture Overview

### MVC-Inspired Architecture

```
┌─────────────────────────────────────┐
│         PRESENTATION LAYER          │
│  (HTML pages in /dashboard/*)        │
│  - User-facing interfaces            │
│  - Forms and data display            │
│  - Role-based views                  │
└────────────┬────────────────────────┘
             │
┌────────────▼────────────────────────┐
│         BUSINESS LOGIC               │
│  (PHP in /dashboard/ & /api/)       │
│  - Data validation                   │
│  - Business rules enforcement        │
│  - Transaction handling              │
└────────────┬────────────────────────┘
             │
┌────────────▼────────────────────────┐
│       DATA ACCESS LAYER              │
│  (Database interactions)             │
│  - Query execution                   │
│  - Transaction management            │
│  - Data persistence                  │
└────────────┬────────────────────────┘
             │
┌────────────▼────────────────────────┐
│      DATABASE LAYER                  │
│  (MySQL - school_enrollment)         │
│  - 13 core tables                    │
│  - Relational integrity              │
│  - Query optimization                │
└─────────────────────────────────────┘
```

---

## 🗄️ Database Schema (13 Tables)

### Core Entity Tables

#### 1. **users**
- System user accounts (Admin, Cashier, Assessment, Registrar)
- Columns: id, username, password, email, full_name, role, status
- Indexes: role, status

#### 2. **students**
- Student information
- Columns: id, first_name, last_name, middle_name, date_of_birth, gender, contact_number, email, address, status
- Indexes: status, name (last_name, first_name)

#### 3. **enrollments**
- Student enrollment records per semester
- Columns: id, student_id, academic_year, semester, grade_level, enrollment_status, total_tuition, additional_fees, scholarship_amount, net_amount
- Unique Key: (student_id, academic_year, semester)

#### 4. **payment_schedules**
- 4 payment installments per enrollment
- Columns: id, enrollment_id, payment_type (Prelim/Midterm/Pre-Final/Final), amount_due, amount_paid, payment_status
- Unique Key: (enrollment_id, payment_type)

#### 5. **payments**
- Payment transaction log
- Columns: id, payment_schedule_id, amount_paid, payment_method, reference_number, paid_by (user_id), notes

#### 6. **scholarships**
- Scholarship program definitions
- Columns: id, scholarship_name, scholarship_type, discount_percentage, discount_amount, description, status

#### 7. **student_scholarships**
- Student scholarship applications and approvals
- Columns: id, student_id, scholarship_id, enrollment_id, approved_amount, status, approved_by (user_id), approved_date

#### 8. **documents**
- Required enrollment documents
- Columns: id, student_id, document_type (Form 137/138/Report Card), file_path, status, verified_by (user_id)

#### 9. **subjects**
- Philippine curriculum subjects (Grades 7-10)
- Columns: id, subject_code, subject_name, grade_level, description, status
- 40 subjects total (10 per grade)

#### 10. **student_subjects**
- Student-subject enrollment
- Columns: id, enrollment_id, subject_id, grade, status
- Unique Key: (enrollment_id, subject_id)

#### 11. **additional_fees**
- Extra charges applied to enrollments
- Columns: id, enrollment_id, fee_description, fee_amount, applicable_grade, created_by (user_id)

#### 12. **exam_eligibility**
- Exam readiness tracking
- Columns: id, enrollment_id, exam_period (Prelim/Midterm/Pre-Final/Final), is_eligible, checked_by (user_id)

#### 13. **activity_log**
- Complete audit trail
- Columns: id, user_id, action, entity_type, entity_id, description, created_at
- Indexes: created_at, user_id

---

## 🔐 Security Architecture

### Authentication Flow
```
Login Form
    ↓
Auth::login() [Validate credentials]
    ↓
Password verification [BCrypt]
    ↓
Session creation [$_SESSION variables]
    ↓
Redirect to Dashboard
```

### Authorization Model (RBAC)
```
ADMIN → All modules + User Management
CASHIER → Payment + Enrollment + Exam Eligibility
ASSESSMENT → Payment Verification + Scholarships
REGISTRAR → Student Profiles + Documents + Fees
```

### Input Protection
```
$_POST data → sanitize() → HTML escape → SQL prepared stmt
```

---

## 🔄 Data Flow Examples

### Example 1: Payment Recording Flow

```
Cashier records payment
    ↓
/api/record-payment.php
    ↓
BEGIN TRANSACTION
    ├─ Validate payment schedule exists
    ├─ Update payment_schedules (amount_paid, status)
    ├─ INSERT into payments (transaction log)
    ├─ Log activity
    └─ COMMIT
    ↓
Response: { success: true, payment_id: 42 }
    ↓
Assessment sees updated payment status
Exam eligibility auto-updates
Dashboard statistics refresh
```

### Example 2: Additional Fee Distribution Flow

```
Registrar adds ₱500 fee to Grade 8 students
    ↓
/api/add-additional-fee.php
    ↓
BEGIN TRANSACTION
    ├─ Get unpaid payment schedules
    ├─ Calculate split: ₱500 ÷ 3 = ₱166.67 each
    ├─ Update Midterm, Pre-Final, Final amounts
    ├─ Recalculate enrollment net_amount
    └─ COMMIT
    ↓
All 3 remaining installments automatically increase
Students see updated payment requirements
Cashier sees new amounts
Assessment verifies correctness
```

### Example 3: Scholarship Approval Flow

```
Assessment approves ₱5,000 scholarship
    ↓
/api/approve-scholarship.php
    ↓
BEGIN TRANSACTION
    ├─ Update scholarship status to "active"
    ├─ Calculate deduction per remaining payment
    ├─ Update enrollment scholarship_amount
    ├─ Reduce all unpaid payment amounts
    └─ COMMIT
    ↓
Student's net tuition reduced
Payment schedules automatically adjusted
Cashier sees lower payment requirements
Dashboard reflects changes
```

---

## 🎨 UI/UX Architecture

### Color System (8px Grid)
```
PRIMARY:    #7c3aed (Purple)
SECONDARY:  #9b62fc (Light Purple)
NEUTRAL:    #1e1b2e → #f9fafb (Gray Scale)
ACCENT:     Green (#10b981), Orange (#f59e0b), Red (#ef4444), Blue (#3b82f6)
```

### Component Library
```
Forms
├─ Input fields (text, email, password, date, number)
├─ Select dropdowns
├─ Text areas
└─ Form groups with validation

Tables
├─ Responsive table wrapper
├─ Status badges
├─ Action buttons
└─ Sortable headers (future)

Cards
├─ Stat cards (dashboard KPIs)
├─ Data cards (content panels)
└─ Action cards (with footers)

Navigation
├─ Sidebar (fixed, collapsible)
├─ Header (with user menu)
└─ Breadcrumbs (future)

Alerts
├─ Success (green)
├─ Error (red)
├─ Warning (orange)
└─ Info (blue)
```

### Responsive Breakpoints
```
Desktop:  > 1024px (3-column layouts)
Tablet:   768px - 1024px (2-column layouts)
Mobile:   < 768px (1-column stack)
```

---

## 📊 Payment Distribution Algorithm

```
Total Tuition: ₱20,000
Split into: 4 installments = ₱5,000 each

Timeline:
┌─────────────┬──────────────┬──────────────┬──────────────┐
│   PRELIM    │   MIDTERM    │  PRE-FINAL   │    FINAL     │
│  ₱5,000     │   ₱5,000     │   ₱5,000     │   ₱5,000     │
│  Paid ✓     │  Pending     │   Pending    │   Pending    │
└─────────────┴──────────────┴──────────────┴──────────────┘

If ₱500 fee added after Prelim is paid:
┌─────────────┬──────────────┬──────────────┬──────────────┐
│   PRELIM    │   MIDTERM    │  PRE-FINAL   │    FINAL     │
│  ₱5,000     │  ₱5,166.67   │  ₱5,166.67   │  ₱5,166.66   │
│  Paid ✓     │  +₱166.67    │  +₱166.67    │  +₱166.66    │
└─────────────┴──────────────┴──────────────┴──────────────┘

If ₱5,000 scholarship approved:
┌─────────────┬──────────────┬──────────────┬──────────────┐
│   PRELIM    │   MIDTERM    │  PRE-FINAL   │    FINAL     │
│  ₱5,000     │  ₱4,388.89   │  ₱4,388.89   │  ₱4,388.89   │
│  Paid ✓     │  -₱1,666.67  │  -₱1,666.67  │  -₱1,666.67  │
└─────────────┴──────────────┴──────────────┴──────────────┘
```

---

## 🔄 Synchronization Points

Real-time updates occur when:

1. **Payment Recorded**
   - Payment schedule status updates
   - Enrollment total paid updates
   - Exam eligibility recalculates
   - Activity log entry created

2. **Enrollment Created**
   - Student status changes (new → continuing)
   - 4 payment schedules generated
   - Subject assignments created
   - Activity log entry created

3. **Fee Added**
   - Enrollment additional_fees increases
   - Net amount recalculated
   - Unpaid schedules redistributed
   - Activity log entry created

4. **Scholarship Approved**
   - Scholarship status changes
   - Student scholarship marked active
   - Enrollment scholarship_amount increases
   - All payment schedules adjusted
   - Activity log entry created

---

## 📈 Performance Considerations

### Query Optimization
- Indexes on frequently searched columns
- JOIN optimizations for multi-table queries
- Pagination for large result sets (future)

### Caching Strategies (Future)
- User session caching
- Dashboard statistics caching
- Payment schedule caching

### Database Maintenance
- Regular backups
- Index optimization
- Old log cleanup

---

## 🚀 Deployment Checklist

- [ ] Database backup created
- [ ] Config credentials updated
- [ ] File permissions set correctly
- [ ] HTTPS certificate installed (production)
- [ ] Admin password changed
- [ ] All user accounts created
- [ ] Sample data imported (optional)
- [ ] Email notifications configured (future)
- [ ] Backup schedule established
- [ ] Monitor logs for errors

---

## 📚 File Size Reference

| File | Lines | Size | Purpose |
|------|-------|------|---------|
| styles.css | 860 | 35KB | Master stylesheet |
| login.php | 200 | 8KB | Authentication UI |
| school_enrollment.sql | 280 | 12KB | Database schema |
| API endpoints | 400+ | 16KB | Business logic |
| Dashboards | 1000+ | 40KB | UI controllers |

**Total Project Size**: ~150KB

---

## 🔗 Relationships Map

```
users ────────┐
              │
students ─────┼─── enrollments ───┬─── payment_schedules ─── payments
              │                   │
documents ────┤                   └─── additional_fees
              │                   │
              │                   └─── student_subjects ─── subjects
              │
student_scholarships ─── scholarships
              │
activity_log ─┘

exam_eligibility ─── enrollments
```

---

**Version**: 1.0.0
**Last Updated**: 2024
**Status**: Production Ready
