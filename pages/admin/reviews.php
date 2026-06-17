<?php
$pageTitle = 'Reviews | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';
requireRole('admin');

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $pdo->prepare('DELETE FROM reviews WHERE review_id = ?')
        ->execute([intval($_POST['review_id'])]);
    header('Location: reviews.php');
    exit();
}

$stmt = $pdo->query("
    SELECT r.*,
           c.first_name AS client_first, c.last_name AS client_last,
           p.first_name AS provider_first, p.last_name AS provider_last
    FROM reviews r
    JOIN users c ON r.client_id   = c.user_id
    JOIN users p ON r.provider_id = p.user_id
    ORDER BY r.created_at DESC
");
$reviews = $stmt->fetchAll();
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
                    <a class="nav-link" href="jobs.php">
                        <i class="bi bi-briefcase me-2"></i>Jobs
                    </a>
                    <a class="nav-link active" href="reviews.php">
                        <i class="bi bi-star me-2"></i>Reviews
                    </a>
                </nav>
            </div>
        </div>

        <div class="col-lg-10">
            <div class="mb-4">
                <h3 class="fw-bold mb-1">Reviews</h3>
                <p class="text-muted mb-0"><?= count($reviews) ?> reviews total</p>
            </div>

            <div class="card p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Provider</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reviews)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No reviews yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reviews as $r): ?>
                                    <tr>
                                        <td class="text-muted small"><?= $r['review_id'] ?></td>
                                        <td class="small fw-semibold">
                                            <?= htmlspecialchars($r['client_first'] . ' ' . $r['client_last']) ?>
                                        </td>
                                        <td class="small fw-semibold">
                                            <?= htmlspecialchars($r['provider_first'] . ' ' . $r['provider_last']) ?>
                                        </td>
                                        <td>
                                            <div class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill' : '' ?>
                                                   " style="font-size:0.8rem;"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td class="small">
                                            <?= htmlspecialchars(substr($r['review_text'], 0, 80)) ?>
                                            <?= strlen($r['review_text']) > 80 ? '...' : '' ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y', strtotime($r['created_at'])) ?>
                                        </td>
                                        <td>
                                            <form method="POST"
                                                onsubmit="return confirm('Delete this review?')">
                                                <input type="hidden" name="review_id"
                                                    value="<?= $r['review_id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
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