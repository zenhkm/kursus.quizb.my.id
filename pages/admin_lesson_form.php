<?php
require_once "auth.php";
check_admin();

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$id       = isset($_GET['id']) ? (int)$_GET['id'] : 0; // ID Materi (kalau edit)
$lesson   = null;
$error    = '';

// Ambil Data Modul (Bab) untuk Dropdown
$stmtMod = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY module_order ASC");
$stmtMod->execute([$courseId]);
$modules = $stmtMod->fetchAll();

if (empty($modules)) {
    die("Error: Kursus ini belum memiliki Modul/Bab. Silakan buat Modul di database terlebih dahulu (Table: course_modules).");
}

// Jika Mode Edit
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$id]);
    $lesson = $stmt->fetch();
}

// PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId    = $_POST['module_id'];
    $lessonOrder = $_POST['lesson_order'];
    $title       = trim($_POST['title']);
    $contentType = $_POST['content_type'];
    $contentText = $_POST['content_text'];
    $videoUrl    = $_POST['video_url'];

    if (empty($title)) {
        $error = "Judul materi wajib diisi.";
    } else {
        try {
            if ($id > 0) {
                // UPDATE
                $sql = "UPDATE lessons SET module_id=?, lesson_order=?, title=?, content_type=?, content_text=?, video_url=? WHERE id=?";
                $params = [$moduleId, $lessonOrder, $title, $contentType, $contentText, $videoUrl, $id];
            } else {
                // INSERT
                $sql = "INSERT INTO lessons (course_id, module_id, lesson_order, title, content_type, content_text, video_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [$courseId, $moduleId, $lessonOrder, $title, $contentType, $contentText, $videoUrl];
            }

            $pdo->prepare($sql)->execute($params);
            
            // Hitung ulang jumlah lessons di tabel courses
            $pdo->prepare("UPDATE courses SET lessons = (SELECT COUNT(*) FROM lessons WHERE course_id = ?) WHERE id = ?")->execute([$courseId, $courseId]);

            header("Location: index.php?page=admin_lessons&course_id=$courseId&msg=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal menyimpan: " . $e->getMessage();
        }
    }
}
?>

<div class="container my-5" style="max-width: 800px;">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= $id > 0 ? 'Edit Materi' : 'Tambah Materi Baru' ?></h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Judul Materi</label>
                        <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($lesson['title'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Urutan (Angka)</label>
                        <input type="number" name="lesson_order" class="form-control" required value="<?= $lesson['lesson_order'] ?? 1 ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Masuk ke Bab (Modul)</label>
                    <select name="module_id" class="form-select" required>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= (isset($lesson['module_id']) && $lesson['module_id'] == $m['id']) ? 'selected' : '' ?>>
                                Bab <?= $m['module_order'] ?>: <?= htmlspecialchars($m['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipe Konten</label>
                    <select name="content_type" class="form-select" id="typeSelect">
                        <option value="text" <?= (isset($lesson['content_type']) && $lesson['content_type'] == 'text') ? 'selected' : '' ?>>Teks Bacaan</option>
                        <option value="video" <?= (isset($lesson['content_type']) && $lesson['content_type'] == 'video') ? 'selected' : '' ?>>Video Saja</option>
                        <option value="mixed" <?= (isset($lesson['content_type']) && $lesson['content_type'] == 'mixed') ? 'selected' : '' ?>>Campuran (Teks + Video)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Link Video (Opsional)</label>
                    <input type="text" name="video_url" class="form-control" placeholder="Link video (MP4/Youtube)" value="<?= htmlspecialchars($lesson['video_url'] ?? '') ?>">
                    <div class="form-text">Isi jika tipe materi menggunakan Video.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Isi Materi (Teks)</label>
                    <textarea name="content_text" class="form-control" rows="10" placeholder="Tulis materi di sini..."><?= htmlspecialchars($lesson['content_text'] ?? '') ?></textarea>
                    <div class="form-text">Tips: Pisahkan antar paragraf dengan Enter (Baris baru) agar sistem bisa memecahnya menjadi poin-poin bacaan.</div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php?page=admin_lessons&course_id=<?= $courseId ?>" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Materi</button>
                </div>
            </form>
        </div>
    </div>
</div>