<?php
require_once "auth.php";
check_admin();

// Filter berdasarkan Kursus (Optional)
$filterCourse = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Query Data Kursus untuk Dropdown Filter
$courses = $pdo->query("SELECT id, title FROM courses")->fetchAll();

// QUERY UTAMA: Menggabungkan User, Kursus, dan Progres
// Logika: Kita hitung jumlah materi yg lulus dibagi total materi kursus
$sql = "
    SELECT 
        u.name AS user_name,
        u.email,
        c.title AS course_title,
        c.lessons AS total_lessons,
        COUNT(lp.lesson_id) AS passed_lessons,
        MAX(lp.updated_at) AS last_activity
    FROM users u
    JOIN lesson_progress lp ON u.id = lp.user_id
    JOIN lessons l ON lp.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE u.role = 'user' AND lp.has_passed = 1
";

if ($filterCourse > 0) {
    $sql .= " AND c.id = $filterCourse ";
}

$sql .= " GROUP BY u.id, c.id ORDER BY last_activity DESC";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll();
?>

<div class="container my-5">
    <div class="mb-3">
        <a href="index.php?page=admin" class="text-decoration-none">← Kembali ke Dashboard</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Rekap Progres Siswa</h1>
        
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="page" value="admin_progress">
            <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="0">-- Semua Kursus --</option>
                <?php foreach($courses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterCourse == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">Nama Siswa</th>
                            <th>Kursus</th>
                            <th>Progres</th>
                            <th>Status</th>
                            <th>Terakhir Akses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <?php
                                // Hitung Persentase
                                $percent = 0;
                                if ($row['total_lessons'] > 0) {
                                    $percent = round(($row['passed_lessons'] / $row['total_lessons']) * 100);
                                }
                                if ($percent > 100) $percent = 100;
                            ?>
                        <tr>
                            <td class="px-4">
                                <div class="fw-bold"><?= htmlspecialchars($row['user_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['course_title']) ?></td>
                            <td style="width: 200px;">
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                        <div class="progress-bar bg-<?= $percent == 100 ? 'success' : 'primary' ?>" 
                                             role="progressbar" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    <span class="small fw-bold"><?= $percent ?>%</span>
                                </div>
                                <div class="small text-muted mt-1">
                                    <?= $row['passed_lessons'] ?> dari <?= $row['total_lessons'] ?> materi
                                </div>
                            </td>
                            <td>
                                <?php if ($percent == 100): ?>
                                    <span class="badge bg-success">LULUS</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Sedang Belajar</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?= date('d M Y H:i', strtotime($row['last_activity'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    Belum ada data progres belajar siswa.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>