<?php
   
                                                   
   
require_once dirname(__DIR__) . '/admin/includes/auth.php';

$manager = getAuthenticatedManager();
if ($manager) {
    header('Location: /manager/dashboard');
    exit;
}

$admin = getAuthenticatedAdmin();
if ($admin) {
    header('Location: /admin/dashboard');
    exit;
}

header('Location: /admin/login.php');
exit;
