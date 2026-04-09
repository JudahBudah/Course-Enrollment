## TC010 Bug Fix: Inaccurate Number of Students in Block Tab

### Issue Description
After removing a student from a block, the student count sometimes remained the same even though the student was successfully removed. This caused discrepancies between the displayed count and the actual number of students in the block.

### Root Causes Identified
1. **No validation before decrement**: The code decremented `current_students` without verifying the student was actually in the block
2. **No negative value prevention**: The count could potentially go negative if decremented multiple times
3. **Class enrolled count issues**: When removing students, the class `enrolled_count` was decremented without checking if it would go negative
4. **Missing enrollment insertion check**: When assigning students, the class `enrolled_count` was incremented even if the enrollment already existed (INSERT IGNORE)

### Files Modified

#### 1. src/php/remove_student_from_block.php
**Changes:**
- Added validation to verify student is actually in the specified block before removal
- Updated block count decrement to use `GREATEST(0, current_students - 1)` to prevent negative values
- Updated class enrolled count decrement to use `GREATEST(0, enrolled_count - 1)` to prevent negative values
- Added error handling for invalid removal attempts

**Before:**
```php
$update_student = "UPDATE students SET block_id = NULL WHERE student_id = $student_id";

if (mysqli_query($con, $update_student)) {
    $update_block = "UPDATE blocks SET current_students = current_students - 1 WHERE block_id = $block_id";
    mysqli_query($con, $update_block);
    
    // ... enrollment removal code ...
    
    mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count - 1 WHERE class_id = $class_id");
}
```

**After:**
```php
// Verify student is actually in this block
$student_check = mysqli_query($con, "SELECT block_id FROM students WHERE student_id = $student_id");
$student = mysqli_fetch_assoc($student_check);

if (!$student || $student['block_id'] != $block_id) {
    header("Location: ../pages/admin/admin_block_students.php?block_id=$block_id&error=not_in_block");
    exit;
}

$update_student = "UPDATE students SET block_id = NULL WHERE student_id = $student_id";

if (mysqli_query($con, $update_student)) {
    // Update block student count (prevent negative)
    $update_block = "UPDATE blocks SET current_students = GREATEST(0, current_students - 1) WHERE block_id = $block_id";
    mysqli_query($con, $update_block);
    
    // ... enrollment removal code ...
    
    // Update class enrolled count (prevent negative)
    mysqli_query($con, "UPDATE classes SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE class_id = $class_id");
}
```

#### 2. src/php/assign_student_to_block.php
**Changes:**
- Added check to only increment class `enrolled_count` when enrollment is actually inserted (not a duplicate)

**Before:**
```php
while ($subject = mysqli_fetch_assoc($block_subjects)) {
    $class_id = (int)$subject['class_id'];
    mysqli_query($con, "INSERT IGNORE INTO enrollments (student_id, class_id, school_year, semester, status) 
                       VALUES ($student_id, $class_id, '$school_year', '$semester', 'reserved')");
}
```

**After:**
```php
while ($subject = mysqli_fetch_assoc($block_subjects)) {
    $class_id = (int)$subject['class_id'];
    $insert_result = mysqli_query($con, "INSERT IGNORE INTO enrollments (student_id, class_id, school_year, semester, status) 
                       VALUES ($student_id, $class_id, '$school_year', '$semester', 'reserved')");
    
    // Update class enrolled count only if enrollment was actually inserted
    if ($insert_result && mysqli_affected_rows($con) > 0) {
        mysqli_query($con, "UPDATE classes SET enrolled_count = enrolled_count + 1 WHERE class_id = $class_id");
    }
}
```

#### 3. src/pages/admin/admin_block_students.php
**Changes:**
- Added success message for student removal
- Added error message for invalid removal attempts (student not in block)

**Added Messages:**
- Success: "Student removed from block successfully!"
- Error: "Student is not in this block."

### Database Fix Script
Created `fix_block_student_counts.sql` to recalculate and correct any existing incorrect student counts:

```sql
-- Update current_students to match actual count from students table
UPDATE blocks b
SET current_students = (
    SELECT COUNT(*) 
    FROM students s 
    WHERE s.block_id = b.block_id
);
```

### Testing Steps
1. **Test Normal Removal:**
   - Assign a student to a block
   - Verify count increases by 1
   - Remove the student
   - Verify count decreases by 1
   - Verify student's block_id is NULL

2. **Test Invalid Removal:**
   - Try to remove a student not in the block
   - Verify error message appears
   - Verify count remains unchanged

3. **Test Duplicate Assignment Prevention:**
   - Assign a student to a block
   - Try to assign the same student again (if possible)
   - Verify count only increases once

4. **Test Count Accuracy:**
   - Run the fix script to recalculate all counts
   - Compare stored counts with actual student counts
   - Verify all counts match

### Expected Results
- ✅ Student count accurately reflects the number of students in the block
- ✅ Count never goes negative
- ✅ Removing a student decrements the count by exactly 1
- ✅ Invalid removal attempts are prevented with error messages
- ✅ Class enrolled counts remain accurate
- ✅ Success/error messages provide clear feedback

### Status
**FIXED** - TC010 test case now passes
