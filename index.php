<?php
ob_start();

// index.php
require __DIR__ . '/config.php';
require_once "auth.php";
check_login();

// Router
$page              = isset($_GET['page']) ? $_GET['page'] : '';
$currentCourseSlug = isset($_GET['kursus']) ? trim($_GET['kursus']) : null;
$lessonId          = isset($_GET['lesson']) ? (int)$_GET['lesson'] : null;

// -- ROUTE KE HALAMAN ADMIN --
if ($page === 'admin') {
    $pageTitle = 'Dashboard Admin';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/admin_home.php'; // File baru
    include __DIR__ . '/layout/footer.php';

} elseif ($page === 'admin_lessons') {
    $pageTitle = 'Kelola Materi';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/admin_lessons.php';
    include __DIR__ . '/layout/footer.php';

} elseif ($page === 'admin_lesson_form') {
    $pageTitle = 'Form Materi';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/admin_lesson_form.php';
    include __DIR__ . '/layout/footer.php';

} elseif ($page === 'profile') {
    $pageTitle = 'Profil Saya';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/profile.php';
    include __DIR__ . '/layout/footer.php';
    
} elseif ($page === 'admin_course_form') {
    $pageTitle = 'Form Kursus Admin';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/admin_course_form.php'; // File baru
    include __DIR__ . '/layout/footer.php';

} elseif ($page === 'admin_modules') {
    $pageTitle = 'Kelola Bab Kursus';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/admin_modules.php';
    include __DIR__ . '/layout/footer.php';

// -- ROUTE HALAMAN UTAMA (USER) --
} elseif ($currentCourseSlug && $lessonId) {
    $pageTitle = 'Materi Kursus';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/lesson_view.php';
    include __DIR__ . '/layout/footer.php';

} elseif ($currentCourseSlug) {
    $pageTitle = 'Detail Kursus';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/kursus_detail.php';
    include __DIR__ . '/layout/footer.php';

} else {
    $pageTitle = 'Kursus Online – Beranda';
    include __DIR__ . '/layout/header.php';
    include __DIR__ . '/pages/home.php';
    include __DIR__ . '/layout/footer.php';
}

ob_end_flush();