<?php
require_once "auth.php";
check_login();
// pages/lesson_view.php

// --- Debug ringan (kalau mau lihat error, boleh aktifkan lalu nanti dimatikan) ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Pastikan $lessonId dan $currentCourseSlug sudah diset dari index.php
if (empty($lessonId) || empty($currentCourseSlug)) {
?>
    <div class="container my-5">
        <div class="alert alert-danger">
            Parameter materi tidak lengkap.
        </div>
    </div>
<?php
    return;
}

// Ambil data lesson + course + module
$sql = "
    SELECT 
        l.*,
        c.title AS course_title,
        c.slug  AS course_slug,
        m.title AS module_title,
        m.module_order
    FROM lessons l
    JOIN courses c ON l.course_id = c.id
    JOIN course_modules m ON l.module_id = m.id
    WHERE l.id = ? AND c.slug = ?
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute(array($lessonId, $currentCourseSlug));
$lesson = $stmt->fetch();

if (!$lesson) {
?>
    <div class="container my-5">
        <div class="alert alert-danger">
            Materi tidak ditemukan untuk kursus ini.
        </div>
    </div>
<?php
    return;
}

// ----------------------------------------------------
// Cek apakah user boleh mengakses materi ini
// Syarat: kalau ada materi sebelumnya, harus sudah lulus
// ----------------------------------------------------
$userId = $_SESSION['user']['id'] ?? 0;
$canAccessLesson = true;

// Cari lesson sebelumnya dalam kursus yang sama
// Cari lesson sebelumnya yang paling dekat posisinya
// Logika: Cari di modul yang sama dengan order lebih kecil, ATAU cari di modul sebelumnya
// PERBAIKAN: Cek materi sebelumnya berdasarkan Bab dan Materi
$sqlPrev = "
    SELECT l.id, l.title
    FROM lessons l
    JOIN course_modules m ON l.module_id = m.id
    WHERE l.course_id = ?
      AND (
          (m.module_order = ? AND l.lesson_order < ?) 
          OR 
          (m.module_order < ?)
      )
    ORDER BY m.module_order DESC, l.lesson_order DESC
    LIMIT 1
";
$stmtPrev = $pdo->prepare($sqlPrev);
$stmtPrev->execute(array(
    $lesson['course_id'],
    $lesson['module_order'],
    $lesson['lesson_order'],
    $lesson['module_order']
));
$prevLesson = $stmtPrev->fetch();

if ($prevLesson) {
    $sqlPrevProg = "
        SELECT has_passed
        FROM lesson_progress
        WHERE user_id = ? AND lesson_id = ?
        LIMIT 1
    ";
    $stmtPrevProg = $pdo->prepare($sqlPrevProg);
    $stmtPrevProg->execute(array($userId, $prevLesson['id']));
    $rowPrevProg = $stmtPrevProg->fetch();

    // Kalau belum pernah mengerjakan atau belum lulus → tidak boleh akses
    if (!$rowPrevProg || (int)$rowPrevProg['has_passed'] !== 1) {
        $canAccessLesson = false;
    }
}

// Kalau tidak boleh akses, tampilkan pesan dan hentikan
if (!$canAccessLesson) {
?>
    <div class="container my-5">
        <div class="alert alert-warning">
            Untuk mengakses materi ini, Anda harus <strong>lulus</strong> materi sebelumnya terlebih dahulu.
        </div>
    </div>
<?php
    return;
}

// Ambil soal & opsi untuk lesson ini
$sqlQ = "
    SELECT q.*
    FROM lesson_questions q
    WHERE q.lesson_id = ?
    ORDER BY RAND()    <-- GANTI JADI INI (ACAK)
";
$stmtQ = $pdo->prepare($sqlQ);
$stmtQ->execute(array($lesson['id']));
$questions = $stmtQ->fetchAll();

