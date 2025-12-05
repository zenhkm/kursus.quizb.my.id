<?php
// auth_reset.php
require __DIR__ . '/config.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = '';
$success = '';

// 1. Validasi Token Awal (Saat halaman dibuka)
if (empty($token) || empty($email)) {
    die("Link tidak valid.");
}

// Cek token di database
$stmtCheck = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? LIMIT 1");
$stmtCheck->execute([$email, $token]);
$resetData = $stmtCheck->fetch();

if (!$resetData) {
    die("Link reset password tidak valid atau sudah kadaluarsa.");
}

// 2. Proses Ubah Password (Saat Form Disubmit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if (strlen($pass1) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($pass1 !== $pass2) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        // Hash password baru
        $newHash = password_hash($pass1, PASSWORD_DEFAULT);

        // Update User
        $stmtUpd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmtUpd->execute([$newHash, $email]);

        // Hapus token agar tidak bisa dipakai lagi
        $stmtDel = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmtDel->execute([$email]);

        $success = "Password berhasil diubah! Silakan login dengan password baru.";
    }
}

$pageTitle = 'Reset Password Baru';
include __DIR__ . '/layout/header.php';
?>

<div class="container my-5" style="max-width: 480px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            
            <?php if ($success): ?>
                <div class="text-center">
                    <div class="fs-1 mb-2">✅</div>
                    <h3 class="mb-3">Berhasil!</h3>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <a href="auth.php?action=login" class="btn btn-primary w-100">Login Sekarang</a>
                </div>
            <?php else: ?>

                <h3 class="card-title mb-4 text-center">Buat Password Baru</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="pass1" class="form-control" required placeholder="Minimal 6 karakter">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Ulangi Password Baru</label>
                        <input type="password" name="pass2" class="form-control" required placeholder="Ketik ulang password">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Simpan Password</button>
                </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>