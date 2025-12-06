<?php
require_once "auth.php";
check_admin();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Ambil Info Kursus
$stmtC = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
$stmtC->execute([$courseId]);
$course = $stmtC->fetch();

if (!$course) {
    die("Kursus tidak ditemukan.");
}

// PROSES TAMBAH MODUL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']);
    $order   = (int)$_POST['module_order'];
    $summary = trim($_POST['summary']);

    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO course_modules (course_id, module_order, title, summary) VALUES (?, ?, ?, ?)");
        $stmt->execute([$courseId, $order, $title, $summary]);
        header("Location: index.php?page=admin_modules&course_id=$courseId&msg=saved");
        exit;
    }
}

// PROSES HAPUS MODUL
if (isset($_GET['delete_mod'])) {
    $modId = (int)$_GET['delete_mod'];
    // Hapus modul (Materi di dalamnya akan ikut terhapus karena CASCADE di database)
    $pdo->prepare("DELETE FROM course_modules WHERE id = ?")->execute([$modId]);
    header("Location: index.php?page=admin_modules&course_id=$courseId&msg=deleted");
    exit;
}

// Ambil Daftar Modul
$stmtM = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY module_order ASC");
$stmtM->execute([$courseId]);
$modules = $stmtM->fetchAll();
?>

<div class="container my-5">
    <div class="mb-3">
        <a href="index.php?page=admin" class="text-decoration-none">← Kembali ke Dashboard</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daftar Bab (Modul)</h5>
                    <span class="badge bg-primary"><?= htmlspecialchars($course['title']) ?></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Urutan</th>
                                <th>Nama Bab</th>
                                <th>Deskripsi</th>
                                <th class="text-end px-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $m): ?>
                            <tr>
                                <td class="px-3 text-center fw-bold"><?= $m['module_order'] ?></td>
                                <td><?= htmlspecialchars($m['title']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($m['summary']) ?></td>
                                <td class="text-end px-3">
                                    <a href="index.php?page=admin_modules&course_id=<?= $courseId ?>&delete_mod=<?= $m['id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Hapus Bab ini? PERINGATAN: Semua materi di dalam bab ini juga akan terhapus!');">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($modules)): ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada Bab/Modul.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="alert alert-info small">
                <strong>Tips:</strong> Setelah membuat Bab di sini, silakan kembali ke Dashboard lalu klik tombol <strong>Materi</strong> untuk mengisi konten pelajaran.
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <h5 class="card-title mb-3">Tambah Bab Baru</h5>
                    <form method="post">
                        <div class="mb-2">
                            <label class="form-label small">Urutan Bab</label>
                            <input type="number" name="module_order" class="form-control" value="<?= count($modules) + 1 ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Judul Bab</label>
                            <input type="text" name="title" class="form-control" placeholder="Contoh: Bab 1 - Muqaddimah" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Ringkasan (Opsional)</label>
                            <textarea name="summary" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Simpan Bab</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>