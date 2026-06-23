# ⚡ Quick Start Guide (5 Minutes)

## Prerequisites
- XAMPP installed and running (Apache & MySQL)
- Project files in `C:\xampp\htdocs\school-enrollment\` (Windows)

---

## 🚀 Step 1: Import Database (1 minute)

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Select `school_enrollment.sql`
4. Click **Import**
5. ✅ Done!

---

## 🌱 Step 2: Seed Demo Data (30 seconds)

1. Open browser
2. Go to: `http://localhost/school-enrollment/seed-database.php`
3. Wait for confirmation message
4. ✅ Demo users and sample students created!

---

## 🔑 Step 3: Access System (30 seconds)

1. Open browser
2. Go to: `http://localhost/school-enrollment/`
3. Login with:
   ```
   Username: admin
   Password: admin123
   ```

---

## 👥 Step 4: Demo Accounts Available

Already created and ready to use:
```
Admin:       admin / admin123
Cashier:     cashier / cashier123
Registrar:   registrar / registrar123
Assessment:  assessment / assessment123
```

Try logging in with each role to explore different dashboards!

---

## 🎓 Step 5: Explore with Sample Students

Sample students are pre-created in the system:
- Juan Reyes (Grade 7)
- Maria Santos (Grade 8)
- Carlos Gonzales (Grade 9)
- Anna Lopez (Grade 10)
- Roberto Fernandez (New enrollee)

As Cashier, you can:
1. View student profiles
2. Create enrollments
3. Record payments

---

## 💳 Step 6: Process First Enrollment (Optional)

As Cashier:
1. Go to **New Enrollees**
2. Click student name → **Enroll**
3. Select Grade Level (7-10)
4. Enter Tuition Amount (e.g., 20000)
5. Click **Create Enrollment**

✅ System automatically creates 4 payment schedules!

---

## 💰 Step 6: Record Payment (30 seconds)

As Cashier:
1. Go to **Payment Records**
2. Find pending payment
3. Click **Record Payment**
4. Enter:
   - Amount (e.g., 5000)
   - Method (Cash/Check/etc)
   - Reference (optional)
5. Click **Save**

✅ Payment status updates to "partial" or "paid"!

---

## 📊 Dashboard Overview

### Admin Dashboard
- System statistics
- Recent activities
- User management link

### Cashier Dashboard
- New enrollees (waiting)
- Pending payments (to collect)
- Today's collections (revenue)

### Registrar Dashboard
- New students (need profiles)
- Pending documents (to verify)
- Additional fees (to apply)

### Assessment Dashboard
- Pending scholarships (to approve)
- Payment records (to verify)
- Active scholarships (tracking)

---

## 🧪 Test Scenarios

### Scenario 1: Full Payment Process
```
1. Register student (Registrar)
2. Create enrollment (Cashier)
3. Record Prelim payment (Cashier)
4. Record Midterm payment (Cashier)
5. Student is 50% paid
```

### Scenario 2: Additional Fees
```
1. Student has ₱20,000 tuition
2. Prelim (₱5,000) is paid
3. Add ₱500 sports fee (Registrar)
4. Remaining 3 payments increase by ₱166.67 each
```

### Scenario 3: Scholarship Application
```
1. Create new student
2. Request scholarship (future: student portal)
3. Assessment reviews & approves
4. ₱5,000 deduction applied to unpaid installments
```

---

## 🔧 Common Tasks

### How to Change Admin Password
1. Login as Admin
2. Go to Manage Users
3. Click Edit on admin user
4. Enter new password
5. Save

### How to Add Multiple Users
1. Manage Users → Add New User (repeat)
2. Or import from CSV (future feature)

### How to View Payment History
1. As Cashier: Payment Records
2. As Assessment: Verify Payments
3. As Admin: Full system view

### How to Apply Scholarship
1. Assessment → Scholarships
2. Find pending application
3. Click Review → Approve
4. Deduction applies automatically

### How to Generate Reports
1. Admin Dashboard shows summary
2. Click "View All" on any card
3. Export to CSV (future feature)

---

## 📋 Key Features to Try

- ✅ Payment splitting (add fee, see it distribute)
- ✅ Scholarship deduction (approve, see amounts change)
- ✅ Exam eligibility (mark as paid, unlock exam)
- ✅ Activity log (track all changes)
- ✅ Real-time sync (change appears everywhere)

---

## ⚠️ Important Notes

1. **Default Password**: Change immediately in production!
2. **Backup Database**: `phpMyAdmin → Export` before major changes
3. **Clear Cache**: Browser cache can show old data
4. **Check Logs**: Check activity log if something seems wrong
5. **Verify Amounts**: Always confirm math on payment splits

---

## 🆘 Troubleshooting

| Issue | Solution |
|-------|----------|
| 404 Error | Check URL, ensure files in htdocs |
| Login fails | Clear cookies, check default credentials |
| Database error | Ensure MySQL running, check credentials |
| Styling broken | Clear browser cache (Ctrl+F5) |
| Payment not updating | Refresh page or check activity log |

---

## 📚 Next Steps

1. ✅ **Complete this quickstart**
2. 📖 Read **README.md** for features
3. 📐 Review **PROJECT_STRUCTURE.md** for architecture
4. 🔌 Check **API_REFERENCE.md** for integrations
5. 🛠️ See **SETUP.md** for advanced configuration

---

## 🎓 Learning Path

**Beginner** (Today)
- Understand 4 user roles
- Create student & enroll
- Record payments

**Intermediate** (Day 2-3)
- Add scholarships & fees
- Verify payments
- Manage exam eligibility

**Advanced** (Week 1)
- Review activity logs
- Customize forms
- Create reports
- Integrate with systems

---

## 📞 Quick Help

**Can't find something?**
- Check sidebar navigation (left side)
- Look for "View All" buttons to access full lists
- Admin dashboard shows everything

**Not sure what to do?**
- Read instructions on each page
- Follow the role-specific workflow
- Check README.md or SETUP.md

**Something went wrong?**
1. Check activity log for errors
2. Try logging out and back in
3. Restart XAMPP (Apache & MySQL)
4. Clear browser cache

---

## ✨ Pro Tips

1. **Batch Operations**: Create multiple users at once (repeat step 3)
2. **Test Data**: Create test students in different grades (7-10)
3. **Payment Tracking**: Use reference numbers for easy lookup
4. **Scholarship Groups**: Create scholarships per grade level
5. **Activity Audit**: Review activity log daily for compliance

---

## 🎯 Success Checklist

- [ ] Database imported
- [ ] Logged in with admin account
- [ ] Created 3+ user accounts
- [ ] Registered test student
- [ ] Created test enrollment
- [ ] Recorded test payment
- [ ] Explored all 4 dashboards
- [ ] Changed admin password
- [ ] Backed up database

**You're ready to go! 🚀**

---

**Version**: 1.0.0
**Estimated Time**: 5 minutes
**Last Updated**: 2024
