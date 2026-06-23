# Troubleshooting Guide

## Issue: Demo Account Not Working

### Problem
Login fails with "Invalid username or password" when using `admin / admin123`

### Solution

**Step 1: Check if Database is Imported**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Check if `school_enrollment` database exists
3. Check if `users` table exists in the database
4. If not, import `school_enrollment.sql` first

**Step 2: Run Seed Script**
1. Navigate to: `http://localhost/school-enrollment/seed-database.php`
2. Wait for the confirmation message
3. You should see:
   ```
   ✓ Created user: admin (admin)
   ✓ Created user: cashier (cashier)
   ✓ Created user: registrar (registrar)
   ✓ Created user: assessment (assessment)
   ```

**Step 3: Verify Users in Database**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on `school_enrollment` database
3. Click on `users` table
4. Should see 4 rows with: admin, cashier, registrar, assessment
5. Passwords should be long hashed strings (not plain text)

**Step 4: Try Login**
1. Go to: `http://localhost/school-enrollment/login.php`
2. Enter: `admin` / `admin123`
3. Should redirect to dashboard

---

## Common Issues & Fixes

### "Can't connect to database"
**Fix:**
- Make sure MySQL is running in XAMPP
- Check `config/Database.php` has correct credentials
- Default: host=localhost, user=root, password=(empty)

### "404 Page not found"
**Fix:**
- Check project is in correct folder: `C:\xampp\htdocs\school-enrollment\`
- Clear browser cache
- Restart XAMPP

### "Seed script shows database connection failed"
**Fix:**
- Make sure database is imported first
- Check MySQL is running
- Try importing schema again

### "After login, shows blank page"
**Fix:**
- Check browser console for errors (F12)
- Make sure all files exist in correct folders
- Try clearing browser cache and session

### "Can only login with one account"
**Fix:**
- Make sure seed script was run (creates all 4 accounts)
- Check all 4 users exist in phpMyAdmin
- Try restarting browser or clearing cookies

---

## Verification Checklist

Before attempting to login, verify:

- [ ] XAMPP is running (Apache + MySQL green)
- [ ] `school_enrollment.sql` is imported
- [ ] Database `school_enrollment` exists in phpMyAdmin
- [ ] Table `users` exists with 4+ rows
- [ ] Seed script ran successfully (`seed-database.php`)
- [ ] Project folder is at `C:\xampp\htdocs\school-enrollment\`
- [ ] All files exist with correct structure
- [ ] Browser can access `http://localhost/school-enrollment/`

---

## Demo Credentials

After running seed script, these credentials should work:

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Cashier | cashier | cashier123 |
| Registrar | registrar | registrar123 |
| Assessment | assessment | assessment123 |

---

## Manual Database Setup (Alternative)

If seed script doesn't work, manually insert demo user:

1. Open phpMyAdmin
2. Go to `school_enrollment` database
3. Click `SQL` tab
4. Paste this SQL:

```sql
INSERT INTO users (username, password, email, full_name, role, status) 
VALUES (
    'admin',
    '$2y$10$YIjlrBxxx...', -- This is bcrypt hash of 'admin123'
    'admin@school.com',
    'System Administrator',
    'admin',
    'active'
);
```

Or use this command-line:

```php
<?php
echo password_hash('admin123', PASSWORD_BCRYPT);
?>
```

Copy the output hash and use in SQL above.

---

## Getting Help

If issues persist:

1. Check all console errors (F12 in browser)
2. Check XAMPP error logs
3. Verify all file paths are correct
4. Make sure MySQL credentials match `config/Database.php`
5. Try fresh import of database schema
6. Run seed script again

---

## Key Files to Check

- `config/Database.php` - Database connection settings
- `config/Auth.php` - Login authentication logic
- `login.php` - Login page
- `school_enrollment.sql` - Database schema
- `seed-database.php` - Demo data script

All should be in project root or `config/` folder.
