<?php
$pageTitle = 'Provider Dashboard | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';
requireRole('provider');

// Fetch provider record
$stmt = $pdo->prepare('
    SELECT sp.* FROM service_providers sp
    WHERE sp.user_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$provider = $stmt->fetch();

// Fetch stats
$stmt = $pdo->prepare('
    SELECT
        COUNT(*) as total_jobs,
        SUM(CASE WHEN status = "pending"   THEN 1 ELSE 0 END) as pending_jobs,
        SUM(CASE WHEN status = "accepted"  THEN 1 ELSE 0 END) as active_jobs,
        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_jobs
    FROM job_requests WHERE provider_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Fetch recent job requests
$stmt = $pdo->prepare('
    SELECT jr.*, u.first_name, u.last_name, u.location, u.phone
    FROM job_requests jr
    JOIN users u ON jr.client_id = u.user_id
    WHERE jr.provider_id = ?
    ORDER BY jr.created_at DESC
    LIMIT 5
');
$stmt->execute([$_SESSION['user_id']]);
$jobs = $stmt->fetchAll();

// Handle job status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['status'])) {
    $allowed = ['accepted', 'declined', 'completed'];
    $new_status = $_POST['status'];
    if (in_array($new_status, $allowed)) {
        $stmt = $pdo->prepare('
            UPDATE job_requests SET status = ?
            WHERE job_id = ? AND provider_id = ?
        ');
        $stmt->execute([$new_status, $_POST['job_id'], $_SESSION['user_id']]);

        // If completed, increment jobs_completed counter
        if ($new_status === 'completed') {
            $pdo->prepare('
                UPDATE service_providers
                SET jobs_completed = jobs_completed + 1
                WHERE user_id = ?
            ')->execute([$_SESSION['user_id']]);
        }
    }
    header('Location: dashboard.php');
    exit();
}
?>
<?php require_once '../../includes/header.php'; ?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">
                Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!
            </h3>
            <?php if ($provider['verification_status'] === 'pending'): ?>
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Verification Pending — your profile won't appear in search until approved.
                </span>
            <?php elseif ($provider['verification_status'] === 'approved'): ?>
                <span class="badge bg-success">
                    <i class="bi bi-patch-check-fill me-1"></i>Verified Provider
                </span>
            <?php elseif ($provider['verification_status'] === 'rejected'): ?>
                <span class="badge bg-danger">
                    <i class="bi bi-x-circle me-1"></i>Verification Rejected — contact support.
                </span>
            <?php endif; ?>
        </div>
        <a href="profile.php" class="btn btn-primary">
            <i class="bi bi-person-gear me-2"></i>Edit Profile
        </a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Jobs</p>
                        <h3 class="fw-bold mb-0"><?= $stats['total_jobs'] ?></h3>
                    </div>
                    <i class="bi bi-briefcase fs-1 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Pending</p>
                        <h3 class="fw-bold mb-0"><?= $stats['pending_jobs'] ?></h3>
                    </div>
                    <i class="bi bi-hourglass-split fs-1 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Active</p>
                        <h3 class="fw-bold mb-0"><?= $stats['active_jobs'] ?></h3>
                    </div>
                    <i class="bi bi-lightning fs-1 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Completed</p>
                        <h3 class="fw-bold mb-0"><?= $stats['completed_jobs'] ?></h3>
                    </div>
                    <i class="bi bi-check-circle fs-1 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Recent Job Requests</h5>
            <a href="jobs.php" class="btn btn-sm btn-outline-secondary">View All</a>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">No job requests yet.</p>
                <p class="text-muted small">
                    Complete your profile to appear in search results.
                </p>
                <a href="profile.php" class="btn btn-primary">Complete Profile</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Location</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold small">
                                        <?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?>
                                    </div>
                                    <?php if ($job['phone']): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($job['phone']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($job['service_type']) ?></td>
                                <td><?= htmlspecialchars($job['location']) ?></td>
                                <td class="fw-semibold">
                                    R<?= number_format($job['budget'], 2) ?>
                                </td>
                                <td>
                                    <?php
                                    $badges = [
                                        'pending'   => 'warning',
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
                                <td>
                                    <?php if ($job['status'] === 'pending'): ?>
                                        <form method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="job_id"
                                                value="<?= $job['job_id'] ?>">
                                            <button name="status" value="accepted"
                                                class="btn btn-sm btn-success">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button name="status" value="declined"
                                                class="btn btn-sm btn-danger">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($job['status'] === 'accepted'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="job_id"
                                                value="<?= $job['job_id'] ?>">
                                            <button name="status" value="completed"
                                                class="btn btn-sm btn-primary">
                                                Mark Done
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>