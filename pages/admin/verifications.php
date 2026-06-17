<?php
$pageTitle = 'Verifications | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';
requireRole('admin');

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['provider_id'])) {
    $allowed = ['approved', 'rejected'];
    if (in_array($_POST['action'], $allowed)) {
        $pdo->prepare('
            UPDATE service_providers SET verification_status = ?
            WHERE provider_id = ?
        ')->execute([$_POST['action'], intval($_POST['provider_id'])]);
    }
    header('Location: verifications.php');
    exit();
}

// Filter
$filter = trim($_GET['status'] ?? '');
$where  = $filter ? 'WHERE sp.verification_status = ?' : '';
$params = $filter ? [$filter] : [];

$stmt = $pdo->prepare("
    SELECT sp.*, u.first_name, u.last_name, u.email, u.phone, u.location, u.created_at
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.user_id
    $where
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$providers = $stmt->fetchAll();
?>
<?php require_once '../../includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">

        <div class="col-lg-2 d-none d-lg-block">
            <div class="sidebar rounded-3 p-3">
                <p class="text-muted small px-2 mb-2 fw-semibold">NAVIGATION</p>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                    <a class="nav-link active" href="verifications.php">
                        <i class="bi bi-patch-check me-2"></i>Verifications
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
                    <h3 class="fw-bold mb-1">Provider Verifications</h3>
                    <p class="text-muted mb-0"><?= count($providers) ?> providers found</p>
                </div>
            </div>

            <div class="card p-3 mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $filter === 'pending'  ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="verifications.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="card p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Provider</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Location</th>
                                <th>Services</th>
                                <th>Rate</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($providers)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        No providers found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($providers as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-warning d-flex
                                                        align-items-center justify-content-center
                                                        me-2 fw-bold text-dark"
                                                    style="width:32px;height:32px;font-size:0.85rem;">
                                                    <?= strtoupper(substr($p['first_name'], 0, 1)) ?>
                                                </div>
                                                <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                            </div>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($p['email']) ?></td>
                                        <td class="small"><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                                        <td class="small"><?= htmlspecialchars($p['location'] ?? '—') ?></td>
                                        <td class="small">
                                            <?= htmlspecialchars(substr($p['services_offered'] ?? '—', 0, 40)) ?>
                                        </td>
                                        <td class="small">
                                            <?= $p['hourly_rate'] ? 'R' . number_format($p['hourly_rate'], 0) . '/hr' : '—' ?>
                                        </td>
                                        <td>
                                            <?php
                                            $vs_colors = [
                                                'pending'  => 'warning text-dark',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                            ];
                                            $vc = $vs_colors[$p['verification_status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $vc ?>">
                                                <?= ucfirst($p['verification_status']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y', strtotime($p['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($p['verification_status'] === 'pending'): ?>
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
                                            <?php elseif ($p['verification_status'] === 'approved'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="provider_id"
                                                        value="<?= $p['provider_id'] ?>">
                                                    <button name="action" value="rejected"
                                                        class="btn btn-sm btn-outline-danger">
                                                        Revoke
                                                    </button>
                                                </form>
                                            <?php elseif ($p['verification_status'] === 'rejected'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="provider_id"
                                                        value="<?= $p['provider_id'] ?>">
                                                    <button name="action" value="approved"
                                                        class="btn btn-sm btn-outline-success">
                                                        Re-approve
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>