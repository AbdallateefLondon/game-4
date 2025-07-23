# 🎮 Educational Game Builder System - Complete Installation Guide

## 📋 Table of Contents

1. [System Overview](#system-overview)
2. [Prerequisites](#prerequisites)
3. [Installation Steps](#installation-steps)
4. [Configuration](#configuration)
5. [Testing](#testing)
6. [Troubleshooting](#troubleshooting)
7. [Features Overview](#features-overview)

---

## 🎯 System Overview

The Educational Game Builder System is a comprehensive gaming module integrated into your existing CodeIgniter-based school management platform. It enables:

- **Teachers**: Create and manage interactive educational games (Quiz & Matching types)
- **Students**: Play assigned games, earn points, and track progress through levels
- **Administrators**: Monitor student performance, view analytics, and manage permissions

### Key Features:

- ✅ Two game types: Quiz Games and Matching Games
- ✅ Non-linear leveling system (Level N = N² × 2 formula)
- ✅ Role-based access control (RBAC) integration
- ✅ Class/section-based game assignment
- ✅ Real-time leaderboards and analytics
- ✅ Student achievements and progress tracking

---

## 🔧 Prerequisites

Before installation, ensure your system meets these requirements:

### System Requirements:

- **CodeIgniter Framework**: 3.x (already installed)
- **PHP Version**: 7.4+
- **MySQL Database**: 5.7+ or MariaDB 10.2+
- **Web Server**: Apache/Nginx with mod_rewrite enabled
- **Browser Support**: Modern browsers (Chrome, Firefox, Safari, Edge)

### Existing Platform Requirements:

- ✅ School management system with user authentication
- ✅ Classes and sections management
- ✅ Student and staff management
- ✅ RBAC permission system with `permission_group` and `permission_category` tables

---

## 🚀 Installation Steps

### Step 1: File Deployment

1. **Upload Files**: Copy all game system files to your CodeIgniter application:

```
/application/controllers/Gamebuilder.php
/application/controllers/admin/Gamesetup.php
/application/models/Educationalgame_model.php
/application/models/Gameresult_model.php
/application/models/Studentpoint_model.php
/application/views/gamebuilder/
```

2. **Update Routes**: Add the routing configuration to `/application/config/routes.php`:

```php
$route['gamebuilder'] = 'gamebuilder/index';
$route['gamebuilder/student-games'] = 'gamebuilder/student_games';
$route['gamebuilder/play/(:num)'] = 'gamebuilder/play_game/$1';
$route['gamebuilder/create'] = 'gamebuilder/create';
$route['gamebuilder/create/(:num)'] = 'gamebuilder/create/$1';
$route['gamebuilder/delete/(:num)'] = 'gamebuilder/delete/$1';
$route['gamebuilder/results'] = 'gamebuilder/results';
$route['gamebuilder/results/(:num)'] = 'gamebuilder/results/$1';
$route['gamebuilder/leaderboard'] = 'gamebuilder/leaderboard';
$route['gamebuilder/dashboard'] = 'gamebuilder/dashboard';
```

### Step 2: Database Installation

#### Option A: Web-based Installation (Recommended)

1. **Access Setup Page**: Go to `/admin/gamesetup` in your admin panel
2. **Run Installation**: Click "Install Game System"
3. **Verify Success**: Check that all tables and permissions are created

#### Option B: Manual SQL Installation

If the web installer doesn't work, execute the SQL from `database_setup.sql`:

```sql
-- Execute the contents of database_setup.sql
-- This creates:
-- - educational_games table
-- - game_results table
-- - student_points table
-- - Permission groups and categories
```

### Step 3: Permission Configuration

1. **Access Role Management**: Go to Admin → Users → Roles

2. **Assign Staff Permissions**:
   - Find "Game Builder" permission group
   - Assign appropriate permissions to Teacher/Staff roles:
     - `games_management`: view, add, edit, delete (for creating games)
     - `game_results`: view (for viewing student results)
     - `student_gaming`: view (for monitoring)

3. **Assign Student Permissions**:
   - Find "Student Games" permission group
   - Assign to Student role:
     - `play_games`: view (allows playing games)
     - `view_game_results`: view (allows viewing own results)
     - `game_leaderboard`: view (allows viewing leaderboard)

---

## ⚙️ Configuration

### Database Configuration

The system uses your existing database configuration from `/application/config/database.php`. No additional database setup is required.

### System Integration Points

#### 1. User Authentication

The system integrates with existing login systems:

- **Staff Login**: `/login` (for teachers and administrators)
- **Student Login**: `/userlogin` (for student access)

#### 2. Class/Section Integration

Games can be assigned to specific classes and sections using your existing:

- `classes` table
- `sections` table
- `student_session` table (links students to classes/sections)

#### 3. Permission Integration

Uses existing RBAC tables:

- `permission_group`
- `permission_category`
- `roles_permissions`

---

## 🧪 Testing

### 1. Admin Testing

- ✅ Access `/admin/gamesetup` to verify installation
- ✅ Go to `/gamebuilder` to access game management
- ✅ Create a test quiz game
- ✅ Create a test matching game
- ✅ View analytics dashboard at `/gamebuilder/dashboard`

### 2. Teacher Testing

- ✅ Login as teacher with game permissions
- ✅ Access games section
- ✅ Create and edit games
- ✅ View student results

### 3. Student Testing

- ✅ Login as student
- ✅ Access games section at `/gamebuilder/student-games`
- ✅ Play a quiz game
- ✅ Play a matching game
- ✅ Check points and level progression
- ✅ View leaderboard

---

## 🔧 Troubleshooting

### Common Installation Issues

#### 1. Database Connection Errors

**Problem**: Can't connect to database during setup
**Solution**:

- Verify database credentials in `/application/config/database.php`
- Ensure database user has CREATE and INSERT privileges
- Check database server is running

#### 2. Permission Denied Errors

**Problem**: Web installer shows permission denied
**Solution**:

- Ensure file permissions allow web server to write
- Verify database user has proper privileges
- Try manual SQL installation instead

#### 3. Missing Tables Error

**Problem**: Game system reports missing tables
**Solution**:

- Run the setup again from `/admin/gamesetup`
- Check database for partial installation
- Use uninstall option and reinstall if needed

#### 4. Routes Not Working

**Problem**: Game URLs return 404 errors
**Solution**:

- Verify mod_rewrite is enabled
- Check `.htaccess` file exists
- Confirm routes are properly added to `routes.php`

#### 5. Permission Access Denied

**Problem**: Users can't access game features
**Solution**:

- Check role permissions in Admin → Roles
- Assign proper game permissions to user roles
- Verify users are assigned to correct roles

### Database Schema Issues

#### Foreign Key Relationships

Ensure these tables exist and have proper structure:

- `classes` (id, class)
- `sections` (id, section)
- `students` (id, firstname, lastname, etc.)
- `staff` (id, name, surname, etc.)
- `student_session` (student_id, class_id, section_id, session_id)

#### Index Performance

The system creates these indexes for optimal performance:

- `idx_class_section` on educational_games
- `idx_game_student` on game_results
- `idx_total_points` on student_points

---

## 🎮 Features Overview

### Game Types

#### 1. Quiz Games

- **Format**: Multiple choice questions (2-4 options)
- **Features**:
  - Timed or untimed
  - Explanation for correct answers
  - Difficulty levels (easy/medium/hard)
  - Point multipliers based on difficulty

#### 2. Matching Games

- **Format**: Match left-side items with right-side answers
- **Features**:
  - Text-based matching
  - Case-insensitive matching
  - Instant feedback
  - Bulk answer submission

### Leveling System

The system uses a non-linear progression formula:

- **Level 1**: 0-10 points
- **Level 2**: 11-18 points (8 points needed)
- **Level 3**: 19-34 points (16 points needed)
- **Level 4**: 35-66 points (32 points needed)
- **Level N**: Previous total + (N² × 2) points

### Permission System

#### Staff Permissions (game_builder):

- `games_management`: Create, edit, delete games
- `game_results`: View student performance
- `student_gaming`: Monitor student activity

#### Student Permissions (student_games):

- `play_games`: Access and play assigned games
- `view_game_results`: View personal game history
- `game_leaderboard`: View class leaderboards

### Analytics & Reporting

#### For Administrators:

- Total games created
- Student engagement statistics
- Popular games analysis
- Level distribution charts
- Recent activity monitoring

#### For Teachers:

- Games they created
- Student performance per game
- Class performance analytics
- Individual student progress

#### For Students:

- Personal progress tracking
- Points and level display
- Achievement badges
- Class leaderboard position

---

## 📊 Usage Guidelines

### For Teachers Creating Games:

1. **Plan Your Content**: Prepare questions/matching pairs in advance
2. **Choose Appropriate Difficulty**: Match game difficulty to student level
3. **Set Reasonable Limits**: Configure attempt limits and time appropriately
4. **Test Before Assigning**: Play your own games to ensure they work correctly
5. **Monitor Results**: Check student performance and adjust games as needed

### For Students Playing Games:

1. **Read Instructions**: Each game type has specific rules
2. **Manage Attempts**: You have limited attempts per game
3. **Focus on Learning**: Games are educational tools, not just entertainment
4. **Track Progress**: Monitor your points and level advancement
5. **Compete Positively**: Use leaderboards for motivation, not pressure

### For Administrators:

1. **Assign Permissions**: Properly configure role-based access
2. **Monitor Usage**: Use analytics to track system adoption
3. **Support Teachers**: Help educators create effective games
4. **Maintain System**: Keep the gaming system updated and optimized

---

## 🔄 System Maintenance

### Regular Tasks:

- **Monitor Database Size**: Game results can accumulate over time
- **Review Analytics**: Check student engagement regularly
- **Update Permissions**: Adjust as roles change
- **Backup Data**: Include game tables in regular backups

### Performance Optimization:

- **Archive Old Results**: Move old game results to archive tables
- **Update Statistics**: Refresh leaderboard caches periodically
- **Monitor Indexes**: Ensure database indexes are optimized

---

## 📞 Support

For technical support or questions about the Educational Game Builder System:

1. **Check Documentation**: Review this guide first
2. **Test Installation**: Use the built-in setup diagnostics
3. **Review Logs**: Check CodeIgniter and database logs for errors
4. **Community Support**: Consult CodeIgniter and educational technology forums

---

## 📝 Version Information

- **System Version**: 1.0.0
- **CodeIgniter Compatibility**: 3.x
- **Database Schema Version**: 1.0
- **Last Updated**: January 2025

---

**🎉 Congratulations!** You've successfully installed the Educational Game System. Your students can now enjoy interactive learning while teachers track their progress through comprehensive analytics.

Happy Gaming! 🎮📚