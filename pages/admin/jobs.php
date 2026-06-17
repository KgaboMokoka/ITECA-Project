<?php
$pageTitle = 'Manage Jobs | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';
requireRole('admin');

// Filter
$filter_status = trim($_GET['status'] ?? '');
$search        = trim($_GET['search'] ?? '');
$where         = [];
$params        = [];

if ($filter_status) {
    $where[]  = 'jr.status = ?';
    $params[] = $filter_status;
}
if ($search) {
    $where[]  = '(c.first_name LIKE ? OR c.last_name LIKE ? OR p.first_name LIKE ? OR jr.service_type LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT jr.*,
           c.first_name AS client_first, c.last_name AS client_last,
           p.first_name AS provider_first, p.last_name AS provider_last
    FROM job_requests jr
    JOIN users c ON jr.client_id    = c.user_id
    JOIN users p ON jr.provider_id  = p.user_id
    $whereSQL
    ORDER BY jr.created_at DESC
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();
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
                    <a class="nav-link" href="verifications.php">
                        <i class="bi bi-patch-check me-2"></i>Verifications
                    </a>
                    <a class="nav-link active" href="jobs.php">
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
                    <h3 class="fw-bold mb-1">Job Requests</h3>
                    <p class="text-muted mb-0"><?= count($jobs) ?> jobs found</p>
                </div>
            </div>

            <div class="card p-3 mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by client, provider or service..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <?php
                            $statuses = ['pending', 'accepted', 'declined', 'completed', 'cancelled'];
                            foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>"
                                    <?= $filter_status === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="jobs.php" class="btn btn-outline-secondary">
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
                                <th>#</th>
                                <th>Client</th>
                                <th>Provider</th>
                                <th>Service</th>
                                <th>Location</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jobs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No jobs found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($jobs as $job): ?>
                                    <tr>
                                        <td class="text-muted small"><?= $job['job_id'] ?></td>
                                        <td class="small fw-semibold">
                                            <?= htmlspecialchars($job['client_first'] . ' ' . $job['client_last']) ?>
                                        </td>
                                        <td class="small fw-semibold">
                                            <?= htmlspecialchars($job['provider_first'] . ' ' . $job['provider_last']) ?>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($job['service_type']) ?></td>
                                        <td class="small"><?= htmlspecialchars($job['location']) ?></td>
                                        <td class="small fw-semibold">
                                            R<?= number_format($job['budget'], 2) ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'pending'   => 'warning text-dark',
                                                'accepted'  => 'success',
                                                'declined'  => 'danger',
                                                'completed' => 'primary',
                                                'cancelled' => 'secondary',
                                            ];
                                            $badge = $badges[$job['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $badge ?>">
                                                <?= ucfirst($job['status']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y', strtotime($job['created_at'])) ?>
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