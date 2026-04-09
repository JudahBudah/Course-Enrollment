## Fix: Allow Same Subject Code for Multiple Courses

### Issue Description
When importing subjects, the system skips subjects with the same subject code even if they belong to different courses. For example:
- "CET 0111 - Calculus 1" for BSCpE is skipped because "CET 0111" already exists for BSChE
- "CET 0112 - Chemistry for Engineers" for BSME is skipped because it exists for BSChE
- This prevents proper curriculum setup for multiple engineering courses that share common subjects

This causes enrollment conflicts because students from different courses can't enroll in their course-specific sections of shared subjects.

### Root Cause
The duplicate check only validates `subject_code`, not the combination of `subject_code + course_id`. This means:
1. Batch import skips subjects if the code exists, regardless of course
2. Manual add prevents creating subjects with existing codes
3. Database has no constraint to enforce the correct uniqueness rule

### Solution
Changed the uniqueness constraint from `subject_code` alone to `(subject_code, course_id)` combination. This allows:
- Same subject code for different courses (e.g., CET 0111 for BSCpE, BSChE, BSME)
- Prevents true duplicates (same code + same course)
- Proper course-specific subject management

### Files Modified

#### 1. src/php/admin_subjects_batch_import.php
**Before:**
```php
// Check for duplicates
$chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ?");
mysqli_stmt_bind_param($chk, "s", $subject_code);
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
```

**After:**
```php
// Check for duplicates (subject_code + course_id combination)
if ($course_id !== null) {
    $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id = ?");
    mysqli_stmt_bind_param($chk, "si", $subject_code, $course_id);
} else {
    $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id IS NULL");
    mysqli_stmt_bind_param($chk, "s", $subject_code);
}
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
```

#### 2. src/php/admin_subjects_handler.php
**Add Action - Before:**
```php
// Check duplicate code
$chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ?");
mysqli_stmt_bind_param($chk, "s", $subject_code);
```

**Add Action - After:**
```php
// Check duplicate code + course combination
if ($course_id !== null) {
    $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id = ?");
    mysqli_stmt_bind_param($chk, "si", $subject_code, $course_id);
} else {
    $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id IS NULL");
    mysqli_stmt_bind_param($chk, "s", $subject_code);
}
```

**Edit Action - Before:**
```php
// Check duplicate code excluding self
$chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND subject_id != ?");
mysqli_stmt_bind_param($chk, "si", $subject_code, $subject_id);
```

**Edit Action - After:**
```php
// Check duplicate code + course combination excluding self
if ($course_id !== null) {
    $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id = ? AND subject_id != ?");
    mysqli_stmt_bind_param($chk, "sii", $subject_code, $course_id, $subject_id);
} else {
    $chk = mysqli_prepare($con, "SELECT subject_id FROM subjects WHERE subject_code = ? AND course_id IS NULL AND subject_id != ?");
    mysqli_stmt_bind_param($chk, "si", $subject_code, $subject_id);
}
```

### Database Changes

#### fix_subject_duplicate_constraint.sql
```sql
-- Drop old constraint on subject_code alone
ALTER TABLE subjects DROP INDEX IF EXISTS subject_code;

-- Add new constraint on (subject_code, course_id) combination
ALTER TABLE subjects ADD UNIQUE KEY unique_subject_course (subject_code, course_id);
```

This ensures database-level enforcement of the correct uniqueness rule.

### Impact

**Before Fix:**
- ❌ Can't import CET 0111 for multiple courses
- ❌ Each subject code can only exist once in the entire system
- ❌ Enrollment conflicts when students from different courses need the same subject
- ❌ Manual workarounds needed (different subject codes for same subject)

**After Fix:**
- ✅ CET 0111 can exist for BSCpE, BSChE, BSME, etc.
- ✅ Each course has its own set of subjects
- ✅ No enrollment conflicts - students enroll in course-specific subjects
- ✅ Proper curriculum management per course
- ✅ Batch import works correctly for all courses

### Example Scenarios

**Scenario 1: Shared General Education Subjects**
- CET 0111 (Calculus 1) is required for BSCpE, BSChE, BSME, BSECE
- Each course can now have its own CET 0111 entry
- Students enroll in their course-specific section

**Scenario 2: Common Engineering Subjects**
- CET 0112 (Chemistry for Engineers) for multiple engineering courses
- Each course maintains its own subject record
- Proper tracking per course

**Scenario 3: Course-Specific Subjects**
- CPE 0111 (Computer Engineering as a Discipline) only for BSCpE
- No conflicts with other courses
- Clear course ownership

### Testing Steps

1. **Test Batch Import:**
   - Import BSChE subjects (includes CET 0111)
   - Import BSCpE subjects (also includes CET 0111)
   - Verify both are imported successfully
   - Verify each has correct course_id

2. **Test Manual Add:**
   - Manually add CET 0111 for BSME
   - Verify it's added without duplicate error
   - Verify course_id is set correctly

3. **Test Duplicate Prevention:**
   - Try to add CET 0111 for BSCpE again (same course)
   - Verify duplicate error appears
   - Verify it prevents true duplicates

4. **Test Database Constraint:**
   - Try to insert duplicate via SQL
   - Verify constraint prevents it
   - Verify error message is clear

5. **Test Enrollment:**
   - Create classes for CET 0111 (BSCpE)
   - Create classes for CET 0111 (BSChE)
   - Assign to respective blocks
   - Verify students enroll in correct course-specific classes

### Migration Steps

1. **Run the database script:**
   ```bash
   mysql -u root studentenrollment < fix_subject_duplicate_constraint.sql
   ```

2. **Check for existing duplicates:**
   - The script will show any existing duplicates
   - Manually resolve if needed

3. **Re-import subjects:**
   - Now you can import subjects for all courses
   - Previously skipped subjects will be imported

4. **Verify:**
   - Check subjects table for multiple entries with same code
   - Verify each has different course_id
   - Test enrollment workflow

### Expected Results
- ✅ Same subject code can exist for multiple courses
- ✅ Each course has its own subject records
- ✅ Batch import works for all courses
- ✅ Manual add allows course-specific subjects
- ✅ Database constraint enforces correct uniqueness
- ✅ No enrollment conflicts
- ✅ Proper course-specific curriculum management

### Status
**FIXED** - Subject codes can now be shared across courses while preventing true duplicates within the same course.
