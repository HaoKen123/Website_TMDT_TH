<?php
session_start();
$_SESSION['cart'] = [];
unset($_SESSION['coupon']);
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_role']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);
session_destroy();
session_start();
header('Location: index.php');
exit;
?>
