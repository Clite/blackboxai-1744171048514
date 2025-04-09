<?php
require_once '../config.php';
require_once '../classes/User.php';

$user = new User($pdo);
$user->logout();

redirect(BASE_URL . '/auth/login.php');
