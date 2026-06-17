<?php require_once __DIR__ . '/../includes/session.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Handyman Hub' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/handyman-hub/assets/css/style.css" rel="stylesheet">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/handyman-hub/index.php">
                <i class="bi bi-tools me-2"></i>Handyman Hub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <span class="nav-link text-light">
                                Hi, <?= htmlspecialchars($_SESSION['first_name']) ?>
                            </span>
                        </li>

                        <?php if ($_SESSION['role'] === 'client'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/handyman-hub/pages/client/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/handyman-hub/pages/client/search.php">Find Services</a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'provider'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/handyman-hub/pages/provider/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/handyman-hub/pages/provider/profile.php">My Profile</a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/handyman-hub/pages/admin/dashboard.php">Admin Panel</a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm ms-2 px-3"
                                href="/handyman-hub/auth/logout.php">Logout</a>
                        </li>

                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/handyman-hub/index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/handyman-hub/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-warning btn-sm ms-2 px-3 text-dark fw-bold"
                                href="/handyman-hub/auth/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>