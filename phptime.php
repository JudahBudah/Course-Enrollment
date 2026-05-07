<?php
echo 'PHP time(): ' . time() . '<br>';
echo 'PHP date: ' . date('Y-m-d H:i:s') . '<br>';
echo 'Timezone: ' . date_default_timezone_get() . '<br>';
echo 'strtotime test: ' . strtotime('2026-05-03 20:16:00') . '<br>';
echo 'now >= start: ' . (time() >= strtotime('2026-05-03 20:16:00') ? 'YES' : 'NO') . '<br>';
echo 'now <= end: '   . (time() <= strtotime('2026-05-22 20:20:00') ? 'YES' : 'NO') . '<br>';
