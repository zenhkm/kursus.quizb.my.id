<?php
require_once "auth.php";
check_admin();

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$error = '';

// Ambil Data Lama (Mode Edit)
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) die("User tidak ditemukan.");
}

// PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role  = $_POST['role'];
    $pass  = $_POST['password']; // Bisa kosong jika tidak ingin ganti

    if (empty($name) || empty($email)) {
        $error = "Nama dan Email wajib diisi.";
    } else {
        try {
            if ($id > 0) {
                // UPDATE USER
                // Cek apakah password diisi?
                if (!empty($pass)) {
                    // Update dengan password baru
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmtUpd = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE id=?");
                    $stmtUpd->execute([$name, $email, $role, $hash, $id]);
                } else {
                    // Update TANPA ganti password
                    $stmtUpd = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
                    $stmtUpd->execute([$name, $email, $role, $id]);
                }
            } else {
                // INSERT USER BARU (Wajib ada password)
                if (empty($pass)) {
                    $error = "Untuk user baru, password wajib diisi.";
                } else {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmtIns = $pdo->prepare("INSERT INTO users (name, email, role, password_hash) VALUES (?, ?, ?, ?)");
                    $stmtIns->execute([$name, $email, $role, $hash]);
                }
            }

            if (empty($error)) {
                header("Location: index.php?page=admin_users&msg=saved");
                exit;
            }

        } catch (PDOException $e) {
            $error = "Gagal menyimpan (Mungkin email sudah terpakai): " . $e->getMessage();
        }
    }
}
?>

<div class="container my-5" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= $id > 0 ? 'Edit User / Reset Password' : 'Tambah User Baru' ?></h5>
        </div>
        <div class="card-body">
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email Login</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Role (Hak Akses)</label>
                    <select name="role" class="form-select">
                        <option value="user" <?= (isset($user['role']) && $user['role'] == 'user') ? 'selected' : '' ?>>Peserta (User)</option>
                        <option value="admin" <?= (isset($user['role']) && $user['role'] == 'admin') ? 'selected' : '' ?>>Administrator</option>
                    </select>
                    <div class="form-text text-danger">Hati-hati memberikan akses Administrator.</div>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label fw-bold">Password Baru</label>
                    <input type="text" name="password" class="form-control bg-light" placeholder="Kosongkan jika tidak ingin mengganti password">
                    <div class="form-text">
                        <?php if($id > 0): ?>
                            Isi kolom ini HANYA jika Anda ingin me-reset password user ini.<br>
                            User bisa login dengan password baru yang Anda tulis di sini.
                        <?php else: ?>
                            Wajib diisi untuk user baru.
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php?page=admin_users" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>