<?php
$pageTitle = 'Dashboard | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';
requireRole('client');

// Fetch client stats
$stmt = $pdo->prepare('
    SELECT 
        COUNT(*) as total_jobs,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_jobs,
        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_jobs
    FROM job_requests WHERE client_id = ?
');
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Fetch recent job requests
$stmt = $pdo->prepare('
    SELECT jr.*, u.first_name, u.last_name, sp.profile_picture
    FROM job_requests jr
    JOIN users u ON jr.provider_id = u.user_id
    LEFT JOIN service_providers sp ON sp.user_id = u.user_id
    WHERE jr.client_id = ?
    ORDER BY jr.created_at DESC
    LIMIT 5
');
$stmt->execute([$_SESSION['user_id']]);
$recent_jobs = $stmt->fetchAll();
?>
<?php require_once '../../includes/header.php'; ?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">
                Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>!
            </h3>
            <p class="text-muted mb-0">Here's what's happening with your bookings.</p>
        </div>
        <a href="search.php" class="btn btn-primary">
            <i class="bi bi-search me-2"></i>Find a Service
        </a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Bookings</p>
                        <h3 class="fw-bold mb-0"><?= $stats['total_jobs'] ?></h3>
                    </div>
                    <i class="bi bi-briefcase fs-1 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
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
        <div class="col-md-4">
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
            <h5 class="fw-bold mb-0">Recent Bookings</h5>
            <a href="bookings.php" class="btn btn-sm btn-outline-secondary">View All</a>
        </div>

        <?php if (empty($recent_jobs)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                <p class="text-muted mt-3">No bookings yet.</p>
                <a href="search.php" class="btn btn-primary">Find a Service</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Provider</th>
                            <th>Service</th>
                            <th>Location</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_jobs as $job): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?>
                                </td>
                                <td><?= htmlspecialchars($job['service_type']) ?></td>
                                <td><?= htmlspecialchars($job['location']) ?></td>
                                <td>R<?= number_format($job['budget'], 2) ?></td>
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
                                <td><?= date('d M Y', strtotime($job['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>