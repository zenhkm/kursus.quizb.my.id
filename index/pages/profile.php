<?php
require_once "auth.php";
check_login();

$userId = $_SESSION['user']['id'];
$msg    = isset($_GET['msg']) ? $_GET['msg'] : '';
$error  = '';

// 1. Ambil Data User Terbaru
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// 2. Hitung Statistik Belajar
$stmtStat = $pdo->prepare("
    SELECT COUNT(*) as total_lulus 
    FROM lesson_progress 
    WHERE user_id = ? AND has_passed = 1
");
$stmtStat->execute([$userId]);
$stat = $stmtStat->fetch();

// ==========================================
// PROSES UPDATE PROFIL (NAMA)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_profile'])) {
    $newName = trim($_POST['name']);
    
    if (empty($newName)) {
        $error = "Nama tidak boleh kosong.";
    } else {
        $stmtUpd = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmtUpd->execute([$newName, $userId]);
        
        // Update Session agar header langsung berubah
        $_SESSION['user']['name'] = $newName;
        
        header("Location: index.php?page=profile&msg=updated");
        exit;
    }
}

// ==========================================
// PROSES GANTI PASSWORD
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_change_password'])) {
    $oldPass = $_POST['old_password'];
    $newPass = $_POST['new_password'];
    $cnfPass = $_POST['confirm_password'];

    if (!password_verify($oldPass, $user['password_hash'])) {
        $error = "Password lama salah.";
    } elseif (strlen($newPass) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } elseif ($newPass !== $cnfPass) {
        $error = "Konfirmasi password baru tidak cocok.";
    } else {
        // Hash password baru
        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmtUpdPass = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmtUpdPass->execute([$newHash, $userId]);

        header("Location: index.php?page=profile&msg=pass_changed");
        exit;
    }
}
?>

<div class="container my-5" style="max-width: 900px;">
    <h1 class="h3 mb-4">Profil Saya</h1>

    <?php if ($msg == 'updated'): ?>
        <div class="alert alert-success">Profil berhasil diperbarui.</div>
    <?php elseif ($msg == 'pass_changed'): ?>
        <div class="alert alert-success">Password berhasil diubah. Silakan ingat password baru Anda.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Data Diri</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                            <div class="form-text">Email tidak dapat diubah.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bergabung Sejak</label>
                            <input type="text" class="form-control-plaintext" value="<?= date('d M Y', strtotime($user['created_at'])) ?>" readonly>
                        </div>
                        <button type="submit" name="btn_update_profile" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4 bg-primary text-white border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 display-4">
                        🏆
                    </div>
                    <div>
                        <div class="fs-5 fw-bold">Pencapaian Belajar</div>
                        <div>Anda telah lulus <strong><?= (int)$stat['total_lulus'] ?></strong> materi.</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold text-danger">Ganti Password</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-2">
                            <label class="form-label small">Password Lama</label>
                            <input type="password" name="old_password" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Password Baru</label>
                            <input type="password" name="new_password" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Ulangi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control form-control-sm" required>
                        </div>
                        <button type="submit" name="btn_change_password" class="btn btn-outline-danger btn-sm w-100">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>