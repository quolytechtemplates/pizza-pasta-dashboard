# INSTALLATION GUIDE - Udhëzues Instalimi

## Quick Start / Fillimi i Shpejtë

### 1. Database Setup / Konfigurimi i Databazës

#### Using phpMyAdmin:
1. Open phpMyAdmin
2. Create new database named: `school_management`
3. Select the database
4. Click "Import"
5. Choose file: `database_schema.sql`
6. Click "Go"

#### Using Command Line:
```bash
mysql -u root -p
CREATE DATABASE school_management;
exit;

mysql -u root -p school_management < database_schema.sql
```

### 2. Configure Database Connection / Konfigurim i Lidhjes

Open file: `config/database.php`

Change these lines / Ndryshoni këto rreshta:
```php
define('DB_HOST', 'localhost');    // Usually 'localhost'
define('DB_USER', 'root');         // Your MySQL username
define('DB_PASS', '');             // Your MySQL password
define('DB_NAME', 'school_management');
```

### 3. Access the System / Hyrje në Sistem

#### For XAMPP/WAMP:
1. Copy folder to: `C:\xampp\htdocs\`
2. Start Apache and MySQL
3. Open browser: `http://localhost/school_management/`

#### For PHP Built-in Server:
```bash
cd school_management
php -S localhost:8000
```
Then open: `http://localhost:8000/`

### 4. First Login / Hyrja e Parë

**Admin Login:**
- Go to: `http://localhost/school_management/login.php`
- Click "Admin" role
- Username: `superadmin`
- Email: `admin@school.com`
- Password: `password`

**⚠️ IMPORTANT: Change password immediately!**

---

## Default Data Included / Të Dhënat Standarde të Përfshira

The database includes:
- 1 Super Admin account
- Academic Year 2024-2025
- 2 Semesters
- 10 Sample Subjects (Math, Albanian, English, Physics, etc.)

---

## Creating Sample Users / Krijimi i Përdoruesve Shembull

### Add Teacher / Shto Mësues:
1. Login as admin
2. Go to "Mësuesit" → "Shto Mësues"
3. Fill in details
4. Teacher can login with email + password

### Add Student / Shto Student:
1. Login as admin
2. Go to "Studentët" → "Shto Student"
3. Fill in details and assign to class
4. Student can login with full name + student ID

### Add Parent / Shto Prind:
1. Login as admin
2. Go to "Prindërit" → "Shto Prind"
3. Fill in details
4. Assign children to parent
5. Parent can login with email + password + child's full name

---

## System Requirements / Kërkesat e Sistemit

**Minimum:**
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- 50MB disk space

**Recommended:**
- PHP 8.0+
- MySQL 8.0+
- 100MB disk space
- Modern browser (Chrome, Firefox, Edge)

---

## File Permissions / Lejet e Skedarëve

For Linux/Mac servers:
```bash
chmod 755 school_management/
chmod 644 school_management/*.php
chmod 666 school_management/config/database.php
```

---

## Troubleshooting / Zgjidhja e Problemeve

### Error: "Connection failed"
**Solution:** Check database credentials in `config/database.php`

### Error: "Access denied"
**Solution:** Verify MySQL username and password are correct

### Error: "Table doesn't exist"
**Solution:** Re-import `database_schema.sql`

### Blank white page
**Solution:** 
1. Enable error reporting in PHP
2. Check error logs
3. Verify file permissions

### Cannot login
**Solution:**
1. Verify database contains user data
2. Clear browser cache
3. Check role-specific login fields

---

## Testing the System / Testimi i Sistemit

1. **Login as Admin**: Create users, classes, subjects
2. **Login as Teacher**: Grade students, mark absences
3. **Login as Student**: View grades and absences
4. **Login as Parent**: Monitor children's progress

---

## Security Checklist / Lista e Sigurisë

- [ ] Change admin password
- [ ] Update database credentials
- [ ] Set proper file permissions
- [ ] Enable HTTPS in production
- [ ] Regular database backups
- [ ] Keep PHP updated

---

## Next Steps / Hapat e Ardhshëm

1. Change default admin password
2. Create academic year for current year
3. Add classes and subjects
4. Register teachers
5. Assign teachers to subjects
6. Create class schedules
7. Register students
8. Register parents and link to students
9. Start using the system!

---

## Support / Mbështetje

For help:
- Read README.md for detailed documentation
- Check troubleshooting section
- Review error logs
- Contact: QUOLYTECH

---

**Good luck! / Suksese!**