// Ambil semua options per question
$optionsByQuestion = array();
if (!empty($questions)) {
    $questionIds = array();
    foreach ($questions as $q) {
        $questionIds[] = (int)$q['id'];
    }

    if (count($questionIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $sqlOpt = "
            SELECT * FROM lesson_options
            WHERE question_id IN ($placeholders)
            ORDER BY question_id ASC, option_label ASC
        ";
        $stmtOpt = $pdo->prepare($sqlOpt);
        $stmtOpt->execute($questionIds);
        $options = $stmtOpt->fetchAll();

        foreach ($options as $opt) {
            $qid = (int)$opt['question_id'];
            if (!isset($optionsByQuestion[$qid])) {
                $optionsByQuestion[$qid] = array();
            }
            $optionsByQuestion[$qid][] = $opt;
        }
    }
}

// ------------------------------
// LOGIKA PENILAIAN SOAL
// ------------------------------
// Hapus baris lama (-), pakai baris baru (+) tapi hilangkan tanda plusnya
$userId = $_SESSION['user']['id'] ?? 0;
$quizSubmitted   = ($_SERVER['REQUEST_METHOD'] === 'POST');
$quizResult      = null;
$selectedAnswers = array();

if ($quizSubmitted && !empty($questions)) {
    $totalQuestions = count($questions);
    $correctCount   = 0;
    $details        = array();

    foreach ($questions as $q) {
        $qid       = (int)$q['id'];
        $fieldName = 'q_' . $qid;
        $selected  = isset($_POST[$fieldName]) ? $_POST[$fieldName] : null;
        $selectedAnswers[$qid] = $selected;

        // Cari opsi yg benar
        if (isset($optionsByQuestion[$qid])) {
            $opts = $optionsByQuestion[$qid];
        } else {
            $opts = array();
        }

        $correctOption = null;
        foreach ($opts as $opt) {
            if (!empty($opt['is_correct'])) {
                $correctOption = $opt;
                break;
            }
        }

        $isCorrect = false;
        if ($selected !== null && $correctOption) {
            $isCorrect = ($selected === $correctOption['option_label']);
        }

        if ($isCorrect) {
            $correctCount++;
        }

        $details[$qid] = array(
            'selected'       => $selected,
            'is_correct'     => $isCorrect,
            'correct_option' => $correctOption,
            'question'       => $q,
        );
    }

    $allCorrect = ($correctCount === $totalQuestions);

    $quizResult = array(
        'total'       => $totalQuestions,
        'correct'     => $correctCount,
        'all_correct' => $allCorrect,
        'details'     => $details,
    );

    // Simpan progres ke database
    // Simpan progres ke database
    try {
        $hasRead   = 1;
        $hasPassed = $allCorrect ? 1 : 0;
        $lastScore = $correctCount;

        $stmtProg = $pdo->prepare("
            INSERT INTO lesson_progress (user_id, lesson_id, has_read, has_passed, attempts, last_score)
            VALUES (:user_id, :lesson_id, :has_read, :has_passed, 1, :last_score)
            ON DUPLICATE KEY UPDATE
                has_read   = VALUES(has_read),
                has_passed = VALUES(has_passed),
                attempts   = attempts + 1,
                last_score = VALUES(last_score),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmtProg->execute(array(
            ':user_id'    => $userId,
            ':lesson_id'  => $lesson['id'],
            ':has_read'   => $hasRead,
            ':has_passed' => $hasPassed,
            ':last_score' => $lastScore,
        ));
    } catch (Exception $e) {
        // Biarkan kosong atau log error jika perlu
    } // <--- TUTUP CATCH DI SINI

    // ----------------------------------------------------
    // PINDAHKAN KODE INI KELUAR DARI CATCH (Supaya selalu dijalankan)
    // ----------------------------------------------------
    $hasPassedLesson = false;

    // Kalau barusan submit dan semua benar → sudah pasti lulus
    if (!empty($quizResult) && !empty($quizResult['all_correct']) && $quizResult['all_correct']) {
        $hasPassedLesson = true;
    } else {
        // Cek di tabel lesson_progress
        $stmtThisProg = $pdo->prepare("
            SELECT has_passed
            FROM lesson_progress
            WHERE user_id = ? AND lesson_id = ?
            LIMIT 1
        ");
        $stmtThisProg->execute(array($userId, $lesson['id']));
        $rowThisProg = $stmtThisProg->fetch();
        if ($rowThisProg && (int)$rowThisProg['has_passed'] === 1) {
            $hasPassedLesson = true;
        }
    }

    // Cari lesson berikutnya di kursus ini
    $nextLesson = null;
    // PERBAIKAN: Urutkan berdasarkan Module/Bab dulu, baru Lesson/Materi
    $sqlNext = "
            SELECT l.id, l.title
            FROM lessons l
            JOIN course_modules m ON l.module_id = m.id
            WHERE l.course_id = ?
              AND (
                (m.module_order = ? AND l.lesson_order > ?) -- Materi selanjutnya di Bab yang sama
                OR 
                (m.module_order > ?) -- Materi pertama di Bab selanjutnya
              )
            ORDER BY m.module_order ASC, l.lesson_order ASC
            LIMIT 1
        ";
    $stmtNext = $pdo->prepare($sqlNext);
    $stmtNext->execute(array(
        $lesson['course_id'],       // Parameter 1: ID Kursus
        $lesson['module_order'],    // Parameter 2: Bab saat ini
        $lesson['lesson_order'],    // Parameter 3: Materi saat ini
        $lesson['module_order']     // Parameter 4: Bab saat ini (untuk cari bab > ini)
    ));
    $nextLesson = $stmtNext->fetch();

    // Untuk saat ini, kalau gagal simpan progress, tidak perlu mematikan halaman
    // echo "DEBUG PROGRESS ERROR: " . htmlspecialchars($e->getMessage());

}

// ============================================================
// FIX: Pastikan $hasPassedLesson selalu terdefinisi
// (Agar tidak error saat halaman dibuka pertama kali)
// ============================================================
if (!isset($hasPassedLesson)) {
    $hasPassedLesson = false;

    // Cek ke database: Apakah user ini sudah pernah lulus materi ini?
    $stmtCheckPass = $pdo->prepare("
        SELECT has_passed 
        FROM lesson_progress 
        WHERE user_id = ? AND lesson_id = ? 
        LIMIT 1
    ");
    $stmtCheckPass->execute([$userId, $lesson['id']]);
    $rowPass = $stmtCheckPass->fetch();

    if ($rowPass && (int)$rowPass['has_passed'] === 1) {
        $hasPassedLesson = true;
    }
}
// ============================================================
?>

<section class="hero-section">
    <div class="container">
        <div class="mb-2 small">
            <a href="index.php" class="text-decoration-none">Beranda</a>
            &nbsp;›&nbsp;
            <a href="index.php?kursus=<?= htmlspecialchars($lesson['course_slug']) ?>"
                class="text-decoration-none">
                <?= htmlspecialchars($lesson['course_title']) ?>
            </a>
            &nbsp;›&nbsp;
            <span class="text-muted">
                <?= htmlspecialchars($lesson['module_title']) ?>
            </span>
        </div>

        <div class="row gy-3">
            <div class="col-12 col-lg-8">
                <div class="hero-badge mb-2">
                    <span>Bab <?= (int)$lesson['module_order'] ?></span>
                    <span>•</span>
                    <span>Materi</span>
                </div>
                <h1 class="h4 mb-2">
                    <?= htmlspecialchars($lesson['title']) ?>
                </h1>

                <!-- Konten materi -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <?php if ($lesson['content_type'] === 'video' || $lesson['content_type'] === 'mixed'): ?>
                            <?php if (!empty($lesson['video_url'])): ?>
                                <div class="mb-3">
                                    <video width="100%" controls>
                                        <source src="<?= htmlspecialchars($lesson['video_url']) ?>" type="video/mp4">
                                        Browser Anda tidak mendukung pemutar video.
                                    </video>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (
                            $lesson['content_type'] === 'text'
                            || $lesson['content_type'] === 'mixed'
                        ): ?>
                            <?php
                            // Pecah materi per baris sebagai poin
                            $rawLines = preg_split('/\r\n|\r|\n/', trim($lesson['content_text']));
                            $points = array();
                            if (is_array($rawLines)) {
                                foreach ($rawLines as $line) {
                                    $line = trim($line);
                                    if ($line === '') continue;
                                    $points[] = $line;
                                }
                            }
                            ?>
                            <?php if (!empty($points)): ?>
                                <ol id="lesson-points" class="small">
                                    <?php foreach ($points as $i => $p): ?>
                                        <?php
                                        // Hitung jumlah kata
                                        $plain = strip_tags($p);
                                        $wordCount = str_word_count($plain);
                                        if ($wordCount <= 0) $wordCount = 1;
                                        // 0.3 detik per kata, min 3 detik
                                        $requiredSeconds = max(3, ceil($wordCount * 0.3));
                                        ?>
                                        <li class="lesson-point"
                                            data-required-seconds="<?= $requiredSeconds ?>"
                                            data-index="<?= $i ?>"
                                            <?= $i > 0 ? 'style="display:none;"' : '' ?>>
                                            <?= nl2br(htmlspecialchars($p)) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>

                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <button type="button"
                                        id="btn-next-point"
                                        class="btn btn-primary btn-sm">
                                        Lanjut
                                    </button>
                                    <small class="text-muted" id="lesson-hint">
                                        Tekan tombol <strong>Lanjut</strong> setelah membaca poin ini sampai selesai.
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="small" style="white-space: pre-wrap;">
                                    <?= nl2br(htmlspecialchars($lesson['content_text'])) ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Panel samping -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="section-label mb-2">Status Belajar</div>

                        <?php if ($hasPassedLesson): ?>
                            <div class="alert alert-success d-flex align-items-center mb-0 p-2 small">
                                <span class="fs-4 me-2">🎉</span>
                                <div>
                                    <strong>Lulus!</strong><br>
                                    Anda sudah menyelesaikan materi ini.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning d-flex align-items-center mb-0 p-2 small">
                                <span class="fs-4 me-2">⏳</span>
                                <div>
                                    <strong>Belum Selesai</strong><br>
                                    Silakan baca materi & kerjakan soal dengan benar.
                                </div>
                            </div>
                        <?php endif; ?>

                        <hr class="my-3 opacity-25">

                        <div class="small text-muted">
                            <strong>Ketentuan Lulus:</strong>
                            <ul class="mb-0 ps-3 mt-1">
                                <li>Baca semua poin materi sampai selesai.</li>
                                <li>Jawab semua soal kuis dengan benar (100%).</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Soal setelah materi -->
        <div class="mt-4">
            <h2 class="h6 mb-2">Soal Materi Ini</h2>
            <p class="small text-muted">
                Jawablah soal berdasarkan materi yang baru saja Anda pelajari.
                Untuk dinyatakan lulus, semua jawaban harus benar.
            </p>

            <?php if (empty($questions)): ?>
                <div class="alert alert-warning small">
                    Belum ada soal untuk materi ini.
                </div>
            <?php else: ?>

                <?php if ($quizSubmitted && $quizResult): ?>
                    <?php if ($quizResult['all_correct']): ?>
                        <div class="alert alert-success small">
                            <p class="mb-2">
                                Alhamdulillah, semua jawaban Anda <strong>benar</strong>
                                (<?= $quizResult['correct'] ?>/<?= $quizResult['total'] ?>).
                                Materi ini dinyatakan <strong>LULUS</strong>.
                            </p>

                            <?php if (!empty($nextLesson)): ?>
                                <a href="index.php?kursus=<?= htmlspecialchars($lesson['course_slug']) ?>&lesson=<?= (int)$nextLesson['id'] ?>"
                                    class="btn btn-success btn-sm fw-bold mt-2">
                                    Lanjut ke materi berikutnya: <?= htmlspecialchars($nextLesson['title']) ?> →
                                </a>
                            <?php else: ?>
                                <p class="mb-0 mt-2 text-muted">
                                    <em>Anda telah menyelesaikan semua materi di kursus ini. Selamat!</em>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger small">
                            Jawaban Anda belum semuanya benar
                            (<?= $quizResult['correct'] ?>/<?= $quizResult['total'] ?>).
                            Silakan perhatikan pembahasan, lalu <strong>coba lagi</strong>.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="post" action="">
                    <?php foreach ($questions as $idx => $q): ?>
                        <?php
                        $qid = (int)$q['id'];

                        // Ambil detail jawaban kalau sudah disubmit
                        $detail = null;
                        if (!empty($quizResult) && isset($quizResult['details'][$qid])) {
                            $detail = $quizResult['details'][$qid];
                        }

                        if (isset($optionsByQuestion[$qid])) {
                            $opts = $optionsByQuestion[$qid];
                        } else {
                            $opts = array();
                        }
                        ?>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">
                                    Soal <?= $idx + 1 ?>:
                                    <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                                </div>

                                <?php
                                // --- LOGIKA PENGACAKAN JAWABAN ---
                                // Kita acak array $opts agar posisinya berubah
                                if (!$quizSubmitted && !empty($opts)) {
                                    shuffle($opts);
                                }
                                // Array bantu untuk label visual agar tetap A, B, C, D (bukan acak C, A, D, B)
                                $abcLabels = ['A', 'B', 'C', 'D', 'E'];
                                ?>

                                <?php foreach ($opts as $key => $opt): ?>
                                    <?php
                                    $visualLabel = isset($abcLabels[$key]) ? $abcLabels[$key] : '?';

                                    $fieldName = 'q_' . $qid;
                                    // ID input kita buat unik pakai label asli database biar tidak bentrok
                                    $idInput   = $fieldName . '_' . $opt['option_label'];

                                    // Cek jawaban user (tetap bandingkan dengan label asli dari DB)
                                    $wasSelected = isset($selectedAnswers[$qid]) &&
                                        $selectedAnswers[$qid] === $opt['option_label'];

                                    $checkedAttr = $wasSelected ? 'checked' : '';
                                    ?>
                                    <div class="form-check small mb-1">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="<?= htmlspecialchars($fieldName) ?>"
                                            id="<?= htmlspecialchars($idInput) ?>"
                                            value="<?= htmlspecialchars($opt['option_label']) ?>"
                                            <?= $checkedAttr ?>>
                                        <label class="form-check-label" for="<?= htmlspecialchars($idInput) ?>">
                                            <strong><?= $visualLabel ?>.</strong>
                                            <?= nl2br(htmlspecialchars($opt['option_text'])) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($quizSubmitted && $detail): ?>
                                    <div class="mt-2 small">
                                        <?php if (!empty($detail['is_correct'])): ?>
                                            <span class="text-success">✔ Jawaban Anda benar.</span>
                                        <?php else: ?>
                                            <span class="text-danger">✘ Jawaban Anda belum tepat.</span><br>
                                            <?php if (!empty($detail['correct_option'])): ?>
                                                <span class="text-muted">
                                                    Jawaban yang benar:
                                                    <strong>
                                                        <?= htmlspecialchars($detail['correct_option']['option_label']) ?>.
                                                        <?= nl2br(htmlspecialchars($detail['correct_option']['option_text'])) ?>
                                                    </strong>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($q['explanation'])): ?>
                                                <div class="text-muted mt-1">
                                                    <?= nl2br(htmlspecialchars($q['explanation'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button class="btn btn-primary" type="submit" id="btn-submit-quiz" disabled>
                        Kirim Jawaban
                    </button>
                </form>

                <?php if (!empty($nextLesson)): ?>
                    <div class="mt-3">
                        <?php if ($hasPassedLesson): ?>
                            <a class="btn btn-success btn-sm"
                                href="index.php?kursus=<?= htmlspecialchars($lesson['course_slug']) ?>&lesson=<?= (int)$nextLesson['id'] ?>">
                                Lanjut ke materi berikutnya:
                                <?= htmlspecialchars($nextLesson['title']) ?>
                            </a>
                        <?php else: ?>
                            <div class="alert alert-info small mt-2">
                                Untuk membuka materi berikutnya, silakan kerjakan soal
                                hingga semua jawaban benar.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>
</section>

<script>
    (function() {
        var list = document.getElementById('lesson-points');
        var btnNext = document.getElementById('btn-next-point');
        var hint = document.getElementById('lesson-hint');
        var btnSubmit = document.getElementById('btn-submit-quiz');

        if (!list || !btnNext) return;

        var points = Array.prototype.slice.call(list.querySelectorAll('.lesson-point'));
        if (!points.length) {
            if (btnSubmit) btnSubmit.disabled = false;
            return;
        }

        var currentIndex = 0;
        var lastShownAt = Date.now();

        function updateHint(msg) {
            if (hint) {
                hint.textContent = msg;
            }
        }

        btnNext.addEventListener('click', function() {
            var currentPoint = points[currentIndex];
            var requiredSeconds = parseInt(currentPoint.getAttribute('data-required-seconds') || '0', 10);
            var requiredMs = requiredSeconds * 1000;
            var elapsed = Date.now() - lastShownAt;

            if (elapsed < requiredMs) {
                var remaining = Math.ceil((requiredMs - elapsed) / 1000);
                updateHint('Silakan baca dulu, sekitar ' + remaining +
                    ' detik lagi baru bisa lanjut ke poin berikutnya.');
                return;
            }

            var lastIndex = points.length - 1;

            if (currentIndex < lastIndex - 1) {
                // Masih ada minimal 2 poin lagi
                currentIndex++;
                points[currentIndex].style.display = 'list-item';
                lastShownAt = Date.now();
                updateHint('Tekan tombol Lanjut lagi setelah membaca poin ini.');
            } else if (currentIndex === lastIndex - 1) {
                // Klik ini akan menampilkan poin terakhir
                currentIndex++;
                points[currentIndex].style.display = 'list-item';
                lastShownAt = Date.now();
                btnNext.disabled = true;

                // Semua poin sudah tampil
                updateHint('Semua poin materi sudah ditampilkan. Soal bisa diaktifkan (tahap berikutnya).');
                if (btnSubmit) btnSubmit.disabled = false;
            } else {
                // Sudah di poin terakhir sejak awal
                btnNext.disabled = true;
                updateHint('Semua poin materi sudah ditampilkan. Soal bisa diaktifkan (tahap berikutnya).');
                if (btnSubmit) btnSubmit.disabled = false;
            }
        });
    })();
</script>