<?php
session_start();
require_once __DIR__ . "/classes/database.php";

$db = Database::getInstance();

// Protect page: must be logged in as customer
if (!$db->isUserLoggedIn() || $db->getCurrentUserRole() !== 'customer') {
    header("Location: customer_login.php");
    exit;
}

// Get customer data
$dashboardData = $db->getCustomerDashboardData();
$customer = $dashboardData['customer'];

// Handle form submissions
$success = '';
$error = '';

/* ---------------------- UPDATE PROFILE INFO ---------------------- */
if (isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $street = trim($_POST['street']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $postal_code = trim($_POST['postal_code']);
    
    if (empty($firstname) || empty($lastname) || empty($email)) {
        $error = 'First name, last name, and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $updated = $db->updateCustomerProfile($firstname, $lastname, $email, $contact, $street, $barangay, $city, $province, $postal_code);
        if ($updated) {
            $success = 'Profile updated successfully!';
            $dashboardData = $db->getCustomerDashboardData();
            $customer = $dashboardData['customer'];
        } else {
            $error = 'Failed to update profile. Email may already be in use.';
        }
    }
}

/* ---------------------- CHANGE PASSWORD ---------------------- */
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $error = 'Password must contain at least one special character.';
    } else {
        $changed = $db->changeCustomerPassword($current_password, $new_password);
        if ($changed) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

/* ---------------------- PROFILE PICTURE UPLOAD ---------------------- */
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_picture']['name'];
    $filesize = $_FILES['profile_picture']['size'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $error = 'Only JPG, JPEG, PNG & GIF files are allowed.';
    } elseif ($filesize > 5 * 1024 * 1024) {
        $error = 'File size must be less than 5MB.';
    } else {
        $upload_dir = __DIR__ . '/uploads/profiles/';
        $relative_dir = 'uploads/profiles/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_filename = 'profile_' . $_SESSION['customer_id'] . '_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;
        $relative_path = $relative_dir . $new_filename; // this is saved to DB

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            // Delete old profile picture if it exists
            if (!empty($customer['profile_picture']) && file_exists(__DIR__ . '/' . $customer['profile_picture'])) {
                unlink(__DIR__ . '/' . $customer['profile_picture']);
            }

            // ✅ Correct call and variables
            $updated = $db->updateProfilePicture($relative_path, $_SESSION['customer_id']);

            if ($updated) {
                $success = 'Profile picture updated successfully!';
                $dashboardData = $db->getCustomerDashboardData();
                $customer = $dashboardData['customer'];
            } else {
                $error = 'Failed to update profile picture in database.';
            }
        } else {
            $error = 'Failed to upload file.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - Happy Sprays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
* {margin: 0; padding: 0; box-sizing: border-box;}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #fff;
    color: #000;
    padding-top: 120px;
    min-height: 100vh;
}

/* Top Navbar */
.top-nav {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: #fff;
    border-bottom: 1px solid #eee;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
}

.top-nav .logo {
    flex: 1;
    text-align: center;
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.top-nav .logo a {
    color: #000;
    text-decoration: none;
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 25px;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}

.icon-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    outline: none;
}

.nav-icon svg {
    display: block;
    width: 22px;
    height: 23px;
    stroke: black;
}

.nav-icon:hover svg {
    stroke: #555;
}

.profile-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid #e0e0e0;
    text-decoration: none;
    color: #000;
    font-weight: 600;
    font-size: 14px;
}

.profile-icon:hover {
    background: #e0e0e0;
    border-color: #ccc;
    transform: scale(1.05);
}

.profile-icon.active {
    background: #000;
    color: #fff;
    border-color: #000;
}

.logout-btn {
    background: #000;
    color: #fff;
    border: none;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    letter-spacing: 0.5px;
}

.logout-btn:hover {
    background: #333;
}

/* Sub Nav */
.sub-nav {
    position: fixed;
    top: 60px;
    left: 0;
    width: 100%;
    background: #fff;
    border-bottom: 1px solid #ccc;
    text-align: center;
    padding: 12px 0;
    z-index: 999;
    font-family: 'Playfair Display', serif;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 1px;
}

.sub-nav a {
    margin: 0 20px;
    text-decoration: none;
    color: #000;
    font-size: 16px;
    transition: color 0.3s;
}

