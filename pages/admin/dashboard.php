<?php
$pageTitle = 'Admin Dashboard | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';
requireRole('admin');

// Platform stats
$stats = [];
$stats['total_users']     = $pdo->query('SELECT COUNT(*) FROM users WHERE role != "admin"')->fetchColumn();
$stats['total_providers'] = $pdo->query('SELECT COUNT(*) FROM service_providers')->fetchColumn();
$stats['total_clients']   = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "client"')->fetchColumn();
$stats['total_jobs']      = $pdo->query('SELECT COUNT(*) FROM job_requests')->fetchColumn();
$stats['pending_verify']  = $pdo->query('SELECT COUNT(*) FROM service_providers WHERE verification_status = "pending"')->fetchColumn();
$stats['completed_jobs']  = $pdo->query('SELECT COUNT(*) FROM job_requests WHERE status = "completed"')->fetchColumn();

// Recent users
$recent_users = $pdo->query('
    SELECT * FROM users
    WHERE role != "admin"
    ORDER BY created_at DESC
    LIMIT 5
')->fetchAll();

// Pending verifications
$pending = $pdo->query('
    SELECT sp.*, u.first_name, u.last_name, u.email, u.location, u.created_at
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    WHERE sp.verification_status = "pending"
    ORDER BY u.created_at DESC
    LIMIT 5
')->fetchAll();

// Handle verification action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['provider_id'])) {
    $action      = $_POST['action'];
    $provider_id = intval($_POST['provider_id']);
    $allowed     = ['approved', 'rejected'];

    if (in_array($action, $allowed)) {
        $pdo->prepare('
            UPDATE service_providers SET verification_status = ?
            WHERE provider_id = ?
        ')->execute([$action, $provider_id]);
    }
    header('Location: dashboard.php');
    exit();
}
?>
<?php require_once '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">

        <div class="col-lg-2 d-none d-lg-block">
            <div class="sidebar rounded-3 p-3">
                <p class="text-muted small px-2 mb-2 fw-semibold">NAVIGATION</p>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                    <a class="nav-link" href="verifications.php">
                        <i class="bi bi-patch-check me-2"></i>Verifications
                        <?php if ($stats['pending_verify'] > 0): ?>
                            <span class="badge bg-warning text-dark ms-1">
                                <?= $stats['pending_verify'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link" href="jobs.php">
                        <i class="bi bi-briefcase me-2"></i>Jobs
                    </a>
                    <a class="nav-link" href="reviews.php">
                        <i class="bi bi-star me-2"></i>Reviews
                    </a>
                </nav>
            </div>
        </div>

        <div class="col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Admin Dashboard</h3>
                    <p class="text-muted mb-0">
                        Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>
                    </p>
                </div>
                <a href="users.php?action=create" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Add User
                </a>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card stat-card p-3 text-center">
                        <i class="bi bi-people fs-2 text-warning mb-2"></i>
                        <h4 class="fw-bold mb-0"><?= $stats['total_users'] ?></h4>
                        <small class="text-muted">Total Users</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card stat-card p-3 text-center">
                        <i class="bi bi-tools fs-2 text-warning mb-2"></i>
                        <h4 class="fw-bold mb-0"><?= $stats['total_providers'] ?></h4>
                        <small class="text-muted">Providers</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card stat-card p-3 text-center">
                        <i class="bi bi-person-circle fs-2 text-warning mb-2"></i>
                        <h4 class="fw-bold mb-0"><?= $stats['total_clients'] ?></h4>
                        <small class="text-muted">Clients</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card stat-card p-3 text-center">
                        <i class="bi bi-briefcase fs-2 text-warning mb-2"></i>
                        <h4 class="fw-bold mb-0"><?= $stats['total_jobs'] ?></h4>
                        <small class="text-muted">Total Jobs</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card stat-card p-3 text-center">
                        <i class="bi bi-check-circle fs-2 text-warning mb-2"></i>
                        <h4 class="fw-bold mb-0"><?= $stats['completed_jobs'] ?></h4>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card stat-card p-3 text-center">
                        <i class="bi bi-hourglass-split fs-2 text-warning mb-2"></i>
                        <h4 class="fw-bold mb-0"><?= $stats['pending_verify'] ?></h4>
                        <small class="text-muted">Pending Verify</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-6">
                    <div class="card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Pending Verifications</h5>
                            <a href="verifications.php"
                                class="btn btn-sm btn-outline-secondary">View All</a>
                        </div>

                        <?php if (empty($pending)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-patch-check fs-1 text-muted"></i>
                                <p class="text-muted mt-2">No pending verifications.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending as $p): ?>
                                <div class="d-flex align-items-center justify-content-between
                                        border-bottom pb-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-warning d-flex align-items-center
                                                justify-content-center me-3 fw-bold text-dark"
                                            style="width:40px;height:40px;">
                                            <?= strtoupper(substr($p['first_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small">
                                                <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($p['email']) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <form method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="provider_id"
                                            value="<?= $p['provider_id'] ?>">
                                        <button name="action" value="approved"
                                            class="btn btn-sm btn-success"
                                            title="Approve">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button name="action" value="rejected"
                                            class="btn btn-sm btn-danger"
                                            title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Recent Users</h5>
                            <a href="users.php"
                                class="btn btn-sm btn-outline-secondary">View All</a>
                        </div>

                        <?php foreach ($recent_users as $user): ?>
                            <div class="d-flex align-items-center justify-content-between
                                    border-bottom pb-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center
                                            justify-content-center me-3 fw-bold text-white"
                                        style="width:40px;height:40px;">
                                        <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">
                                            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($user['email']) ?>
                                        </small>
                                    </div>
                                </div>
                                <span class="badge bg-<?= $user['role'] === 'provider' ? 'warning text-dark' : 'info text-dark' ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>