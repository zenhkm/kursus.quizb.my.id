<?php
require_once "auth.php";
check_login();
// pages/home.php

$userId = $_SESSION['user']['id'] ?? 0;

// 1. Ambil semua kursus
$stmt = $pdo->query("SELECT * FROM courses ORDER BY id ASC");
$courses = $stmt->fetchAll();

// 2. Hitung progres user per kursus
$sqlProgress = "
    SELECT 
        l.course_id, 
        COUNT(lp.lesson_id) as total_passed
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    WHERE lp.user_id = ? AND lp.has_passed = 1
    GROUP BY l.course_id
";
$stmtProg = $pdo->prepare($sqlProgress);
$stmtProg->execute([$userId]);
$progressData = $stmtProg->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Hitung total semua materi yang lulus (untuk Statistik Header)
$totalMateriSelesai = array_sum($progressData);
?>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-12 col-md-8">
                <div class="hero-badge mb-2">
                    <span>✅ Belajar Berjenjang</span>
                    <span>•</span>
                    <span>Materi → Soal → Naik Level</span>
                </div>
                <h1 class="h3 mb-2">
                    Selamat datang, <strong><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Peserta') ?></strong>!
                </h1>
                <p class="text-muted mb-0">
                    Lanjutkan pembelajaran Anda. Setiap materi harus diselesaikan 
                    secara berurutan untuk membuka materi berikutnya.
                </p>
            </div>
            
            <div class="col-12 col-md-4 text-md-end">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-body p-2 d-flex align-items-center justify-content-center gap-3">
                        <div class="text-end">
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Total Materi Lulus</small>
                            <span class="fw-bold text-success fs-5"><?= $totalMateriSelesai ?></span>
                            <span class="text-muted small">Materi</span>
                        </div>
                        <div class="bg-success bg-opacity-10 p-2 rounded-circle text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-trophy-fill" viewBox="0 0 16 16">
                              <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.377.135.531.255L13.3 15.1a.5.5 0 0 1-.3.9H3a.5.5 0 0 1-.3-.9l1.838-1.379c.154-.12.337-.207.531-.255l1.425-.356v-2.173a5.355 5.355 0 0 1-2.716-.998A3 3 0 0 1 2.536 2.036 10.73 10.73 0 0 1 2.5.5z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Daftar Kursus</h2>
            <small class="text-muted">Pantau kemajuan belajar Anda di sini.</small>
        </div>

        <div class="row g-3">
            <?php foreach ($courses as $course): ?>
                <?php 
                    // Siapkan Variabel
                    $courseId    = $course['id'];
                    $totalLesson = (int)$course['lessons'];
                    $passedCount = isset($progressData[$courseId]) ? (int)$progressData[$courseId] : 0;
                    $isAvailable = ($course['status'] === 'Tersedia');
                    
                    // URL Link Kursus
                    $courseUrl   = "index.php?kursus=" . urlencode($course['slug']);

                    // Hitung Persentase
                    $percent = 0;
                    if ($totalLesson > 0) {
                        $percent = round(($passedCount / $totalLesson) * 100);
                    }
                    if ($percent > 100) $percent = 100;
                ?>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="course-card h-100 d-flex flex-column overflow-hidden">
                        
                        <div style="height: 160px; overflow: hidden; background-color: #eee; position: relative;">
                            
                            <?php if ($isAvailable): ?>
                                <a href="<?= $courseUrl ?>" class="d-block w-100 h-100 text-decoration-none">
                            <?php endif; ?>

                                <?php if (!empty($course['image']) && file_exists('uploads/' . $course['image'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($course['image']) ?>" 
                                         alt="<?= htmlspecialchars($course['title']) ?>"
                                         class="w-100 h-100" 
                                         style="object-fit: cover; transition: transform 0.3s ease;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted bg-light">
                                        <span style="font-size: 3rem;">📚</span>
                                    </div>
                                <?php endif; ?>

                            <?php if ($isAvailable): ?>
                                </a>
                            <?php endif; ?>
                            
                            <div class="position-absolute top-0 end-0 p-2">
                                <?php if ($isAvailable): ?>
                                    <span class="badge bg-success shadow-sm">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary shadow-sm">Segera Hadir</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="mb-2">
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($course['level']) ?>
                                </span>
                            </div>

                            <h3 class="h6 mb-2 fw-bold">
                                <?php if ($isAvailable): ?>
                                    <a href="<?= $courseUrl ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($course['title']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($course['title']) ?>
                                <?php endif; ?>
                            </h3>
                            
                            <p class="text-muted small mb-3">
                                <?= htmlspecialchars($course['description']) ?>
                            </p>

                            <div class="mb-3 small text-muted">
                                <div>⏱ Perkiraan durasi: <?= htmlspecialchars($course['duration']) ?></div>
                                <?php if ($totalLesson > 0): ?>
                                    <div>📚 Materi selesai: <strong><?= $passedCount ?></strong> / <?= $totalLesson ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Progres belajar</span>
                                    <span class="fw-bold"><?= $percent ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?= $percent ?>%;" 
                                         aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <?php if ($isAvailable): ?>
                                    <a href="<?= $courseUrl ?>" class="btn btn-primary btn-sm">
                                        <?php echo ($percent > 0) ? 'Lanjutkan' : 'Mulai Belajar'; ?>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" type="button" disabled>
                                        Segera Hadir
                                    </button>
                                <?php endif; ?>

                                <small class="text-muted">
                                    Kode: <?= strtoupper(htmlspecialchars($course['slug'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>