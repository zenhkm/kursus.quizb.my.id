<?php
// layout/header.php
if (!isset($pageTitle)) {
    $pageTitle = 'Kursus Online';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f5f7fb;
        }
        .navbar-brand span {
            font-weight: 700;
        }
        .hero-section {
            padding: 40px 0 24px 0;
        }
        .hero-badge {
            font-size: 0.85rem;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e8f0ff;
            color: #2153ff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .course-card {
            border-radius: 16px;
            border: 1px solid #e0e4f0;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            background: #ffffff;
        }
        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
        }
        .course-status {
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 999px;
        }
        .status-tersedia {
            background: #e7f8ef;
            color: #13723d;
        }
        .status-segera {
            background: #fff4e5;
            color: #b56500;
        }
        .footer {
            font-size: 0.85rem;
            color: #6b7280;
            padding: 20px 0;
        }
        .section-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
        }
        .step-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    

    

<nav class="navbar navbar-light bg-white border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <span>Kursus</span>&nbsp;<span>Online</span>
        </a>
        <div class="d-flex align-items-center">
            <?php if (isset($_SESSION['user'])): ?>
                
                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                    <a href="index.php?page=admin" class="btn btn-danger btn-sm me-2">
                        ⚙️ Admin Panel
                    </a>
                <?php endif; ?>
                
                <a href="index.php?page=profile" class="btn btn-light btn-sm me-2 fw-semibold text-primary">
                    👤 <?= htmlspecialchars($_SESSION['user']['name']) ?>
                </a>
                <a href="auth.php?action=logout" class="btn btn-outline-secondary btn-sm">
                    Logout
                </a>
                
            <?php else: ?>
                <a href="auth.php?action=login" class="btn btn-outline-light btn-sm me-2">Login</a>
                <a href="auth.php?action=register" class="btn btn-warning btn-sm">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
