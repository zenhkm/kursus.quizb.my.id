<?php
// auth_forgot.php
require __DIR__ . '/config.php';

$message = '';
$error   = '';
$debugLink = ''; // Variabel bantu untuk testing di localhost

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // 1. Cek apakah email terdaftar
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. Buat Token Unik
        $token = bin2hex(random_bytes(32)); // Token acak 64 karakter

        // 3. Simpan Token ke Database
        // Hapus token lama jika ada (biar bersih)
        $stmtDel = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmtDel->execute([$email]);

        // Insert token baru
        $stmtIns = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
        $stmtIns->execute([$email, $token]);

        // 4. Siapkan Link Reset
        // Ganti domain sesuai website Anda nanti
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $domain   = $_SERVER['HTTP_HOST'];
        $path     = dirname($_SERVER['PHP_SELF']); // Mendapatkan folder saat ini
        $resetLink = "$protocol://$domain$path/auth_reset.php?token=$token&email=" . urlencode($email);

        // 5. Kirim Email
        $to      = $email;
        $subject = "Reset Password Kursus Online";
        $msgBody = "Halo " . $user['name'] . ",\n\n";
        $msgBody .= "Silakan klik link di bawah ini untuk mereset password Anda:\n";
        $msgBody .= $resetLink . "\n\n";
        $msgBody .= "Link ini berlaku sementara. Abaikan jika Anda tidak memintanya.";
        $headers = "From: no-reply@quizb.my.id";

        // Coba kirim email
        if (@mail($to, $subject, $msgBody, $headers)) {
            $message = "Link reset password telah dikirim ke email Anda. Silakan cek Inbox/Spam.";
        } else {
            // JIKA GAGAL KIRIM (Biasanya di Localhost/Hosting tanpa SMTP)
            // Kita tampilkan linknya di layar supaya Anda bisa tetap test fitur ini.
            $message   = "Email server sedang sibuk. Gunakan link di bawah ini untuk reset manual (Mode Testing):";
            $debugLink = $resetLink;
        }
    } else {
        $error = "Email tersebut tidak terdaftar.";
    }
}

$pageTitle = 'Lupa Password';
include __DIR__ . '/layout/header.php';
?>

<div class="container my-5" style="max-width: 480px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h3 class="card-title mb-3 text-center">Lupa Password?</h3>
            <p class="text-muted text-center small mb-4">
                Masukkan email Anda, kami akan mengirimkan link untuk membuat password baru.
            </p>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($message) ?>
                    <?php if ($debugLink): ?>
                        <div class="mt-2 p-2 bg-white rounded border small text-break">
                            <a href="<?= htmlspecialchars($debugLink) ?>">Klik di sini untuk Reset Password</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Email Terdaftar</label>
                    <input type="email" name="email" class="form-control" required placeholder="nama@email.com">
                </div>
                <button type="submit" class="btn btn-primary w-100">Kirim Link Reset</button>
            </form>

            <div class="mt-4 text-center">
                <a href="auth.php?action=login" class="text-decoration-none small">
                    ← Kembali ke Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>