.sub-nav a:hover {
    color: #555;
}

/* Container */
.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Page Header */
.page-header {
    margin-bottom: 40px;
    text-align: center;
}

.page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 10px;
    color: #000;
}

.page-header p {
    color: #666;
    font-size: 16px;
}

/* Profile Picture Section */
.profile-picture-section {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
}

.profile-picture-container {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.profile-picture {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #e0e0e0;
    background: #f0f0f0;
}

.profile-picture-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #f0f0f0;
    border: 4px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    color: #666;
    font-family: 'Playfair Display', serif;
}

.upload-btn-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.upload-btn {
    background: #000;
    color: #fff;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    cursor: pointer;
    letter-spacing: 0.5px;
}

.upload-btn:hover {
    background: #333;
}

.upload-btn-wrapper input[type=file] {
    font-size: 100px;
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
}

/* Alert Messages */
.alert {
    padding: 15px 20px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
    font-size: 14px;
}

.alert-success {
    color: #0f5132;
    background-color: #d1e7dd;
    border-color: #badbcc;
}

.alert-error {
    color: #842029;
    background-color: #f8d7da;
    border-color: #f5c2c7;
}

/* Profile Sections */
.profile-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    padding: 30px;
    margin-bottom: 30px;
}

.profile-section h2 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
    color: #000;
}

/* Form Styling */
.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e0e0e0;
    font-size: 14px;
    font-family: inherit;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #000;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    padding-right: 45px;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
    transition: color 0.2s;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-password:hover {
    color: #000;
}

.eye-icon {
    width: 20px;
    height: 20px;
    display: inline-block;
}

.password-requirements {
    font-size: 13px;
    color: #333;
    margin-top: 8px;
    padding: 12px 14px;
    background: #f9f9f9;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    display: none;
}

.password-requirements.show {
    display: block;
}

.password-requirements strong {
    display: block;
    margin-bottom: 8px;
    color: #000;
    font-size: 13px;
}

.password-requirements ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.password-requirements li {
    margin: 6px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    font-size: 12px;
}

.password-requirements li .icon {
    font-size: 14px;
    min-width: 16px;
}

.requirement-met {
    color: #2e7d32;
}

.requirement-met .icon::before {
    content: '✔';
}

.requirement-unmet {
    color: #c62828;
}

.requirement-unmet .icon::before {
    content: '✖';
}

