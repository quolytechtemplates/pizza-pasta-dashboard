# School Management System - Sistemi i Menaxhimit Shkollor

A comprehensive school management system built with PHP and MySQL, following the Albanian education system structure (grades 4-10).

## 🎓 Features

### Multi-Role System
- **Admin**: Complete system control
- **Teacher**: Grade students, mark absences, manage classes
- **Student**: View grades, absences calendar, schedule
- **Parent**: Monitor children's performance, grades, and absences

### Key Functionalities

#### 👨‍💼 Admin Features
- User management (Create, Edit, Delete users)
- Class and subject management
- Academic year and semester management
- Schedule/timetable management
- Comprehensive reports and statistics
- Activity logs
- Excuse absence management
- Interactive dashboards with charts

#### 👨‍🏫 Teacher Features
- View assigned classes and students
- Mark daily absences per hour (1-8 hours)
- Grade students with Albanian system (4-10)
  - Vlerësim i Vazhduar (Continuous Assessment)
  - Projekti Final (Final Project)
  - Testi Final (Final Test)
- View class schedules
- Track activities

#### 👨‍🎓 Student Features
- View all grades by subject and type
- Interactive absence calendar (30-day view)
- Detailed absence records
- Class schedule/timetable
- Performance statistics
- Color-coded calendar showing:
  - Red: Unexcused absences
  - Blue: Excused absences
  - Current day highlighted

#### 👨‍👩‍👧 Parent Features
- Monitor multiple children
- View each child's grades and absences
- Compare children's performance
- Access class schedules
- Receive notifications about children
- Comprehensive dashboard with statistics

## 📋 System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

## 🚀 Installation

### Step 1: Setup Database

1. Create a new MySQL database:
```sql
CREATE DATABASE school_management;
```

2. Import the database schema:
```bash
mysql -u root -p school_management < database_schema.sql
```

Or manually run the `database_schema.sql` file in your MySQL client.

### Step 2: Configure Database Connection

Edit `config/database.php` and update these values:

```php
define('DB_HOST', 'localhost');     // Your database host
define('DB_USER', 'root');          // Your database username
define('DB_PASS', '');              // Your database password
define('DB_NAME', 'school_management');
```

### Step 3: Setup Web Server

#### Option A: Using XAMPP/WAMP
1. Copy the `school_management` folder to your `htdocs` directory
2. Access: `http://localhost/school_management/`

#### Option B: Using PHP Built-in Server
```bash
cd school_management
php -S localhost:8000
```
Access: `http://localhost:8000/`

### Step 4: Default Login Credentials

**Super Admin:**
- Username: `superadmin`
- Email: `admin@school.com`
- Password: `password`

**⚠️ IMPORTANT: Change the admin password immediately after first login!**

## 🔐 Login System

Each role has specific login requirements:

| Role | Login Requirements |
|------|-------------------|
| **Admin** | Username + Email + Password |
| **Teacher** | Email + Password |
| **Student** | Full Name + Student ID |
| **Parent** | Email + Password + Child's Full Name |

## 📊 Database Structure

### Main Tables
- `users` - All system users
- `students` - Student-specific data
- `teachers` - Teacher-specific data
- `parents` - Parent-specific data
- `parent_student` - Parent-child relationships
- `classes` - School classes
- `subjects` - Academic subjects
- `class_subjects` - Class-subject-teacher assignments
- `schedules` - Timetable/schedule
- `grades` - Student grades (4-10 scale)
- `absences` - Student absences per hour
- `academic_years` - Academic year management
- `semesters` - Semester management
- `notifications` - System notifications
- `activity_logs` - User activity tracking

## 🎨 Design

The system uses a modern, responsive design with:
- Bootstrap 5.3
- Tabler Icons
- Chart.js for data visualization
- Custom gradient themes
- Mobile-friendly interface

## 📱 Responsive Design

Fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones

## 🔧 Usage Guide

### For Admins

