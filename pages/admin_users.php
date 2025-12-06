<?php
require_once "auth.php";
check_admin();

// Hapus User
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    
    // Proteksi: Jangan hapus diri sendiri!
    if ($uid == $_SESSION['user']['id']) {
        echo "<script>alert('Anda tidak bisa menghapus akun sendiri!'); window.location='index.php?page=admin_users';</script>";
        exit;
    }

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
    header("Location: index.php?page=admin_users&msg=deleted");
    exit;
}

// Ambil Daftar User (Urutkan dari yang terbaru daftar)
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="container my-5">
    <div class="mb-3">
        <a href="index.php?page=admin" class="text-decoration-none">← Kembali ke Dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Kelola Peserta (User)</h1>
        <a href="index.php?page=admin_user_form" class="btn btn-primary">
            + Tambah User Manual
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success">User berhasil dihapus.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">Nama Lengkap</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Tgl Daftar</th>
                            <th class="text-end px-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="px-4">
                                <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['role'] == 'admin'): ?>
                                    <span class="badge bg-danger">ADMIN</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Peserta</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end px-4">
                                <a href="index.php?page=admin_user_form&id=<?= $u['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">Edit / Reset</a>
                                
                                <?php if($u['id'] != $_SESSION['user']['id']): ?>
                                    <a href="index.php?page=admin_users&delete_user=<?= $u['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Yakin hapus user ini? Data nilai dan progres belajarnya akan hilang permanen!');">Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>