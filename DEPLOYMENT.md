# DEPLOYMENT CHECKLIST - Lista e Kontrollit për Vendosje

## 📋 Pre-Deployment / Para Vendosjes

### 1. Server Requirements Check
- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ installed
- [ ] Apache/Nginx configured
- [ ] mod_rewrite enabled (if using Apache)
- [ ] PHP extensions: mysqli, session, json

### 2. Download and Extract
- [ ] Download school_management folder
- [ ] Extract to web server directory
  - XAMPP/WAMP: `C:\xampp\htdocs\school_management\`
  - Linux: `/var/www/html/school_management/`
  - macOS: `/Applications/XAMPP/htdocs/school_management/`

### 3. Database Setup
```sql
-- Step 1: Create database
CREATE DATABASE school_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Step 2: Import schema
SOURCE /path/to/database_schema.sql;

-- Step 3: Verify tables
USE school_management;
SHOW TABLES;
```

Expected tables:
- users
- students
- teachers
- parents
- parent_student
- classes
- subjects
- class_subjects
- schedules
- grades
- absences
- academic_years
- semesters
- notifications
- activity_logs

### 4. Configure Database Connection
Edit: `config/database.php`

```php
define('DB_HOST', 'localhost');        // Change if needed
define('DB_USER', 'your_db_username'); // Change this
define('DB_PASS', 'your_db_password'); // Change this
define('DB_NAME', 'school_management');
```

### 5. File Permissions (Linux/Mac only)
```bash
chmod 755 school_management/
chmod 644 school_management/*.php
chmod 755 school_management/config/
chmod 644 school_management/config/*.php
```

---

## 🚀 Initial Setup / Konfigurimi Fillestar

### 1. Access the System
Open browser: `http://localhost/school_management/`

### 2. First Login (IMPORTANT!)
**Default Admin Credentials:**
- Username: `superadmin`
- Email: `admin@school.com`
- Password: `password`

⚠️ **CRITICAL: Change this password immediately!**

### 3. Change Admin Password
1. Login as admin
2. Go to Profile
3. Change password to something secure
4. Update email if needed

### 4. Create Academic Year
1. Go to "Viti Akademik"
2. Click "Shto Vit të Ri"
3. Enter: 2024-2025
4. Start Date: September 1, 2024
5. End Date: June 30, 2025
6. Set as Active: Yes

### 5. Create Semesters
**Semester 1:**
- Name: Semestri 1
- Start: September 1, 2024
- End: January 31, 2025
- Active: Yes

**Semester 2:**
- Name: Semestri 2  
- Start: February 1, 2025
- End: June 30, 2025
- Active: No (activate later)

### 6. Add Subjects (Sample provided, add more as needed)
The system comes with 10 default subjects:
- Matematikë (MATH)
- Gjuhë Shqipe (ALB)
- Anglisht (ENG)
- Fizikë (PHY)
- Kimi (CHEM)
- Biologji (BIO)
- Histori (HIST)
- Gjeografi (GEO)
- Edukim Fizik (PE)
- TIK (ICT)

Add more from: "Lëndët" → "Shto Lëndë"

### 7. Create Classes
Example classes to create:
- Klasa 10A
- Klasa 10B
- Klasa 11A
- Klasa 11B
- Klasa 12A

Go to: "Klasat" → "Shto Klasë"

---

## 👥 Adding Users / Shtimi i Përdoruesve

### Adding Teachers
1. Go to "Mësuesit" or "Përdoruesit"
2. Click "Shto Mësues"
3. Fill in:
   - Full Name
   - Email
   - Password
   - Phone
   - Specialization
   - Hire Date
4. Save

**Teacher Login:**
- Email + Password

### Adding Students
1. Go to "Studentët" or "Përdoruesit"
2. Click "Shto Student"
3. Fill in:
   - Full Name
   - Email (optional but recommended)
   - Class Assignment
   - Date of Birth
   - Phone
   - Address
4. Save
5. **Note the Student ID** (auto-generated: STU000001)

**Student Login:**
- Full Name + Student ID

### Adding Parents
1. Go to "Prindërit" or "Përdoruesit"
2. Click "Shto Prind"
3. Fill in:
   - Full Name
   - Email
   - Password
   - Phone
   - Address
   - **Select Children** (from dropdown)
4. Save

**Parent Login:**
- Email + Password + Child's Full Name

---

## 📚 Assigning Teachers to Classes

### Method 1: Through Class Subjects
1. Go to "Lëndët"
2. Select a Class
3. Click "Assign Teacher"
4. Choose Subject and Teacher
5. Select Semester
6. Save

### Method 2: Through Schedule
1. Go to "Orari"
2. Create schedule entries
3. Automatically assigns teacher to class-subject

---

## 📅 Creating Schedule / Krijimi i Orarit

For each class:
1. Go to "Orari"
2. Select Class
3. Add schedule for each day:
   - Day of week
   - Subject
   - Teacher (auto-filled if assigned)
   - Start time
   - End time
   - Room number
4. Repeat for all subjects and days

Example schedule entry:
- Monday, Math, 08:00-09:00, Room 101
- Monday, Albanian, 09:00-10:00, Room 101
- etc.

---

## ✅ Testing the System / Testimi i Sistemit

### Test as Admin
- [ ] Create users
- [ ] Create classes
- [ ] Assign teachers to subjects
- [ ] Create schedules
- [ ] View reports

### Test as Teacher
- [ ] Login with teacher credentials
- [ ] View assigned classes
- [ ] Mark absences for a student
- [ ] Add grades (all 3 types)
- [ ] Check dashboard statistics

### Test as Student
- [ ] Login with full name + student ID
- [ ] View grades
- [ ] Check absence calendar
- [ ] View schedule

### Test as Parent
- [ ] Login with email + password + child name
- [ ] View child's grades
- [ ] Check child's absences
- [ ] Compare children (if multiple)

---

## 🔒 Security Checklist / Lista e Sigurisë

### Essential Security Steps
- [ ] Change admin password from default
- [ ] Update database credentials
- [ ] Set strong passwords for all users
- [ ] Enable HTTPS in production
- [ ] Regular backups of database
- [ ] Keep PHP and MySQL updated
- [ ] Restrict file permissions properly
- [ ] Review and clear activity logs regularly

### Recommended
- [ ] Change default admin email
- [ ] Add email verification for new users
- [ ] Implement session timeout
- [ ] Add CAPTCHA to login
- [ ] Enable database query logging
- [ ] Set up automated backups

---

## 🛠️ Common Tasks / Detyrat e Zakonshme

### Daily Operations
1. Mark student absences
2. Enter grades
3. Check notifications
4. Review activity logs

### Weekly Operations
1. Review attendance reports
2. Update grades
3. Communicate with parents
4. Backup database

### Monthly Operations
1. Generate reports
2. Review user accounts
3. Update schedules if needed
4. Clean up old notifications

### Semester Operations
1. Finalize grades
2. Generate report cards
3. Archive old data
4. Prepare for new semester
5. Switch active semester

---

## 📊 Reporting / Raportimi

### Available Reports
- Student grade reports
- Attendance reports
- Class performance
- Teacher workload
- Monthly statistics

### Generating Reports
1. Go to "Raporte"
2. Select report type
3. Choose date range
4. Select filters
5. Generate and export

---

## 🔄 Maintenance / Mirëmbajtja

### Regular Maintenance
```sql
-- Clear old notifications (older than 90 days)
DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Clear old activity logs (older than 180 days)
DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);

-- Optimize tables
OPTIMIZE TABLE users, students, grades, absences;
```

### Database Backup
```bash
# Manual backup
mysqldump -u root -p school_management > backup_$(date +%Y%m%d).sql

# Automated backup (add to crontab)
0 2 * * * mysqldump -u root -p school_management > /backups/school_$(date +\%Y\%m\%d).sql
```

---

## 🆘 Troubleshooting / Zgjidhja e Problemeve

### Cannot connect to database
1. Check config/database.php
2. Verify MySQL is running
3. Test connection with MySQL client
4. Check user permissions

### Users cannot login
1. Verify user exists in database
2. Check user is_active = 1
3. Verify password is correct
4. Clear browser cache/cookies
5. Check role-specific login fields

### Grades not showing
1. Verify grades exist in database
2. Check semester is active
3. Verify class assignment
4. Check student-class relationship

### Charts not displaying
1. Check browser console for errors
2. Verify Chart.js is loading
3. Check data exists in database
4. Test with different browser

---

## 📞 Support / Mbështetje

For issues:
1. Check this deployment guide
2. Review README.md
3. Check INSTALL.md
4. Review error logs:
   - PHP: `/var/log/php/error.log`
   - Apache: `/var/log/apache2/error.log`
   - MySQL: `/var/log/mysql/error.log`

---

## ✨ Optional Enhancements

Consider adding:
- Email notifications
- SMS alerts for absences
- PDF report generation
- Bulk import (CSV/Excel)
- Mobile responsive improvements
- Dark mode
- Multi-language support
- API for mobile apps
- Advanced analytics

---

**System Ready!** 🎉

Your school management system is now deployed and ready to use.

Remember to:
- Keep regular backups
- Update passwords regularly
- Monitor system usage
- Train users properly
- Maintain documentation

**Good luck!** 🚀
