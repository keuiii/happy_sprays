<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../classes/database.php';

$db = Database::getInstance();

// Response template
$response = ['status' => 'error', 'message' => 'Unknown error'];

try {
    $input = $_POST;
    $usernameOrEmail = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($usernameOrEmail) || empty($password)) {
        $response['message'] = 'Please enter both username/email and password.';
        echo json_encode($response);
        exit;
    }

    // Check customer
    $customer = $db->fetch(
        "SELECT * FROM customers WHERE customer_username = ? OR customer_email = ? LIMIT 1",
        [$usernameOrEmail, $usernameOrEmail]
    );

    if (!$customer) {
        $response['message'] = 'Invalid username/email or password.';
        echo json_encode($response);
        exit;
    }

    if (!password_verify($password, $customer['customer_password'])) {
        $response['message'] = 'Invalid username/email or password.';
        echo json_encode($response);
        exit;
    }

    // Check if email verified
    if ($customer['is_verified'] == 0) {
        $response['status'] = 'otp_required';
        $response['message'] = 'Your account is not verified. Check your email for the OTP.';
        // Optionally, you can resend OTP here
        $_SESSION['pending_email'] = $customer['customer_email'];
        $_SESSION['pending_customer_id'] = $customer['customer_id'];
        echo json_encode($response);
        exit;
    }

    // Login successful → set session
    $_SESSION['role'] = 'customer';
    $_SESSION['customer_id'] = $customer['customer_id'];
    $_SESSION['customer_username'] = $customer['customer_username'];
    $_SESSION['customer_email'] = $customer['customer_email'];
    $_SESSION['customer_firstname'] = $customer['customer_firstname'];
    $_SESSION['customer_lastname'] = $customer['customer_lastname'];

    // Load saved cart if your DB method exists
    if (method_exists($db, 'loadCartFromDatabase')) {
        $db->loadCartFromDatabase();
    }

    $response['status'] = 'success';
    $response['message'] = 'Login successful.';
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Server error: ' . $e->getMessage();
    echo json_encode($response);
}
