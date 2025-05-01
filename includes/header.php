<?php
ob_start();
session_start();
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SociVerse</title>
    <link rel="icon" type="image/jpg" href="assets/sociverse logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@emoji-mart/css@latest/css/emoji-mart.css">
    <script src="https://cdn.jsdelivr.net/npm/@emoji-mart/data@latest/sets/14/native.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@emoji-mart/react@latest/dist/browser.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #f0f2f5;
        }
        body {
            background-color: var(--secondary-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
        }
        .home-btn {
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        .notificationbadge {
            font-size: 0.6rem;
            top: -5px !important;
            right: -5px !important;
            background-color: #ef4444;
            border: 2px solid #fff;
        }
        #notificationList {
            width: 320px;
            max-height: 480px;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .notification-item {
            border-left: 3px solid transparent;
            transition: all 0.3s;
            padding: 0.75rem;
        }
        .notification-item.unread {
            border-left-color: var(--primary-color);
            background-color: rgba(99, 102, 241, 0.05);
        }
        .notification-item:hover {
            background-color: #f1f5f9;
        }
        .notification-time {
            font-size: 0.75rem;
            color: #6b7280;
        }
        .search-form {
            position: relative;
        }
        .search-form .form-control {
            border-radius: 20px;
            padding-right: 40px;
            background-color: #f1f5f9;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }
        .search-form .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }
        .search-form .btn {
            position: absolute;
            right: 4px;
            top: 4px;
            border-radius: 50%;
            width: 32px;  
            height: 32px; 
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-color);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        
        .search-form .btn:hover {
            background: var(--primary-hover);
            transform: scale(1.05);
        }
        
        .search-form .btn i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        
        .search-form .btn:hover i {
            transform: scale(1.1);
        }
        @media (max-width: 768px) {
            .navbar-collapse {
                background: rgba(255, 255, 255, 0.98);
                padding: 1rem;
                border-radius: 0 0 1rem 1rem;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }

            .navbar .d-flex {
                flex-direction: column;
                width: 100%;
                gap: 0.5rem;
            }

            .navbar .btn {
                width: 100%;
                margin: 0.25rem 0;
            }

            .search-form {
                order: -1;
                width: 100%;
            }

            .navbar-toggler {
                padding: 0.4rem;
                border: none;
            }

            .navbar-toggler:focus {
                box-shadow: none;
            }

            .search-form .btn {
                width: 28px;  /* Even smaller on mobile */
                height: 28px;
                right: 3px;
                top: 3px;
            }
            
            .search-form .btn i {
                font-size: 0.875rem; /* Smaller icon on mobile */
            }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <a href="index.php" class="home-btn me-3">
                <i class="fas fa-home fa-lg"></i>
            </a>
            
            <a class="navbar-brand me-auto" href="index.php">SociVerse</a>
            
            <form class="d-flex ms-3 search-form" action="search.php" method="GET">
                <input class="form-control me-2" type="search" 
                        name="q" placeholder="ابحث عن مستخدمين...">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="fas fa-magnifying-glass"></i>
                </button>
            </form>

            <div class="d-flex align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- زر الإشعارات -->
                    <div class="dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                                0
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" id="notificationList">
                            <li class="dropdown-item text-center text-muted">لا توجد إشعارات جديدة</li>
                        </ul>
                    </div>
                    <!-- زر الرسائل -->
                    <div class="position-relative me-3">
                        <a href="messages.php" class="btn btn-outline-primary">
                            <i class="fas fa-envelope"></i>
                            <?php if(check_new_messages()): ?>
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                    <span class="visually-hidden">رسائل جديدة</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <a href="profile.php" class="btn btn-outline-primary me-3">
                        <i class="fas fa-user"></i> الملف الشخصي
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger me-2">
                            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> تسجيل جديد
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('notificationDropdown')) {
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationBadge = document.getElementById('notificationBadge');
        const notificationList = document.getElementById('notificationList');

        // Fetch notifications every 30 seconds
        fetchNotifications();
        setInterval(fetchNotifications, 30000);

        // Mark notifications as read when dropdown is opened
        notificationDropdown.addEventListener('click', function() {
            if (notificationBadge.style.display !== 'none') {
                markNotificationsAsRead();
            }
        });

        async function fetchNotifications() {
            try {
                const response = await fetch('check_notifications.php?_=' + Date.now());
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();

                if (data.success) {
                    updateNotificationUI(data);
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
            }
        }

        function updateNotificationUI(data) {
            if (data.unread > 0) {
                notificationBadge.style.display = 'block';
                notificationBadge.textContent = data.unread;
                
                notificationList.innerHTML = data.notifications.map(notif => `
                    <li class="dropdown-item">
                        <a href="${notif.type === 'message' ? 'messages.php?with=' + notif.sender_id : 'profile.php?id=' + notif.sender_id}" 
                           class="d-flex align-items-center text-decoration-none text-dark">
                            <img src="uploads/${notif.sender_pic}" class="rounded-circle me-2" width="40" height="40">
                            <div>
                                <strong>${notif.sender_name}</strong>
                                <p class="small mb-0">${notif.content}</p>
                                <small class="text-muted">${notif.time_ago}</small>
                            </div>
                        </a>
                    </li>
                `).join('');
            } else {
                notificationBadge.style.display = 'none';
                notificationList.innerHTML = '<li class="dropdown-item text-center text-muted">لا توجد إشعارات جديدة</li>';
            }
        }

        async function markNotificationsAsRead() {
            try {
                const response = await fetch('mark_notifications_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    notificationBadge.style.display = 'none';
                }
            } catch (error) {
                console.error('Error marking notifications as read:', error);
            }
        }
    }
});

</script>

</body>
</html>