<?php
session_start();
include("connection.php");
include("admin_functions.php");
check_admin_login($con);

header('Content-Type: application/json');

$result = mysqli_query($con, "
    SELECT log_id, admin_username, action, entity, detail, created_at
    FROM admin_logs
    ORDER BY created_at DESC
    LIMIT 30
");

if (!$result) {
    echo json_encode([]);
    exit;
}

$icon_map = [
    'auth'         => 'fa-right-to-bracket',
    'class'        => 'fa-door-open',
    'subject'      => 'fa-book',
    'announcement' => 'fa-bullhorn',
    'faculty'      => 'fa-chalkboard-user',
    'student'      => 'fa-users',
    'enrollment'   => 'fa-file-lines',
    'block'        => 'fa-layer-group',
    'admin'        => 'fa-user-shield',
    'calendar'     => 'fa-calendar-days',
    'applicant'    => 'fa-user-plus',
];

$color_map = [
    'auth'         => 'blue',
    'class'        => 'purple',
    'subject'      => 'gold',
    'announcement' => 'red',
    'faculty'      => 'teal',
    'student'      => 'blue',
    'enrollment'   => 'green',
    'block'        => 'navy',
    'admin'        => 'red',
    'calendar'     => 'gold',
    'applicant'    => 'gold',
];

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ts   = strtotime($row['created_at']);
    $diff = time() - $ts;

    if ($diff < 60)         $ago = 'Just now';
    elseif ($diff < 3600)   $ago = floor($diff / 60) . 'm ago';
    elseif ($diff < 86400)  $ago = floor($diff / 3600) . 'h ago';
    elseif ($diff < 604800) $ago = floor($diff / 86400) . 'd ago';
    else                    $ago = date('M j, Y', $ts);

    $entity = $row['entity'] ?? 'general';
    $items[] = [
        'icon'     => $icon_map[$entity]  ?? 'fa-circle-info',
        'color'    => $color_map[$entity] ?? 'blue',
        'action'   => $row['action'],
        'detail'   => $row['detail'],
        'by'       => $row['admin_username'],
        'ago'      => $ago,
    ];
}

echo json_encode($items);
