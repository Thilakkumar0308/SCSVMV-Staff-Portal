# Teacher Class Assignment System

This system allows Admin, Department Admin (DeptAdmin), and HOD users to assign teachers to classes and subjects.

## Setup Instructions

### 1. Update Database Schema
First, run the database update script to add the necessary columns to the `teacher_classes` table:

```
http://your-domain.com/update_teacher_classes_table.php
```

This script will:
- Add `subject_id` column to link teachers to specific subjects
- Add `assigned_by` column to track who made the assignment
- Update the unique key constraint to prevent duplicate assignments
- Rename `assigned_at` to `created_at` for consistency

### 2. Access the System
Navigate to: `http://your-domain.com/teacher_class_management.php`

## User Roles and Permissions

### Admin
- Can assign any teacher to any class and subject
- Can view all assignments across all departments
- Can remove any assignment

### Department Admin (DeptAdmin)
- Can assign any teacher to any class and subject
- Can view all assignments across all departments
- Can remove any assignment

### HOD (Head of Department)
- Can only assign teachers from their own department
- Can only assign to classes in their department
- Can only view assignments within their department
- Can remove assignments within their department

## Features

### 1. Assign Teacher to Class & Subject
- Select a teacher from the dropdown (filtered by department for HOD)
- Select a class (filtered by department for HOD)
- Select a subject (automatically filtered based on selected class)
- The system prevents duplicate assignments

### 2. View Current Assignments
- Displays all current teacher assignments in a responsive table
- Shows teacher name, class, subject, who assigned it, and when
- Includes subject codes for easy identification
- Sortable and searchable with DataTables

### 3. Remove Assignments
- Click the trash icon to remove an assignment
- Confirmation dialog prevents accidental deletions
- Only authorized users can remove assignments

### 4. Dynamic Subject Filtering
- When you select a class, the subject dropdown automatically filters to show only subjects for that class
- Prevents invalid teacher-subject-class combinations

## Database Structure

The `teacher_classes` table now includes:
```sql
CREATE TABLE teacher_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    assigned_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assignment (teacher_id, class_id, subject_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);
```

## Navigation

The system is accessible through the sidebar menu:
- **Admin**: Can see "Teacher-Class Assignment" link
- **DeptAdmin**: Can see "Teacher-Class Assignment" link  
- **HOD**: Can see "Teacher-Class Assignment" link
- **Teacher/Student**: Cannot access this feature

## File Structure

- `teacher_class_management.php` - Main interface for managing teacher assignments
- `update_teacher_classes_table.php` - Database schema update script
- `assets/js/teacher_assignment.js` - JavaScript for dynamic filtering and interactions
- `teacher_classes.php` - Redirects to new system (for backward compatibility)
- `teacher_class_assign.php` - Redirects to new system (for backward compatibility)

## Error Handling

The system includes comprehensive error handling:
- Duplicate assignment prevention
- Database constraint validation
- User permission checks
- Input validation and sanitization
- Graceful fallbacks for missing database columns

## Browser Compatibility

- Modern browsers with JavaScript enabled
- Bootstrap 5 compatible
- DataTables integration for enhanced table functionality
- Responsive design for mobile and tablet devices
