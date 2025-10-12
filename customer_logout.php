<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();
$db->logout();

header("Location: index.php");
exit;
?>