# Demo Account Login Fix

## The Problem
The database schema was created but **no users were added to the database**. The login system uses BCrypt password hashing, so plain passwords don't work.

## The Solution
A new **seed script** has been created that automatically adds all demo users with properly hashed passwords.

---

## Quick Fix (3 Steps)

### 1. Make Sure Database is Imported
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Import `school_enrollment.sql` if not done yet
- Database should have 13 tables

### 2. Run the Seed Script
- Open browser
- Go to: `http://localhost/school-enrollment/seed-database.php`
- Wait 2-3 seconds for confirmation
- You should see green checkmarks confirming users were created

### 3. Login
- Go to: `http://localhost/school-enrollment/login.php`
- Use: `admin` / `admin123`
- Should redirect to dashboard ✅

---

## What the Seed Script Does

Creates 4 demo user accounts with properly hashed passwords:

```
Admin Account:
  Username: admin
  Password: admin123
  Role: admin (full system access)

Cashier Account:
  Username: cashier
  Password: cashier123
  Role: cashier (payment processing)

Registrar Account:
  Username: registrar
  Password: registrar123
  Role: registrar (student profiles)

Assessment Account:
  Username: assessment
  Password: assessment123
  Role: assessment (verification)
```

Also creates 5 sample students for testing.

---

## Verify It Worked

**In phpMyAdmin:**
1. Click on `school_enrollment` database
2. Click on `users` table
3. You should see 4 rows:
   - admin
   - cashier
   - registrar
   - assessment

**Passwords should be:**
- Long hashed strings starting with `$2y$10$`
- NOT plain text
- NOT visible

---

## Files Added/Modified

**New File:**
- `seed-database.php` - Creates demo users and sample data

**Modified Files:**
- `README.md` - Added seed script instructions
- `QUICKSTART.md` - Updated with seed step
- `TROUBLESHOOTING.md` - Full troubleshooting guide

---

## If Seed Script Doesn't Work

**Check:**
1. Database was imported: `http://localhost/phpmyadmin`
2. MySQL is running (XAMPP Control Panel)
3. No errors displayed when running seed script

**If still failing:**
1. Check `config/Database.php` database credentials
2. Make sure you're in correct folder: `C:\xampp\htdocs\school-enrollment\`
3. Try importing database fresh and running seed script again

---

## Next Steps

After successful login:
1. Explore Admin Dashboard
2. Switch users (logout and login as cashier/registrar/assessment)
3. Create enrollments
4. Process payments
5. Verify real-time synchronization

---

**Status: ✅ Fixed and Ready to Use**

Run seed script now: `http://localhost/school-enrollment/seed-database.php`
