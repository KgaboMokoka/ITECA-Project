<?php
$pageTitle = 'Manage Users | Handyman Hub';
require_once '../../config/db.php';
require_once '../../includes/session.php';
requireRole('admin');

$error   = '';
$success = '';

// ── CREATE ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $role       = $_POST['role'];
    $phone      = trim($_POST['phone']);
    $location   = trim($_POST['location']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (!in_array($role, ['client', 'provider', 'admin'])) {
        $error = 'Invalid role.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'A user with that email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt   = $pdo->prepare('
                INSERT INTO users (first_name, last_name, email, password, phone, location, role)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$first_name, $last_name, $email, $hashed, $phone, $location, $role]);
            $user_id = $pdo->lastInsertId();

            if ($role === 'provider') {
                $pdo->prepare('INSERT INTO service_providers (user_id) VALUES (?)')->execute([$user_id]);
            }
            $success = 'User created successfully.';
        }
    }
}

// ── UPDATE ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    $user_id    = intval($_POST['user_id']);
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $role       = $_POST['role'];
    $phone      = trim($_POST['phone']);
    $location   = trim($_POST['location']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        // Check email not taken by another user
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'That email is already in use by another account.';
        } else {
            $pdo->prepare('
                UPDATE users
                SET first_name = ?, last_name = ?, email = ?, phone = ?, location = ?, role = ?
                WHERE user_id = ?
            ')->execute([$first_name, $last_name, $email, $phone, $location, $role, $user_id]);

            // If role changed to provider and no provider record exists, create one
            if ($role === 'provider') {
                $stmt = $pdo->prepare('SELECT provider_id FROM service_providers WHERE user_id = ?');
                $stmt->execute([$user_id]);
                if (!$stmt->fetch()) {
                    $pdo->prepare('INSERT INTO service_providers (user_id) VALUES (?)')->execute([$user_id]);
                }
            }
            $success = 'User updated successfully.';
        }
    }
}

// ── DELETE ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete') {
    $user_id = intval($_POST['user_id']);
    // Prevent admin from deleting themselves
    if ($user_id === intval($_SESSION['user_id'])) {
        $error = 'You cannot delete your own account.';
    } else {
        $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$user_id]);
        $success = 'User deleted successfully.';
    }
}

// ── FETCH USERS ─────────────────────────────────────────────────────────────
$search      = trim($_GET['search'] ?? '');
$filter_role = trim($_GET['role']   ?? '');
$where       = [];
$params      = [];

if ($search) {
    $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_role) {
    $where[]  = 'u.role = ?';
    $params[] = $filter_role;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT u.*,
           sp.verification_status,
           sp.provider_id
    FROM users u
    LEFT JOIN service_providers sp ON sp.user_id = u.user_id
    $whereSQL
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Fetch single user for edit modal
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([intval($_GET['edit'])]);
    $edit_user = $stmt->fetch();
}
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
                    <a class="nav-link active" href="users.php">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                    <a class="nav-link" href="verifications.php">
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
                    <h3 class="fw-bold mb-1">Manage Users</h3>
                    <p class="text-muted mb-0"><?= count($users) ?> users found</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus me-2"></i>Add User
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card p-3 mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by name or email..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="client" <?= $filter_role === 'client'   ? 'selected' : '' ?>>Client</option>
                            <option value="provider" <?= $filter_role === 'provider' ? 'selected' : '' ?>>Provider</option>
                            <option value="admin" <?= $filter_role === 'admin'    ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary">
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
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Location</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="text-muted small"><?= $u['user_id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-warning d-flex align-items-center
                                                    justify-content-center me-2 fw-bold text-dark"
                                                style="width:32px;height:32px;font-size:0.85rem;">
                                                <?= strtoupper(substr($u['first_name'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        </div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="small"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                                    <td class="small"><?= htmlspecialchars($u['location'] ?? '—') ?></td>
                                    <td>
                                        <?php
                                        $role_colors = [
                                            'admin'    => 'danger',
                                            'provider' => 'warning text-dark',
                                            'client'   => 'info text-dark',
                                        ];
                                        $rc = $role_colors[$u['role']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $rc ?>">
                                            <?= ucfirst($u['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] === 'provider'): ?>
                                            <?php
                                            $vs_colors = [
                                                'pending'  => 'warning text-dark',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                            ];
                                            $vs = $u['verification_status'] ?? 'pending';
                                            $vc = $vs_colors[$vs] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $vc ?>">
                                                <?= ucfirst($vs) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="users.php?edit=<?= $u['user_id'] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                                <form method="POST"
                                                    onsubmit="return confirm('Delete this user?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger"
                                                        title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus me-2"></i>Create New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select name="role" class="form-select" required>
                                <option value="">-- Select Role --</option>
                                <option value="client">Client</option>
                                <option value="provider">Provider</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" name="password"
                                class="form-control" required minlength="8">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($edit_user): ?>
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?= $edit_user['user_id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-pencil me-2"></i>Edit User
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">First Name</label>
                                <input type="text" name="first_name" class="form-control"
                                    value="<?= htmlspecialchars($edit_user['first_name']) ?>"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Last Name</label>
                                <input type="text" name="last_name" class="form-control"
                                    value="<?= htmlspecialchars($edit_user['last_name']) ?>"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($edit_user['email']) ?>"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="client"
                                        <?= $edit_user['role'] === 'client'   ? 'selected' : '' ?>>
                                        Client
                                    </option>
                                    <option value="provider"
                                        <?= $edit_user['role'] === 'provider' ? 'selected' : '' ?>>
                                        Provider
                                    </option>
                                    <option value="admin"
                                        <?= $edit_user['role'] === 'admin'    ? 'selected' : '' ?>>
                                        Admin
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                    value="<?= htmlspecialchars($edit_user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Location</label>
                                <input type="text" name="location" class="form-control"
                                    value="<?= htmlspecialchars($edit_user['location'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        });
    </script>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>