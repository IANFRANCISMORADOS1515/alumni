<?php
session_start();
require_once 'db_con.php';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check admin credentials
        $admin = fetchRow("SELECT * FROM admins WHERE username = ? AND is_active = 1", [$username]);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Update last login
            query("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
            
            // Set session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandigan Colleges - Admin Login</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="style/main.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="icon" href="default/logo.png" type="image/x-icon" />
    <style>
        body {
            background: #f4f7fb;
        }
        .hero-section.login-hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(5, 90, 46, 0.85), rgba(10, 150, 57, 0.9)),
                        url('default/sample_school.jpg') center/cover no-repeat;
            color: white;
            position: relative;
            overflow: hidden;
            padding-top: 60px;
            padding-bottom: 60px;
        }
        .hero-section.login-hero::after {
            background: url('default/logo.png') center/60% no-repeat;
            opacity: 0.08;
        }
        .login-panel {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 20px;
            box-shadow: 0 25px 45px rgba(5, 90, 46, 0.35);
            border: 1px solid rgba(5, 90, 46, 0.15);
            overflow: hidden;
        }
        .login-panel .content-card {
            margin: 0;
            background: transparent;
            box-shadow: none;
        }
        .login-panel .form-control {
            border-radius: 12px;
            border: 1px solid #dfe3ea;
            padding: 14px;
            transition: border-color 0.3s ease;
        }
        .login-panel .form-control:focus {
            border-color: #1572e8;
            box-shadow: 0 0 0 0.2rem rgba(21, 114, 232, 0.25);
        }
        .login-panel .btn-login {
            width: 100%;
        }
        .login-panel .login-heading {
            margin-bottom: 1.5rem;
            letter-spacing: 0.2em;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #6c757d;
        }
        .login-panel .school-logo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #fff;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .login-panel .school-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .password-toggle {
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            font-size: 16px;
        }
        .helper-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 12px;
            text-align: center;
        }
        .hero-section .section-title {
            color: #fff;
        }
        @media (max-width: 991px) {
            .hero-section.login-hero {
                padding: 40px 0;
            }
        }
    </style>
</head>
<body>
    <section class="hero-section hero-watermark login-hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6 text-center text-lg-start">
                    <span class="pill">ADMIN PORTAL</span>
                    <h1 class="section-title mt-3 mb-3">Sandigan Colleges Incorporated</h1>
                    <p class="lead-copy text-white-75 mb-4">Manage alumni data, announcements, and events with a secure administrative hub styled after the SCI brand.</p>
                    <p class="text-white-50 mb-0">Last refreshed: March 4, 2026</p>
                </div>
                <div class="col-lg-6">
                    <div class="login-panel">
                        <div class="content-card p-4">
                            <div class="text-center">
                                <div class="school-logo">
                                    <img src="default/logo.png" alt="SCI Logo" onerror="this.style.display='none'">
                                </div>
                                <p class="login-heading">Administrator Login</p>
                            </div>
                            <?php if ($error): ?>
                                <div class="alert alert-danger d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" id="loginForm">
                                <div class="mb-3">
                                    <label class="form-label" for="username">
                                        <i class="fas fa-user me-1"></i>
                                        Username
                                    </label>
                                    <input type="text"
                                           id="username"
                                           name="username"
                                           class="form-control"
                                           required
                                           placeholder="Enter your username"
                                           value="<?= htmlspecialchars(isset($_POST['username']) ? $_POST['username'] : '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="password">
                                        <i class="fas fa-lock me-1"></i>
                                        Password
                                    </label>
                                    <div class="password-container position-relative">
                                        <input type="password"
                                               id="password"
                                               name="password"
                                               class="form-control"
                                               required
                                               placeholder="Enter your password">
                                        <button type="button"
                                                class="password-toggle position-absolute"
                                                onclick="togglePassword()"
                                                aria-label="Toggle password visibility">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-login mt-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Login to Dashboard
                                </button>
                                <p class="helper-text mb-0">Need assistance? Email <strong>admin@sandigan.edu.ph</strong></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const submitBtn = document.querySelector('.btn-login');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
            submitBtn.disabled = true;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            if (!usernameField.value) {
                usernameField.focus();
            } else {
                passwordField.focus();
            }
        });
    </script>
</body>
</html>
