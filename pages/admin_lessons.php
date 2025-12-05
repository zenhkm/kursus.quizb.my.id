<?php
require_once "auth.php";
check_admin();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Ambil Info Kursus
$stmtC = $pdo->prepare("SELECT title, slug FROM courses WHERE id = ?");
$stmtC->execute([$courseId]);
$course = $stmtC->fetch();

if (!$course) {
    die("Kursus tidak ditemukan.");
}

// Hapus Materi
if (isset($_GET['delete_lesson'])) {
    $lid = (int)$$_GET['delete_lesson'];
    $pdo->prepare("DELETE FROM lessons WHERE id = ?")->execute([$lid]);
    header("Location: index.php?page=admin_lessons&course_id=$courseId&msg=deleted");
    exit;
}

// Ambil Daftar Materi + Nama Modulnya
$sql = "
    SELECT l.*, m.title as module_title 
    FROM lessons l
    JOIN course_modules m ON l.module_id = m.id
    WHERE l.course_id = ?
    ORDER BY m.module_order ASC, l.lesson_order ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();
?>

<div class="container my-5">
    <div class="mb-3">
        <a href="index.php?page=admin" class="text-decoration-none">← Kembali ke Dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4">Kelola Materi</h1>
            <p class="text-muted mb-0">Kursus: <strong><?= htmlspecialchars($course['title']) ?></strong></p>
        </div>
        <a href="index.php?page=admin_lesson_form&course_id=<?= $courseId ?>" class="btn btn-primary">
            + Tambah Materi Baru
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success">Materi berhasil dihapus.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">No</th>
                            <th>Judul Materi</th>
                            <th>Bab (Modul)</th>
                            <th>Tipe</th>
                            <th class="text-end px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lessons as $l): ?>
                        <tr>
                            <td class="px-3"><?= $l['lesson_order'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($l['title']) ?></strong>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['module_title']) ?></span></td>
                            <td>
                                <?php if($l['content_type'] == 'video'): ?>
                                    <span class="badge bg-danger">Video</span>
                                <?php elseif($l['content_type'] == 'text'): ?>
                                    <span class="badge bg-primary">Teks</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Mixed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end px-3">
                                <a href="#" class="btn btn-sm btn-outline-warning me-1" title="Kelola Soal (Coming Soon)">
                                    ❓ Soal
                                </a>
                                <a href="index.php?page=admin_lesson_form&course_id=<?= $courseId ?>&id=<?= $l['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="index.php?page=admin_lessons&course_id=<?= $courseId ?>&delete_lesson=<?= $l['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus materi ini?');">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($lessons)): ?>
                            <tr><td colspan="5" class="text-center py-4">Belum ada materi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>