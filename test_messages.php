<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

echo "<h2>Testing Messages System</h2>";

// Test 1: Check if logged in
echo "<h3>1. Login Status</h3>";
if ($db->isLoggedIn()) {
    echo "✅ User is logged in<br>";
    echo "Role: " . $db->getCurrentUserRole() . "<br>";
} else {
    echo "❌ Not logged in<br>";
}

// Test 2: Get messages
echo "<h3>2. Messages from Database</h3>";
$messages = $db->getAllContactMessages('created_at DESC');
echo "Total messages: " . count($messages) . "<br><br>";

if (!empty($messages)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Message</th><th>Created</th></tr>";
    foreach ($messages as $msg) {
        echo "<tr>";
        echo "<td>" . (isset($msg['id']) ? $msg['id'] : 'N/A') . "</td>";
        echo "<td>" . (isset($msg['name']) ? htmlspecialchars($msg['name']) : 'N/A') . "</td>";
        echo "<td>" . (isset($msg['email']) ? htmlspecialchars($msg['email']) : 'N/A') . "</td>";
        echo "<td>" . (isset($msg['status']) ? $msg['status'] : 'N/A') . "</td>";
        echo "<td>" . (isset($msg['message']) ? htmlspecialchars(substr($msg['message'], 0, 50)) . '...' : 'N/A') . "</td>";
        echo "<td>" . (isset($msg['created_at']) ? $msg['created_at'] : 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No messages found in database.";
}

// Test 3: Unread count
echo "<h3>3. Unread Count</h3>";
$unreadCount = $db->getUnreadContactCount();
echo "Unread messages: " . $unreadCount . "<br>";

// Test 4: Check database structure
echo "<h3>4. Available Array Keys</h3>";
if (!empty($messages)) {
    echo "Keys in first message: " . implode(', ', array_keys($messages[0])) . "<br>";
}
?>
