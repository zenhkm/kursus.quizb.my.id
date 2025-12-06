<?php
require_once "auth.php";
check_admin(); // Wajib Admin

// Proses Hapus Kursus (Simple Logic)
if (isset($_GET['delete_course'])) {
    $idDel = (int)$_GET['delete_course'];
    $stmtDel = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmtDel->execute([$idDel]);
    header("Location: index.php?page=admin&msg=deleted");
    exit;
}

// Ambil Semua Kursus
$stmt = $pdo->query("SELECT * FROM courses ORDER BY id DESC");
$courses = $stmt->fetchAll();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Dashboard Admin</h1>
        <a href="index.php?page=admin_course_form" class="btn btn-primary">
            + Tambah Kursus Baru
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success">Data kursus berhasil dihapus.</div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> Data kursus telah disimpan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>



    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">Judul Kursus</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Jml Materi</th>
                            <th class="text-end px-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td class="px-4">
                                    <div class="fw-bold"><?= htmlspecialchars($c['title']) ?></div>
                                    <small class="text-muted">Slug: <?= htmlspecialchars($c['slug']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($c['level']) ?></td>
                                <td>
                                    <?php if ($c['status'] == 'Tersedia'): ?>
                                        <span class="badge bg-success">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Segera Hadir</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$c['lessons'] ?></td>
                                <td class="text-end px-4">
                                    <a href="index.php?page=admin_modules&course_id=<?= $c['id'] ?>"
                                        class="btn btn-sm btn-warning me-1 text-dark fw-bold" title="Kelola Bab">Bab</a>

                                    <a href="index.php?page=admin_lessons&course_id=<?= $c['id'] ?>"
                                        class="btn btn-sm btn-primary me-1">Materi</a>

                                    <a href="index.php?page=admin_course_form&id=<?= $c['id'] ?>"
                                        class="btn btn-sm btn-outline-secondary" title="Edit Info">Info</a>

                                    <a href="index.php?page=admin&delete_course=<?= $c['id'] ?>"
                                        class="btn btn-sm btn-outline-danger ms-1"
                                        onclick="return confirm('Yakin hapus kursus ini?');">×</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">Belum ada data kursus.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>