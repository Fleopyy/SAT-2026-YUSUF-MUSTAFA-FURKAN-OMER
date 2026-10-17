<?php
   
                                                 
                                                                                  
                                                            
                                                            
   

require_once __DIR__ . '/includes/auth.php';

$admin = getAuthenticatedAdmin();

if ($admin && $admin['rol'] === 'admin') {
    header('Location: /admin/dashboard');
    exit;
}

header('Location: /admin/login.php');
exit;
