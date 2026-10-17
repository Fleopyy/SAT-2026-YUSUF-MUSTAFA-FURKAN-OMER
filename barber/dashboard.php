<?php
require_once dirname(__DIR__) . '/admin/includes/auth.php';

if (getAuthenticatedBarber()) {
    header('Location: /isletme/');
} else {
    header('Location: /isletme/giris.php');
}
exit;
