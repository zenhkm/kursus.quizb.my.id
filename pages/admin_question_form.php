<?php
require_once "auth.php";
check_admin();

$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$id       = isset($_GET['id']) ? (int)$_GET['id'] : 0; // ID Soal

$question = null;
$options  = []; // Array untuk menampung opsi A, B, C, D

// Jika Mode Edit, Ambil Data Lama
if ($id > 0) {
    $stmtQ = $pdo->prepare("SELECT * FROM lesson_questions WHERE id = ?");
    $stmtQ->execute([$id]);
    $question = $stmtQ->fetch();

    $stmtO = $pdo->prepare("SELECT * FROM lesson_options WHERE question_id = ? ORDER BY option_label ASC");
    $stmtO->execute([$id]);
    $optionsDB = $stmtO->fetchAll();
    
    // Format ulang agar mudah diakses array-nya [A => data, B => data...]
    foreach($optionsDB as $o) {
        $options[$o['option_label']] = $o;
    }
}

// PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qText = trim($_POST['question_text']);
    $expl  = trim($_POST['explanation']);
    
    // Ambil input opsi dari form
    $optsInput = $_POST['options']; // Array [A => teks, B => teks...]
    $correct   = $_POST['is_correct']; // Label jawaban benar (misal 'A')

    if (empty($qText)) {
        $error = "Pertanyaan wajib diisi.";
    } else {
        try {
            $pdo->beginTransaction();

            if ($id > 0) {
                // UPDATE SOAL
                $pdo->prepare("UPDATE lesson_questions SET question_text=?, explanation=? WHERE id=?")
                    ->execute([$qText, $expl, $id]);
                $questionId = $id;
            } else {
                // INSERT SOAL BARU
                $pdo->prepare("INSERT INTO lesson_questions (lesson_id, question_text, explanation) VALUES (?, ?, ?)")
                    ->execute([$lessonId, $qText, $expl]);
                $questionId = $pdo->lastInsertId();
            }

            // SIMPAN OPSI JAWABAN (Loop A, B, C, D)
            // Cara paling aman: Hapus opsi lama, insert baru (agar tidak ribet update satu2)
            $pdo->prepare("DELETE FROM lesson_options WHERE question_id = ?")->execute([$questionId]);

            $stmtOpt = $pdo->prepare("INSERT INTO lesson_options (question_id, option_label, option_text, is_correct) VALUES (?, ?, ?, ?)");

            foreach (['A', 'B', 'C', 'D'] as $label) {
                $text = trim($optsInput[$label]);
                if ($text !== '') { // Hanya simpan jika teks tidak kosong
                    $isRight = ($correct === $label) ? 1 : 0;
                    $stmtOpt->execute([$questionId, $label, $text, $isRight]);
                }
            }

            $pdo->commit();
            header("Location: index.php?page=admin_questions&lesson_id=$lessonId&course_id=$courseId&msg=saved");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Gagal menyimpan: " . $e->getMessage();
        }
    }
}
?>

<div class="container my-5" style="max-width: 800px;">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= $id > 0 ? 'Edit Soal' : 'Tambah Soal Baru' ?></h5>
        </div>
        <div class="card-body">
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">Pertanyaan</label>
                    <textarea name="question_text" class="form-control" rows="3" required><?= htmlspecialchars($question['question_text'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Pembahasan (Muncul setelah menjawab)</label>
                    <textarea name="explanation" class="form-control" rows="2" placeholder="Jelaskan kenapa jawabannya itu..."><?= htmlspecialchars($question['explanation'] ?? '') ?></textarea>
                </div>

                <hr>
                <h6 class="mb-3">Pilihan Jawaban</h6>

                <?php foreach (['A', 'B', 'C', 'D'] as $label): ?>
                    <?php 
                        $val = isset($options[$label]) ? $options[$label]['option_text'] : ''; 
                        $isRight = isset($options[$label]) && $options[$label]['is_correct'] == 1;
                    ?>
                    <div class="input-group mb-2">
                        <span class="input-group-text fw-bold" style="width: 40px;"><?= $label ?></span>
                        <input type="text" name="options[<?= $label ?>]" class="form-control" value="<?= htmlspecialchars($val) ?>" required>
                        <div class="input-group-text bg-white">
                            <input class="form-check-input mt-0" type="radio" name="is_correct" value="<?= $label ?>" 
                                   <?= $isRight ? 'checked' : '' ?> required>
                            <span class="ms-1 small">Benar</span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php?page=admin_questions&lesson_id=<?= $lessonId ?>&course_id=<?= $courseId ?>" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>