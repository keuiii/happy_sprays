<?php
session_start();
require_once 'classes/database.php';

$db = Database::getInstance();

// Check if admin is logged in
if (!$db->isLoggedIn() || $db->getCurrentUserRole() !== 'admin') {
    header("Location: customer_login.php");
    exit;
}

// Handle Mark as read
if (isset($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $db->updateContactMessageStatus($id, 'read');
    header("Location: admin_contact_messages.php?id=" . $id);
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->deleteContactMessage($id);
    header("Location: admin_contact_messages.php");
    exit;
}

// Get all contact messages - sorted by most recent activity (like Messenger)
$messages = $db->fetchAll("
    SELECT cm.*, 
           COALESCE(
               (SELECT MAX(cr.created_at) FROM contact_replies cr WHERE cr.message_id = cm.id),
               cm.created_at
           ) as last_activity,
           COALESCE(
               (SELECT cr.reply_message 
                FROM contact_replies cr 
                WHERE cr.message_id = cm.id 
                ORDER BY cr.created_at DESC 
                LIMIT 1),
               cm.message
           ) as latest_message,
           CASE 
               WHEN EXISTS(SELECT 1 FROM contact_replies cr WHERE cr.message_id = cm.id) THEN 'You: '
               ELSE ''
           END as message_prefix
    FROM contact_messages cm
    ORDER BY last_activity DESC
");
$unreadCount = $db->getUnreadContactCount();

// Get selected message for conversation view
$selectedMsg = null;
$selectedId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Auto-select first message if none selected and messages exist
if (!$selectedId && !empty($messages) && isset($messages[0]['id'])) {
    $selectedId = $messages[0]['id'];
}

if ($selectedId && !empty($messages)) {
    foreach ($messages as $msg) {
        if (isset($msg['id']) && $msg['id'] == $selectedId) {
            $selectedMsg = $msg;
            
            // Fetch all replies for this message from contact_replies table
            $replies = $db->fetchAll(
                "SELECT reply_id, message_id, admin_id, reply_message, created_at 
                 FROM contact_replies 
                 WHERE message_id = ? 
                 ORDER BY created_at ASC",
                [$selectedId]
            );
            $selectedMsg['replies'] = $replies ?: [];
            
            // Mark as read when viewing
            if (isset($msg['status']) && $msg['status'] === 'unread') {
                $db->updateContactMessageStatus($selectedId, 'read');
                $selectedMsg['status'] = 'read';
                // Refresh unread count
                $unreadCount = $db->getUnreadContactCount();
            }
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Happy Sprays Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f0f5;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: #fff;
            color: #333;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }

        .sidebar-header {
            padding: 20px 20px;
            border-bottom: 1px solid #e8e8e8;
            background: #fff;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .sidebar-header img {
            max-width: 120px;
            height: auto;
            display: block;
        }

        .sidebar-menu {
            padding: 30px 0;
        }

        .menu-item {
            padding: 16px 15px;
            color: #666;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 15px;
            margin: 4px 8px;
            border-radius: 10px;
            position: relative;
        }

        .menu-item::before {
            content: '○';
            font-size: 18px;
        }

        .menu-item:hover {
            background: #f5f5f5;
            color: #000;
        }

        .menu-item.active {
            background: #000;
            color: #fff;
        }

        .menu-item.active::before {
            content: '●';
        }

        .unread-badge {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #ef4444;
            color: #fff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 30px;
            width: 100%;
            padding: 0 8px;
        }

        .logout-item {
            padding: 16px 15px;
            color: #d32f2f;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 15px;
            margin: 4px 0;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .logout-item svg {
            stroke: #d32f2f;
        }

        .logout-item:hover {
            background: #ffebee;
        }

        /* Main Content - Messenger Layout */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            height: 100vh;
            background: #f0f0f5;
        }

        /* Left Panel - User List */
        .users-panel {
            width: 35%;
            max-width: 420px;
            background: #fff;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .users-header {
            padding: 25px 20px;
            border-bottom: 1px solid #e8e8e8;
            background: #fff;
        }

        .users-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: #000;
            margin-bottom: 15px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box::after {
            content: '🔍';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }

        .users-list {
            flex: 1;
            overflow-y: auto;
            background: #fafafa;
        }

        .user-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            text-decoration: none;
            color: inherit;
            background: #fff;
            position: relative;
        }

        .user-item:hover {
            background: #f8f9fa;
        }

        .user-item.active {
            background: #f5f5f5;
            border-left: 4px solid #000;
        }

        .user-item.unread {
            background: #f0f9ff;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
            margin-right: 8px;
        }

        .user-name {
            font-weight: 600;
            font-size: 15px;
            color: #212529;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-time {
            font-size: 12px;
            color: #999;
        }

        .user-preview {
            font-size: 13px;
            color: #6c757d;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-actions {
            display: none;
            position: relative;
            flex-shrink: 0;
        }

        .user-item:hover .user-actions {
            display: block;
        }

        .user-item:hover .unread-dot {
            display: none;
        }

        .user-menu-trigger {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid #ddd;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            background: #fff;
            color: #333;
            transition: all 0.2s;
            letter-spacing: -1px;
        }

        .user-menu-trigger:hover {
            background: #000;
            color: #fff;
            border-color: #000;
            transform: scale(1.1);
        }

        .user-dropdown {
            position: absolute;
            top: 35px;
            right: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            padding: 4px 0;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .user-dropdown.show {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        .user-dropdown-item {
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 18px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-size: 14px;
            color: #111;
            font-weight: 400;
        }

        .user-dropdown-item:hover {
            background: #f5f5f5;
        }

        .user-dropdown-item.delete {
            color: #dc2626;
        }

        .user-dropdown-item.delete:hover {
            background: #fee2e2;
        }

        .user-dropdown-item .icon {
            font-size: 18px;
            width: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-dropdown-divider {
            height: 1px;
            background: #e5e5e5;
            margin: 4px 0;
        }

        .unread-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #000;
            flex-shrink: 0;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .empty-users {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-users h3 {
            font-size: 18px;
            color: #666;
            margin-bottom: 8px;
        }

        /* Right Panel - Conversation Window */
        .conversation-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            height: 100vh;
        }

        .conversation-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e8e8e8;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .conversation-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .conversation-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
        }

        .conversation-details h3 {
            font-size: 16px;
            color: #212529;
            margin-bottom: 2px;
        }

        .conversation-details p {
            font-size: 13px;
            color: #6c757d;
        }

        /* Three Dots Menu */
        .conversation-actions {
            position: relative;
        }

        .menu-trigger {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e0e0e0;
            background: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            transition: all 0.2s;
            letter-spacing: -2px;
        }

        .menu-trigger:hover {
            background: #000;
            color: #fff;
            border-color: #000;
            transform: scale(1.05);
        }

        .dropdown-menu {
            position: absolute;
            top: 45px;
            right: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            padding: 4px 0;
            min-width: 220px;
            z-index: 1000;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-size: 14px;
            color: #111;
            font-weight: 400;
        }

        .dropdown-item:hover {
            background: #f5f5f5;
        }

        .dropdown-item.delete {
            color: #dc2626;
        }

        .dropdown-item.delete:hover {
            background: #fee2e2;
        }

        .dropdown-item .icon {
            font-size: 18px;
            width: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown-divider {
            height: 1px;
            background: #e5e5e5;
            margin: 4px 0;
        }

        .conversation-body {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message-bubble {
            max-width: 75%;
            margin-bottom: 20px;
        }

        .message-bubble.user {
            float: left;
            clear: both;
        }

        .message-bubble.admin {
            float: right;
            clear: both;
        }

        .bubble-content {
            padding: 14px 18px;
            border-radius: 18px;
            line-height: 1.5;
            font-size: 14px;
        }

        .message-bubble.user .bubble-content {
            background: #e9ecef;
            color: #212529;
            border-bottom-left-radius: 4px;
        }

        .message-bubble.admin .bubble-content {
            background: #000000;
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .bubble-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
            padding: 0 8px;
        }

        .message-bubble.user .bubble-time {
            text-align: left;
        }

        .message-bubble.admin .bubble-time {
            text-align: right;
        }

        .message-meta {
            font-size: 12px;
            color: #6c757d;
            margin-top: 8px;
            padding: 8px 18px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #3b82f6;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .conversation-footer {
            padding: 20px 30px;
            border-top: 1px solid #e8e8e8;
            background: #fff;
        }

        .reply-form {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .reply-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 22px;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            transition: all 0.3s;
        }

        .reply-input:focus {
            outline: none;
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }

        .btn-send {
            padding: 12px 24px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 22px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-send:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .empty-conversation {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #999;
        }

        .empty-conversation h3 {
            font-size: 20px;
            color: #666;
            margin-bottom: 8px;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 16px 24px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 500;
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
        }

        .toast.error {
            background: #ef4444;
        }

        .toast.success {
            background: #10b981;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .users-panel {
                width: 40%;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar-header h2,
            .sidebar-menu span,
            .logout-item span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .users-panel {
                width: 100%;
                max-width: none;
                position: absolute;
                left: 0;
                z-index: 10;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .users-panel.show {
                transform: translateX(0);
            }

            .conversation-panel {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="images/logoo.png" alt="Happy Sprays">
    </div>
    <nav class="sidebar-menu">
        <a href="admin_dashboard.php" class="menu-item">Dashboard</a>
        <a href="orders.php" class="menu-item">Orders</a>
        <a href="products_list.php" class="menu-item">Products</a>
        <a href="users.php" class="menu-item">Customers</a>
        <a href="admin_contact_messages.php" class="menu-item active">Messages<?php if ($unreadCount > 0): ?><span class="unread-badge"><?= $unreadCount ?></span><?php endif; ?></a>
    </nav>
    <div class="sidebar-footer">
        <a href="customer_logout.php" class="logout-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Log out
        </a>
    </div>
</div>

<!-- Main Content - Two Panel Layout -->
<div class="main-content">
    
    <!-- Left Panel - Users List -->
    <div class="users-panel">
        <div class="users-header">
            <h2>Messages</h2>
            <div class="search-box">
                <input type="text" id="searchUsers" placeholder="Search by name or email...">
            </div>
        </div>
        
        <div class="users-list">
            <?php if (empty($messages)): ?>
                <div class="empty-users">
                    <h3>No Messages Yet</h3>
                    <p>Customer messages will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <a href="?id=<?= isset($msg['id']) ? $msg['id'] : '' ?>" 
                       class="user-item <?= ($selectedMsg && isset($selectedMsg['id']) && isset($msg['id']) && $selectedMsg['id'] == $msg['id']) ? 'active' : '' ?> <?= (isset($msg['status']) && $msg['status'] === 'unread') ? 'unread' : '' ?>"
                       data-name="<?= isset($msg['name']) ? htmlspecialchars($msg['name']) : 'Unknown' ?>"
                       data-email="<?= isset($msg['email']) ? htmlspecialchars($msg['email']) : '' ?>">
                        <div class="user-avatar">
                            <?= isset($msg['name']) ? strtoupper(substr($msg['name'], 0, 1)) : '?' ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name">
                                <span><?= isset($msg['name']) ? htmlspecialchars($msg['name']) : 'Unknown' ?></span>
                                <span class="user-time"><?= isset($msg['last_activity']) ? date('g:i A', strtotime($msg['last_activity'])) : '' ?></span>
                            </div>
                            <div class="user-preview">
                                <?php 
                                $latestMsg = isset($msg['latest_message']) ? $msg['latest_message'] : '';
                                $prefix = isset($msg['message_prefix']) ? $msg['message_prefix'] : '';
                                $previewText = $prefix . $latestMsg;
                                echo htmlspecialchars(substr($previewText, 0, 60)) . (strlen($previewText) > 60 ? '...' : '');
                                ?>
                            </div>
                        </div>
                        <div class="user-actions">
                            <button class="user-menu-trigger" onclick="event.preventDefault(); event.stopPropagation(); toggleUserMenu(this);">
                                ⋮
                            </button>
                            <div class="user-dropdown">
                                <button class="user-dropdown-item" onclick="event.preventDefault(); event.stopPropagation(); markAsRead(<?= $msg['id'] ?>);">
                                    <span class="icon">✓</span>
                                    <span>Mark as read</span>
                                </button>
                                <div class="user-dropdown-divider"></div>
                                <button class="user-dropdown-item delete" onclick="event.preventDefault(); event.stopPropagation(); deleteConversation(<?= $msg['id'] ?>);">
                                    <span class="icon">✕</span>
                                    <span>Delete chat</span>
                                </button>
                            </div>
                        </div>
                        <?php if (isset($msg['status']) && $msg['status'] === 'unread'): ?>
                            <div class="unread-dot"></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Panel - Conversation Window -->
    <div class="conversation-panel">
        <?php if ($selectedMsg): ?>
            <!-- Conversation Header -->
            <div class="conversation-header">
                <div class="conversation-user">
                    <div class="conversation-avatar">
                        <?= isset($selectedMsg['name']) ? strtoupper(substr($selectedMsg['name'], 0, 1)) : '?' ?>
                    </div>
                    <div class="conversation-details">
                        <h3><?= isset($selectedMsg['name']) ? htmlspecialchars($selectedMsg['name']) : 'Unknown' ?></h3>
                        <p><?= isset($selectedMsg['email']) ? htmlspecialchars($selectedMsg['email']) : '' ?></p>
                    </div>
                </div>
                <div class="conversation-actions">
                    <button class="menu-trigger" onclick="toggleHeaderMenu()">
                        ⋮
                    </button>
                    <div class="dropdown-menu" id="headerMenu">
                        <button class="dropdown-item" onclick="markAsRead(<?= isset($selectedMsg['id']) ? $selectedMsg['id'] : 0 ?>);">
                            <span class="icon">✓</span>
                            <span>Mark as read</span>
                        </button>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item delete" onclick="deleteConversation(<?= isset($selectedMsg['id']) ? $selectedMsg['id'] : 0 ?>);">
                            <span class="icon">✕</span>
                            <span>Delete chat</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Conversation Body (Messages) -->
            <div class="conversation-body" id="conversationBody">
                <div class="clearfix">
                    <!-- User's Original Message -->
                    <div class="message-bubble user">
                        <div class="bubble-content">
                            <?= isset($selectedMsg['message']) ? nl2br(htmlspecialchars($selectedMsg['message'])) : 'No message content' ?>
                        </div>
                        <div class="bubble-time">
                            <?= isset($selectedMsg['created_at']) ? date('M d, Y \a\t g:i A', strtotime($selectedMsg['created_at'])) : '' ?>
                        </div>
                        <?php if (isset($selectedMsg['phone']) && !empty($selectedMsg['phone'])): ?>
                            <div class="message-meta">
                                📱 Phone: <?= htmlspecialchars($selectedMsg['phone']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Admin Replies (loop through all replies) -->
                    <?php if (isset($selectedMsg['replies']) && !empty($selectedMsg['replies'])): ?>
                        <?php foreach ($selectedMsg['replies'] as $reply): ?>
                            <div class="message-bubble admin">
                                <div class="bubble-content">
                                    <?= nl2br(htmlspecialchars($reply['reply_message'])) ?>
                                </div>
                                <div class="bubble-time">
                                    <?= date('M d, Y \a\t g:i A', strtotime($reply['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Conversation Footer (Reply Input) -->
            <div class="conversation-footer">
                <form class="reply-form" onsubmit="return sendReply(event, <?= isset($selectedMsg['id']) ? $selectedMsg['id'] : 0 ?>, '<?= isset($selectedMsg['email']) ? addslashes($selectedMsg['email']) : '' ?>')">
                    <textarea 
                        class="reply-input" 
                        id="reply-text-<?= isset($selectedMsg['id']) ? $selectedMsg['id'] : 0 ?>"
                        placeholder="Type your reply here..."
                        rows="1"
                        required></textarea>
                    <button type="submit" class="btn-send">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Send
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-conversation">
                <h3>Select a conversation</h3>
                <p>Choose a message from the list to view the conversation</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            ${type === 'success' 
                ? '<polyline points="20 6 9 17 4 12"></polyline>' 
                : '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>'}
        </svg>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Auto-resize textarea
document.querySelectorAll('.reply-input').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
});

// Search functionality
document.getElementById('searchUsers')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const userItems = document.querySelectorAll('.user-item');
    
    userItems.forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        const email = item.getAttribute('data-email').toLowerCase();
        
        if (name.includes(searchTerm) || email.includes(searchTerm)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
});

// Send reply function
function sendReply(event, messageId, customerEmail) {
    event.preventDefault();
    
    const textarea = document.getElementById('reply-text-' + messageId);
    const replyMessage = textarea.value.trim();
    
    if (!replyMessage) {
        showToast('Please enter a reply message.', 'error');
        return false;
    }
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path d="M12 2 A10 10 0 0 1 22 12" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></path></svg> Sending...';
    submitBtn.disabled = true;
    
    // Create form data
    const formData = new FormData();
    formData.append('customer_email', customerEmail);
    formData.append('reply_message', replyMessage);
    formData.append('message_id', messageId);
    
    // Send via AJAX
    fetch('send_reply.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create and append new reply bubble
            const replyDiv = document.createElement('div');
            replyDiv.className = 'message-bubble admin';
            replyDiv.innerHTML = `
                <div class="bubble-content">${replyMessage.replace(/\n/g, '<br>')}</div>
                <div class="bubble-time">${data.reply.formatted_time || 'Just now'}</div>
            `;
            document.querySelector('.conversation-body .clearfix').appendChild(replyDiv);
            
            // Clear textarea
            textarea.value = '';
            textarea.style.height = 'auto';
            
            // Scroll to bottom smoothly
            const conversationBody = document.getElementById('conversationBody');
            conversationBody.scrollTop = conversationBody.scrollHeight;
            
            // Show success toast with email status
            if (data.email_sent) {
                showToast('✅ Reply sent and email delivered!', 'success');
            } else {
                showToast('✅ Reply saved successfully!', 'success');
            }
        } else {
            showToast('❌ Failed to send reply: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('❌ Failed to send reply. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
    
    return false;
}

// Scroll conversation to bottom on page load if message is selected
window.addEventListener('DOMContentLoaded', function() {
    const conversationBody = document.getElementById('conversationBody');
    if (conversationBody) {
        conversationBody.scrollTop = conversationBody.scrollHeight;
    }
});

// Action Functions
function toggleHeaderMenu() {
    const menu = document.getElementById('headerMenu');
    menu.classList.toggle('show');
    
    // Close menu when clicking outside
    document.addEventListener('click', function closeMenu(e) {
        if (!e.target.closest('.conversation-actions')) {
            menu.classList.remove('show');
            document.removeEventListener('click', closeMenu);
        }
    });
}

function toggleUserMenu(button) {
    const dropdown = button.nextElementSibling;
    
    // Close all other dropdowns
    document.querySelectorAll('.user-dropdown.show').forEach(d => {
        if (d !== dropdown) d.classList.remove('show');
    });
    
    dropdown.classList.toggle('show');
    
    // Close menu when clicking outside
    document.addEventListener('click', function closeMenu(e) {
        if (!e.target.closest('.user-actions')) {
            dropdown.classList.remove('show');
            document.removeEventListener('click', closeMenu);
        }
    });
}

function markAsRead(messageId) {
    showToast('✓ Marked as read', 'success');
    setTimeout(() => {
        window.location.href = '?mark_read=' + messageId;
    }, 500);
}

function deleteConversation(messageId) {
    if (confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
        showToast('Deleting conversation...', 'success');
        window.location.href = '?delete=' + messageId;
    }
}   
</script>

</body>
</html>