#### Creating a New Academic Year
1. Go to "Viti Akademik"
2. Click "Shto Vit të Ri"
3. Enter year name, start date, and end date
4. Set as active if needed

#### Creating Classes
1. Go to "Klasat"
2. Click "Shto Klasë të Re"
3. Enter class name (e.g., "Klasa 10A")
4. Assign class teacher
5. Select academic year

#### Adding Students
1. Go to "Studentët"
2. Click "Shto Student"
3. Fill in student details
4. Assign to a class
5. Student ID will be auto-generated

#### Assigning Teachers to Subjects
1. Go to "Lëndët"
2. Select a class
3. Assign teacher to subject
4. Set schedule/timetable

### For Teachers

#### Marking Absences
1. Go to "Mungesa"
2. Select class and date
3. Select subject
4. Mark absent hours (1-8) for each student
5. Submit

#### Grading Students
1. Go to "Nota"
2. Select class and subject
3. Choose grade type:
   - Vlerësim i Vazhduar
   - Projekti Final
   - Testi Final
4. Enter grades (4-10 scale)
5. Submit

### For Students

#### Viewing Grades
1. Dashboard shows recent grades
2. "Notat e mia" page shows all grades by subject
3. Grades are categorized by type

#### Checking Absences
1. Dashboard shows 30-day calendar
2. Red = Unexcused, Blue = Excused
3. "Mungesat" page shows detailed list

### For Parents

#### Monitoring Children
1. Dashboard shows all children's statistics
2. Click on each child's card for details
3. View grades, absences, and schedules
4. Compare children's performance on charts

## 🎯 Albanian Education System Integration

The system follows the Albanian education structure:

### Grading Scale: 4-10
- 10: Shkëlqyer (Excellent)
- 9: Shumë mirë (Very Good)
- 8: Mirë (Good)
- 7: Mjaft mirë (Fairly Good)
- 6: Mesatar (Average)
- 5: Mjaftueshëm (Sufficient)
- 4: Kalimtar (Pass)
- Below 4: Fail

### Grade Types
1. **Vlerësim i Vazhduar**: Continuous assessment throughout the semester
2. **Projekti Final**: Final project grade
3. **Testi Final**: Final test grade

### Daily Schedule
- 8 hours maximum per day
- Absences marked per hour per subject
- Can be excused by admin later

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- Student IDs are hashed for security
- Session-based authentication
- Role-based access control
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Activity logging for audit trails

## 📈 Future Enhancements

Potential additions:
- Email notifications
- SMS integration
- Parent-teacher messaging
- Homework/assignment module
- Exam schedule management
- Report card generation (PDF)
- Bulk student import (CSV/Excel)
- Multi-language support
- Mobile app

## 🐛 Troubleshooting

### Cannot Login
- Check database connection in `config/database.php`
- Verify user exists in database
- Ensure correct role-specific login fields

### Charts Not Showing
- Check if Chart.js is loading (network connection)
- Verify data exists in database
- Check browser console for JavaScript errors

### 500 Internal Server Error
- Check PHP error logs
- Verify file permissions
- Check MySQL connection
- Enable error reporting in `php.ini`

### Session Issues
- Check if sessions are enabled in PHP
- Verify session save path is writable
- Clear browser cache and cookies

## 📞 Support

For issues or questions:
- Check the documentation above
- Review error logs
- Contact system administrator

## 👨‍💻 Development

Built with:
- PHP (Backend)
- MySQL (Database)
- Bootstrap 5 (Frontend Framework)
- Chart.js (Data Visualization)
- Tabler Icons (Icons)

## 📄 License

This project is developed by QUOLYTECH for educational purposes.

## 🙏 Credits

- Design Template: Quolytech Admin Dashboard
- Icons: Tabler Icons
- Charts: Chart.js
- Framework: Bootstrap 5

---

**Version**: 1.0.0  
**Last Updated**: January 2025  
**Developed by**: QUOLYTECH
