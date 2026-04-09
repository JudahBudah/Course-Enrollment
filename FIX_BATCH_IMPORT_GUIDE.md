# Fix: Batch Import Subject Issue - Missing Subjects for Enrollment

## Problem
When batch importing subjects, subjects with the same subject code (e.g., CET 0111) are being skipped even though they belong to different courses. This causes missing subjects for enrollment.

**Example:**
- CET 0111 (Calculus 1) exists for BSIT
- When importing CET 0111 for BSCpE, it gets skipped
- Result: BSCpE students cannot enroll in CET 0111

## Root Cause
The database has a UNIQUE constraint on `subject_code` alone:
```sql
UNIQUE KEY `subject_code` (`subject_code`)
```

This prevents the same subject code from existing for different courses, even though the PHP code has been updated to check for `(subject_code, course_id)` combination.

## Solution
Update the database constraint to allow the same subject code for different courses while preventing true duplicates within the same course.

## Steps to Fix

### 1. Run the migration SQL file
Execute the migration file to update the database constraint:

```bash
# Using MySQL command line
mysql -u root -p studentenrollment < fix_batch_import_subjects.sql

# Or using phpMyAdmin
# - Open phpMyAdmin
# - Select 'studentenrollment' database
# - Go to SQL tab
# - Copy and paste the contents of fix_batch_import_subjects.sql
# - Click 'Go'
```

### 2. Verify the changes
After running the migration, verify:

```sql
-- Check if the new constraint exists
SHOW INDEX FROM subjects WHERE Key_name = 'unique_subject_course';

-- Check if old constraint is removed
SHOW INDEX FROM subjects WHERE Key_name = 'subject_code';
```

### 3. Re-import subjects
Now you can re-import subjects for all courses:

1. Go to Admin Panel → Subjects → Batch Import
2. Paste your subject data
3. Check "Skip Duplicates" option
4. Click Import

### 4. Test enrollment
1. Create classes for subjects with same code but different courses
2. Assign to respective blocks
3. Verify students can enroll in their course-specific subjects

## What Changed

### Database Level
**Before:**
```sql
UNIQUE KEY `subject_code` (`subject_code`)
```
- Only one subject with code "CET 0111" can exist in the entire system

**After:**
```sql
UNIQUE KEY `unique_subject_course` (`subject_code`, `course_id`)
```
- Multiple subjects with code "CET 0111" can exist, one per course
- Prevents duplicate "CET 0111" within the same course

### Application Level (Already Fixed)
The PHP files have already been updated:
- `admin_subjects_batch_import.php` - Checks `(subject_code, course_id)` combination
- `admin_subjects_handler.php` - Checks `(subject_code, course_id)` combination

## Expected Results

### Before Fix
❌ CET 0111 can only exist once in the system  
❌ Batch import skips CET 0111 for BSCpE if it exists for BSIT  
❌ Missing subjects for enrollment  
❌ Students cannot enroll in required subjects  

### After Fix
✅ CET 0111 can exist for BSIT, BSCpE, BSChE, BSME, etc.  
✅ Batch import works for all courses  
✅ All subjects available for enrollment  
✅ Students can enroll in their course-specific subjects  
✅ No enrollment conflicts  

## Example Scenario

### Shared Subject: CET 0111 (Calculus 1)

**After Fix:**
| subject_id | subject_code | subject_name | course_id | course_code |
|------------|--------------|--------------|-----------|-------------|
| 11         | CET 0111     | Calculus 1   | 10        | BSIT        |
| 64         | CET 0111     | Calculus 1   | 5         | BSCpE       |
| 95         | CET 0111     | Calculus 1   | 1         | BSChE       |
| 120        | CET 0111     | Calculus 1   | 9         | BSME        |

Each course now has its own CET 0111 subject record, allowing proper enrollment management.

## Troubleshooting

### If migration fails with "Duplicate entry" error:
This means you have actual duplicates (same subject_code AND same course_id). You need to manually resolve these first:

```sql
-- Find duplicates
SELECT subject_code, course_id, COUNT(*) as count, GROUP_CONCAT(subject_id) as ids
FROM subjects
GROUP BY subject_code, course_id
HAVING COUNT(*) > 1;

-- Delete duplicates (keep the first one)
-- Replace X with the subject_id you want to delete
DELETE FROM subjects WHERE subject_id = X;
```

### If subjects still get skipped:
1. Verify the constraint was updated: `SHOW INDEX FROM subjects;`
2. Check if course_id is being set correctly in the import data
3. Verify the course exists in the courses table
4. Check the format of your import data (course_code should match courses table)

## Import Data Format
Ensure your import data includes the correct course_code:

```
BSCpE|CET 0111|Calculus 1|3|3.0|0.0|BSCPE|1|2nd|
BSIT|CET 0111|Calculus 1|3|3.0|0.0|BSIT|1|2nd|
BSChE|CET 0111|Calculus 1|3|3.0|0.0|BSCHE|1|2nd|
```

Format: `course_code|subject_code|subject_name|units|lecture_hours|lab_hours|department|year_level|semester|prerequisite`
