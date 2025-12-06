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
// PROSES UPDATE PROFIL (NAMA & FOTO)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_profile'])) {
    $newName = trim($_POST['name']);
    $avatar  = $user['avatar']; // Default pakai foto lama

    // Logic Upload Foto
    if (!empty($_FILES['avatar']['name'])) {
        $fileTmp  = $_FILES['avatar']['tmp_name'];
        $fileName = $_FILES['avatar']['name'];
        $fileSize = $_FILES['avatar']['size'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($fileExt, $allowed)) {
            $error = "Format foto harus JPG, PNG, atau WEBP.";
        } elseif ($fileSize > 2000000) { // 2MB
            $error = "Ukuran foto maksimal 2MB.";
        } else {
            // Nama file unik: user_ID_waktu.ext
            $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExt;
            $uploadPath  = 'uploads/' . $newFileName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Hapus foto lama jika ada (biar hemat space)
                if ($user['avatar'] && file_exists('uploads/' . $user['avatar'])) {
                    unlink('uploads/' . $user['avatar']);
                }
                $avatar = $newFileName;
            } else {
                $error = "Gagal mengupload foto.";
            }
        }
    }
    
    if (empty($error)) {
        if (empty($newName)) {
            $error = "Nama tidak boleh kosong.";
        } else {
            $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, avatar = ? WHERE id = ?");
            $stmtUpd->execute([$newName, $avatar, $userId]);
            
            // Update Session agar header langsung berubah
            $_SESSION['user']['name']   = $newName;
            $_SESSION['user']['avatar'] = $avatar;
            
            header("Location: index.php?page=profile&msg=updated");
            exit;
        }
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
        <div class="alert alert-success">Password berhasil diubah.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Data Diri</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        
                        <div class="text-center mb-3">
                            <?php if (!empty($user['avatar']) && file_exists('uploads/' . $user['avatar'])): ?>
                                <img src="uploads/<?= htmlspecialchars($user['avatar']) ?>" 
                                     alt="Foto Profil" 
                                     class="rounded-circle shadow-sm"
                                     style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center shadow-sm"
                                     style="width: 100px; height: 100px; font-size: 2.5rem;">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Ganti Foto</label>
                            <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <button type="submit" name="btn_update_profile" class="btn btn-primary w-100">Simpan Profil</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4 bg-primary text-white border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 display-4">🏆</div>
                    <div>
                        <div class="fs-5 fw-bold">Pencapaian</div>
                        <div>Lulus <strong><?= (int)$stat['total_lulus'] ?></strong> materi.</div>
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