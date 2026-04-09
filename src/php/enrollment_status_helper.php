<?php
/**
 * Update student enrollment status based on active enrollments
 * Call this function after any enrollment status change
 */
function update_student_enrollment_status($con, $student_id) {
    // Log the function call
    error_log("update_student_enrollment_status called for student_id=$student_id");
    
    // Check if student has any remaining active enrollments
    $check_stmt = mysqli_prepare($con, "
        SELECT COUNT(*) as active_count 
        FROM enrollments 
        WHERE student_id = ? 
        AND status IN ('reserved','confirmed','ongoing')
    ");
    mysqli_stmt_bind_param($check_stmt, "i", $student_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
    mysqli_stmt_close($check_stmt);
    
    $active_count = (int)$result['active_count'];
    error_log("Active enrollments for student $student_id: $active_count");
    
    // Update student status based on active enrollments
    if ($active_count == 0) {
        // No active enrollments - set to Dropped
        error_log("Setting student $student_id status to Dropped");
        $update = mysqli_query($con, "
            UPDATE students 
            SET status = 'Dropped', block_id = NULL 
            WHERE student_id = $student_id
        ");
        
        if (!$update) {
            error_log("Failed to update student status: " . mysqli_error($con));
            return ['status' => 'Dropped', 'updated' => false, 'error' => mysqli_error($con)];
        }
        
        $affected = mysqli_affected_rows($con);
        error_log("Update query executed, affected rows: $affected");
        
        // Verify the update
        $verify = mysqli_query($con, "SELECT status FROM students WHERE student_id = $student_id");
        $current_status = mysqli_fetch_assoc($verify)['status'];
        error_log("Verified status in DB: $current_status");
        
        return ['status' => 'Dropped', 'updated' => ($affected > 0), 'affected_rows' => $affected, 'verified_status' => $current_status];
    } else {
        // Has active enrollments - ensure status is Enrolled
        error_log("Setting student $student_id status to Enrolled");
        $update = mysqli_query($con, "
            UPDATE students 
            SET status = 'Enrolled' 
            WHERE student_id = $student_id AND status != 'Enrolled'
        ");
        
        if (!$update) {
            error_log("Failed to update student status: " . mysqli_error($con));
            return ['status' => 'Enrolled', 'updated' => false, 'error' => mysqli_error($con)];
        }
        
        $affected = mysqli_affected_rows($con);
        error_log("Update query executed, affected rows: $affected");
        
        return ['status' => 'Enrolled', 'updated' => ($affected > 0), 'affected_rows' => $affected];
    }
}
?>
