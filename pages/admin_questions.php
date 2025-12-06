<?php
require_once "auth.php";
check_admin();

$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Ambil Info Materi
$stmtL = $pdo->prepare("SELECT title FROM lessons WHERE id = ?");
$stmtL->execute([$lessonId]);
$lesson = $stmtL->fetch();

if (!$lesson) {
    die("Materi tidak ditemukan.");
}

// Hapus Soal
if (isset($_GET['delete_q'])) {
    $qid = (int)$$_GET['delete_q'];
    // Hapus soal (opsi jawaban ikut terhapus karena CASCADE di database)
    $pdo->prepare("DELETE FROM lesson_questions WHERE id = ?")->execute([$qid]);
    header("Location: index.php?page=admin_questions&lesson_id=$lessonId&course_id=$courseId&msg=deleted");
    exit;
}

// Ambil Daftar Soal
$stmtQ = $pdo->prepare("SELECT * FROM lesson_questions WHERE lesson_id = ? ORDER BY id ASC");
$stmtQ->execute([$lessonId]);
$questions = $stmtQ->fetchAll();
?>

<div class="container my-5">
    <div class="mb-3">
        <a href="index.php?page=admin_lessons&course_id=<?= $courseId ?>" class="text-decoration-none">← Kembali ke Daftar Materi</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4">Kelola Soal Kuis</h1>
            <p class="text-muted mb-0">Materi: <strong><?= htmlspecialchars($lesson['title']) ?></strong></p>
        </div>
        <a href="index.php?page=admin_question_form&lesson_id=<?= $lessonId ?>&course_id=<?= $courseId ?>" class="btn btn-primary">
            + Tambah Soal Baru
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-success">Soal berhasil dihapus.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">No</th>
                            <th>Pertanyaan</th>
                            <th>Jumlah Opsi</th>
                            <th class="text-end px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $idx => $q): 
                            // Hitung jumlah opsi
                            $stmtOpt = $pdo->prepare("SELECT COUNT(*) FROM lesson_options WHERE question_id = ?");
                            $stmtOpt->execute([$q['id']]);
                            $optCount = $stmtOpt->fetchColumn();
                        ?>
                        <tr>
                            <td class="px-3"><?= $idx + 1 ?></td>
                            <td><?= nl2br(htmlspecialchars(substr($q['question_text'], 0, 100))) ?>...</td>
                            <td><span class="badge bg-secondary"><?= $optCount ?> Opsi</span></td>
                            <td class="text-end px-3">
                                <a href="index.php?page=admin_question_form&lesson_id=<?= $lessonId ?>&course_id=<?= $courseId ?>&id=<?= $q['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="index.php?page=admin_questions&lesson_id=<?= $lessonId ?>&course_id=<?= $courseId ?>&delete_q=<?= $q['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Hapus soal ini beserta pilihan jawabannya?');">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($questions)): ?>
                            <tr><td colspan="4" class="text-center py-4">Belum ada soal untuk materi ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>