<?php
// Redirect legacy "Faculty Comparison" page to the unified Performance Trends view.
$dept = isset($_GET['dept']) ? intval($_GET['dept']) : 0;
$range = isset($_GET['range']) ? preg_replace('/[^0-9a-z]/i', '', $_GET['range']) : '3';
$url = 'index.php?page=faculty_trends&view=comparison&range=' . urlencode($range);
if ($dept > 0) $url .= '&dept=' . $dept;
header('location:' . $url);
exit;
