<?php
function getGradeStatus($grade) {
    $grade = strtoupper(trim($grade));
    
    if ($grade === 'INC') {
        return ['class' => 'incomplete', 'icon' => 'circle-exclamation', 'text' => 'Incomplete'];
    } elseif ($grade === 'OG' || $grade === 'ONGOING') {
        return ['class' => 'ongoing', 'icon' => 'circle-half-stroke', 'text' => 'Ongoing'];
    } elseif ($grade === '5.00' || $grade === 'F' || $grade === 'FAILED') {
        return ['class' => 'failed', 'icon' => 'circle-xmark', 'text' => 'Failed'];
    } elseif (is_numeric($grade) && floatval($grade) >= 1.0 && floatval($grade) <= 3.0) {
        return ['class' => 'passed', 'icon' => 'circle-check', 'text' => 'Passed'];
    } else {
        return ['class' => 'passed', 'icon' => 'circle-check', 'text' => 'Passed'];
    }
}

function renderGradeStatus($grade) {
    $status = getGradeStatus($grade);
    return sprintf(
        '<span class="grade-status %s"><i class="fa-solid fa-%s" style="font-size:.65rem"></i>%s</span>',
        $status['class'],
        $status['icon'],
        $status['text']
    );
}

function renderGradeValue($grade) {
    $grade = strtoupper(trim($grade));
    return sprintf('<span class="grade-val">%s</span>', htmlspecialchars($grade));
}
?>
