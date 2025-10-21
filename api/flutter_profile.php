<?php
session_start();
require_once __DIR__ . "/classes/database.php";

header('Content-Type: application/json');

$db = Database::getInstance();

// Protect page: must be logged in as customer
if (!$db->isUserLoggedIn() || $db->getCurrentUserRole() !== 'customer') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

// Get customer ID
$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Customer ID missing']);
    exit;
}

// Handle GET request: fetch profile
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $dashboardData = $db->getCustomerDashboardData();
    $customer = $dashboardData['customer'];

    // Add full URL for profile picture
    if (!empty($customer['profile_picture']) && file_exists($customer['profile_picture'])) {
        $customer['profile_picture_url'] = "http://localhost/happy_sprays/" . $customer['profile_picture'];
    } else {
        $customer['profile_picture_url'] = null;
    }

    echo json_encode([
        'status' => 'success',
        'customer' => $customer
    ]);
    exit;
}

// Handle POST request: update profile or password
$input = json_decode(file_get_contents('php://input'), true);
$response = ['status' => 'error', 'message' => 'Invalid request'];

// Update profile info
if (isset($input['update_profile'])) {
    $firstname = trim($input['firstname'] ?? '');
    $lastname = trim($input['lastname'] ?? '');
    $email = trim($input['email'] ?? '');
    $contact = trim($input['contact'] ?? '');
    $street = trim($input['street'] ?? '');
    $barangay = trim($input['barangay'] ?? '');
    $city = trim($input['city'] ?? '');
    $province = trim($input['province'] ?? '');
    $postal_code = trim($input['postal_code'] ?? '');

    if (empty($firstname) || empty($lastname) || empty($email)) {
        $response['message'] = 'First name, last name, and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address.';
    } else {
        $updated = $db->updateCustomerProfile($firstname, $lastname, $email, $contact, $street, $barangay, $city, $province, $postal_code);
        if ($updated) {
            $response = ['status' => 'success', 'message' => 'Profile updated successfully!'];
        } else {
            $response['message'] = 'Failed to update profile. Email may already exist.';
        }
    }

    echo json_encode($response);
    exit;
}

// Change password
if (isset($input['change_password'])) {
    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $response['message'] = 'New passwords do not match.';
    } else {
        $changed = $db->changeCustomerPassword($current_password, $new_password);
        if ($changed) {
            $response = ['status' => 'success', 'message' => 'Password changed successfully!'];
        } else {
            $response['message'] = 'Current password is incorrect.';
        }
    }

    echo json_encode($response);
    exit;
}
