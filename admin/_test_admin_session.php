<?php
require_once dirname(__DIR__) . '/data/session.php';
$_SESSION['admin_id'] = 4;
$_SESSION['admin_role'] = 'admin';
$_SESSION['admin_name'] = 'Yusuf (Admin)';
header('Location: /admin/pages/managers.php');
exit;
