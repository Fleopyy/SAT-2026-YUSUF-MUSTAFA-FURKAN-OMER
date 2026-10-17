<?php
   
                                                  
   
require_once dirname(__DIR__) . '/admin/includes/auth.php';

$barber = getAuthenticatedBarber();
if ($barber) {
    header('Location: /isletme/');
    exit;
}

header('Location: /isletme/giris.php');
exit;
