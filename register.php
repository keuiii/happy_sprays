<?php
session_start();
require_once 'classes/database.php';
$db = Database::getInstance();

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];
    $secret   = trim($_POST['secret_code']);

    $result = $db->registerUser($username, $password, $role, $secret);

    if ($result['success']) {
        $msg = $result['message'] . " <a href='login.php'>Login here</a>";
    } else {
        $msg = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Happy Sprays</title>
<style>
    body {font-family:'Segoe UI',sans-serif; background:#f4f4f4; margin:0; display:flex; height:100vh; align-items:center; justify-content:center;}
    .register-box {background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width:340px;}
    h2 {margin:0 0 20px; text-align:center; font-weight:600;}
    input, select {width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:6px;}
    button {width:100%; padding:10px; border:none; background:#333; color:#fff; font-weight:600; border-radius:6px; cursor:pointer;}
    button:hover {background:#000;}
    .msg {font-size:14px; text-align:center; margin-bottom:10px; color:red;}
</style>
</head>
<body>
    <form class="register-box" method="post">
        <h2>Create Account</h2>
        <?php if($msg): ?><p class="msg"><?= $msg ?></p><?php endif; ?>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
        <input type="text" name="secret_code" placeholder="Registration Code" required>
        <button type="submit">Register</button>
    </form>
</body>
</html>