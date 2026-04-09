## Block Subject Assignment Filter Fix

### Issue Description
When trying to add subjects to a CPE (Computer Engineering) block, the system was showing ALL available classes regardless of course, making it impossible to properly assign CPE-specific subjects to a CPE block. The same issue affected all other courses.

### Root Cause
The query for available classes in `admin_block_subjects.php` only filtered by:
- School year
- Semester  
- Status (open)
- Not already assigned to the block

It did NOT filter by:
- Block's course (e.g., BSCpE, BSIT, BSA)
- Block's year level

This meant a BSCpE Year 1 block would see subjects from ALL courses and ALL year levels, making it difficult to find the correct subjects.

### Solution
Updated the available classes query to filter by:
1. **Course matching**: Only show subjects where `course_code` matches the block's course
2. **Year level matching**: Only show subjects for the block's year level
3. **NULL handling**: Allow subjects without course_id or year_level (general education subjects)

### File Modified

**src/pages/admin/admin_block_subjects.php**

**Before:**
```php
$available_query = mysqli_query($con, "
    SELECT c.*, s.subject_code, s.subject_name, s.units,
           f.first_name, f.last_name
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE c.class_id NOT IN (
        SELECT class_id FROM block_subjects WHERE block_id = $block_id
    )
    AND c.school_year = '$sy_esc'
    AND c.semester    = '$sem_esc'
    AND c.status      = 'open'
    ORDER BY s.subject_code
");
```

**After:**
```php
$course_esc = mysqli_real_escape_string($con, $block['course']);
$year_esc = (int)$block['year_level'];

$available_query = mysqli_query($con, "
    SELECT c.*, s.subject_code, s.subject_name, s.units, s.year_level as subject_year,
           f.first_name, f.last_name, co.course_code
    FROM classes c
    JOIN subjects s ON c.subject_id = s.subject_id
    LEFT JOIN courses co ON s.course_id = co.course_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE c.class_id NOT IN (
        SELECT class_id FROM block_subjects WHERE block_id = $block_id
    )
    AND c.school_year = '$sy_esc'
    AND c.semester    = '$sem_esc'
    AND c.status      = 'open'
    AND (co.course_code = '$course_esc' OR s.course_id IS NULL)
    AND (s.year_level = $year_esc OR s.year_level IS NULL)
    ORDER BY s.subject_code
");
```

### Key Changes

1. **Added course filter**: `AND (co.course_code = '$course_esc' OR s.course_id IS NULL)`
   - Shows only subjects for the block's course
   - Includes subjects without a course (general education)

2. **Added year level filter**: `AND (s.year_level = $year_esc OR s.year_level IS NULL)`
   - Shows only subjects for the block's year level
   - Includes subjects without year level (electives, general subjects)

3. **Added JOIN to courses table**: `LEFT JOIN courses co ON s.course_id = co.course_id`
   - Retrieves course_code for filtering

4. **Added variables**: 
   - `$course_esc` - Escaped block course code
   - `$year_esc` - Block year level as integer

### Benefits

1. **Relevant subjects only**: CPE blocks now only see CPE subjects
2. **Year-appropriate**: Year 1 blocks only see Year 1 subjects
3. **Easier selection**: Dropdown is much shorter and more manageable
4. **Prevents errors**: Can't accidentally assign wrong course subjects
5. **Maintains flexibility**: General education subjects still available to all

### Example Scenarios

**Scenario 1: BSCpE Year 1 Block**
- Block: BSCpE, Year 1, 1st Semester
- Shows: BSCpE Year 1 subjects + General Education subjects
- Hides: BSIT subjects, BSA subjects, Year 2+ subjects

**Scenario 2: BSIT Year 2 Block**
- Block: BSIT, Year 2, 2nd Semester  
- Shows: BSIT Year 2 subjects + General subjects
- Hides: BSCpE subjects, Year 1 subjects, Year 3+ subjects

**Scenario 3: BSA Year 3 Block**
- Block: BSA, Year 3, 1st Semester
- Shows: BSA Year 3 subjects + General subjects
- Hides: Engineering subjects, Year 1-2 subjects

### Testing Steps

1. **Create a BSCpE block** (Year 1, 1st Semester)
2. **Go to Block Subjects page**
3. **Check available classes dropdown**
   - Should show only BSCpE Year 1 subjects
   - Should show general education subjects
   - Should NOT show BSIT, BSA, or other course subjects
4. **Add a BSCpE subject** - Should work correctly
5. **Repeat for other courses** (BSIT, BSA, BSME, etc.)

### Expected Results
- ✅ CPE blocks can add CPE classes
- ✅ BSIT blocks can add BSIT classes  
- ✅ BSA blocks can add BSA classes
- ✅ Year 1 blocks only see Year 1 subjects
- ✅ General education subjects available to all
- ✅ Dropdown is manageable and relevant
- ✅ No cross-course contamination

### Status
**FIXED** - Block subject assignment now properly filters by course and year level.