.password-success {
    font-size: 14px;
    color: #2e7d32;
    margin-top: 8px;
    padding: 12px 14px;
    background: #e8f5e9;
    border-radius: 8px;
    border: 1px solid #2e7d32;
    display: none;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.password-success.show {
    display: flex;
}

.password-success::before {
    content: '✅';
    font-size: 16px;
}

.form-actions {
    margin-top: 25px;
    display: flex;
    gap: 10px;
}

.btn {
    padding: 12px 30px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s;
    letter-spacing: 0.5px;
}

.btn-primary {
    background: #000;
    color: #fff;
}

.btn-primary:hover {
    background: #333;
}

.btn-secondary {
    background: #fff;
    color: #000;
    border: 1px solid #e0e0e0;
}

.btn-secondary:hover {
    background: #f9f9f9;
}

/* Footer */
footer {
    background: #000;
    border-top: 1px solid #000;
    padding: 40px 20px;
    text-align: center;
    font-size: 14px;
    color: #fff;
    margin-top: 60px;
}

.footer-columns {
    display: flex;
    justify-content: center;
    gap: 80px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.footer-columns h4 {
    font-size: 14px;
    margin-bottom: 12px;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.footer-columns a {
    display: block;
    text-decoration: none;
    color: #ccc;
    margin: 6px 0;
    font-size: 13px;
    transition: color 0.3s;
}

.footer-columns a:hover { color: #fff; }

.social-icons { 
    margin-top: 20px; 
}

.social-icons a {
    margin: 0 10px;
    color: #ccc;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

.social-icons a:hover { color: #fff; }

footer p {
    margin-top: 20px;
    color: #999;
    font-size: 12px;
}

/* Responsive */
@media(max-width:768px) {
    body {
        padding-top: 110px;
    }
    
    .top-nav .logo {
        font-size: 20px;
        letter-spacing: 1px;
    }
    
    .nav-actions {
        gap: 15px;
    }
    
    .logout-btn {
        padding: 8px 14px;
        font-size: 11px;
    }
    
    .sub-nav {
        padding: 10px 0;
    }
    
    .sub-nav a {
        margin: 0 10px;
        font-size: 14px;
    }
    
    .container {
        padding: 30px 15px;
    }
    
    .page-header h1 {
        font-size: 28px;
    }
    
    .profile-section {
        padding: 20px 15px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .footer-columns {
        gap: 40px;
    }
}
</style>
</head>
<body>

<div class="top-nav">
    <div class="logo"><a href="index.php">Happy Sprays</a></div>
    <div class="nav-actions">
        <a href="index.php" class="nav-icon icon-btn" title="Home">
            <svg width="22" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
        </a>
        <a href="cart.php" class="nav-icon icon-btn" title="Cart">
            <svg width="22" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
        </a>
        <a href="customer_profile.php" class="profile-icon active" title="Profile">
            <?= strtoupper(substr($customer['customer_firstname'], 0, 1)) ?>
        </a>
        <a href="customer_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="sub-nav">
    <a href="customer_dashboard.php">Dashboard</a>
    <a href="my_orders.php">My Orders</a>
    <a href="index.php">Shop</a>
</div>

<div class="container">
    <div class="page-header">
        <h1>My Profile</h1>
        <p>Manage your account information and preferences</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Profile Picture Section -->
    <div class="profile-picture-section">
        <div class="profile-picture-container">
            <?php if (!empty($customer['profile_picture']) && file_exists($customer['profile_picture'])): ?>
                <img src="<?= htmlspecialchars($customer['profile_picture']) ?>" alt="Profile Picture" class="profile-picture">
            <?php else: ?>
                <div class="profile-picture-placeholder">
                    <?= strtoupper(substr($customer['customer_firstname'], 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        <form method="post" enctype="multipart/form-data">
            <div class="upload-btn-wrapper">
                <button class="upload-btn" type="button">Change Profile Picture</button>
                <input type="file" name="profile_picture" accept="image/*" onchange="this.form.submit()">
            </div>
        </form>
        <p style="margin-top: 10px; font-size: 13px; color: #666;">JPG, PNG, or GIF (max 5MB)</p>
    </div>

    <!-- Profile Information Section -->
    <div class="profile-section">
        <h2>Profile Information</h2>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstname">First Name *</label>
                    <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($customer['customer_firstname']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name *</label>
                    <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($customer['customer_lastname']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($customer['customer_email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($customer['customer_contact'] ?? '') ?>" placeholder="+63 912 345 6789">
            </div>
            
            <h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 18px; color: #333;">Delivery Address</h3>
            
            <div class="form-group">
                <label for="street">Street Address</label>
                <input type="text" id="street" name="street" value="<?= htmlspecialchars($customer['customer_street'] ?? '') ?>" placeholder="House/Building No., Street Name">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="barangay">Barangay</label>
                    <input type="text" id="barangay" name="barangay" value="<?= htmlspecialchars($customer['customer_barangay'] ?? '') ?>" placeholder="Enter barangay">
                </div>
                <div class="form-group">
                    <label for="city">City/Municipality</label>
                    <input type="text" id="city" name="city" value="<?= htmlspecialchars($customer['customer_city'] ?? '') ?>" placeholder="Enter city">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="province">Province</label>
                    <input type="text" id="province" name="province" value="<?= htmlspecialchars($customer['customer_province'] ?? '') ?>" placeholder="Enter province">
                </div>
                <div class="form-group">
                    <label for="postal_code">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" value="<?= htmlspecialchars($customer['customer_postal_code'] ?? '') ?>" placeholder="e.g., 1000">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                <button type="reset" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Change Password Section -->
    <div class="profile-section">
        <h2>Change Password</h2>
        <form method="post">
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <div class="password-wrapper">
                    <input type="password" id="current_password" name="current_password" required>
                    <span class="toggle-password" onclick="togglePassword('current_password', this)">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password *</label>
                <div class="password-wrapper">
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                    <span class="toggle-password" onclick="togglePassword('new_password', this)">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </div>
                <div class="password-requirements" id="passwordRequirements">
                    <strong>Password must contain:</strong>
                    <ul>
                        <li class="requirement-unmet"><span class="icon"></span><span>At least 8 characters</span></li>
                        <li class="requirement-unmet"><span class="icon"></span><span>One uppercase letter (A-Z)</span></li>
                        <li class="requirement-unmet"><span class="icon"></span><span>One lowercase letter (a-z)</span></li>
                        <li class="requirement-unmet"><span class="icon"></span><span>One number (0-9)</span></li>
                        <li class="requirement-unmet"><span class="icon"></span><span>One special character (!@#$%^&*)</span></li>
                    </ul>
                </div>
                <div class="password-success" id="passwordSuccess">
                    Password is strong!
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    <span class="toggle-password" onclick="togglePassword('confirm_password', this)">
                        <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </span>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId, iconElement) {
    const field = document.getElementById(fieldId);
    const svg = iconElement.querySelector('svg');
    
    if (field.type === 'password') {
        field.type = 'text';
        // Eye (visible) - password is now shown
        svg.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        `;
    } else {
        field.type = 'password';
        // Eye with slash (hidden) - password is now hidden
        svg.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('new_password');
    const confirmField = document.getElementById('confirm_password');
    const requirementsBox = document.getElementById('passwordRequirements');
    const successBox = document.getElementById('passwordSuccess');
    
    if (passwordField) {
        // Show requirements when user focuses on password field
        passwordField.addEventListener('focus', function() {
            if (this.value.length === 0) {
                requirementsBox.classList.add('show');
                successBox.classList.remove('show');
            }
        });
        
        // Hide both when user leaves password field and it's empty
        passwordField.addEventListener('blur', function() {
            if (this.value.length === 0) {
                requirementsBox.classList.remove('show');
                successBox.classList.remove('show');
            }
        });
        
        // Validate as user types
        passwordField.addEventListener('input', function() {
            validatePasswordStrength(this.value);
        });
    }
    
    if (confirmField) {
        confirmField.addEventListener('input', function() {
            validatePasswordMatch();
        });
    }
});

function validatePasswordStrength(password) {
    const requirements = document.querySelectorAll('.password-requirements li');
    const requirementsBox = document.getElementById('passwordRequirements');
    const successBox = document.getElementById('passwordSuccess');
    if (requirements.length === 0) return;
    
    const checks = [
        password.length >= 8,
        /[A-Z]/.test(password),
        /[a-z]/.test(password),
        /[0-9]/.test(password),
        /[^A-Za-z0-9]/.test(password)
    ];
    
    requirements.forEach((req, index) => {
        if (checks[index]) {
            req.classList.remove('requirement-unmet');
            req.classList.add('requirement-met');
        } else {
            req.classList.remove('requirement-met');
            req.classList.add('requirement-unmet');
        }
    });
    
    // Check if all requirements are met
    const allMet = checks.every(check => check === true);
    
    if (allMet) {
        // Hide requirements, show success
        requirementsBox.classList.remove('show');
        successBox.classList.add('show');
    } else {
        // Show requirements, hide success
        requirementsBox.classList.add('show');
        successBox.classList.remove('show');
    }
}

function validatePasswordMatch() {
    const password = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    const confirmField = document.getElementById('confirm_password');
    
    if (confirm.length > 0) {
        if (password !== confirm) {
            confirmField.style.borderColor = '#c62828';
        } else {
            confirmField.style.borderColor = '#2e7d32';
        }
    } else {
        confirmField.style.borderColor = '#e0e0e0';
    }
}
</script>

<!-- Footer -->
<footer>
    <div class="footer-columns">
        <div>
            <h4>Company</h4>
            <a href="about.php">About</a>
            <a href="reviews.php">Reviews</a>
        </div>
        <div>
            <h4>Customer Service</h4>
            <a href="faq.php">FAQ</a>
            <a href="contact.php">Contact</a>
        </div>
    </div>
    <div class="social-icons">
        <a href="https://www.facebook.com/thethriftbytf">Facebook</a>
        <a href="https://www.instagram.com/thehappysprays/">Instagram</a>
    </div>
    <p>© 2025 Happy Sprays. All rights reserved.</p>
</footer>

</body>
</html>
