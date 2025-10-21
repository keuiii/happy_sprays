<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

try {
    // Only proceed if customer is logged in
    if (isset($_SESSION['customer_id'])) {
        session_unset();
        session_destroy();
        echo json_encode([
            'status' => 'success',
            'message' => 'Logged out successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No active session.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
