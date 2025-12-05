<?php
require_once "auth.php";
check_admin();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$course = null;
$error  = '';

// Jika Mode Edit, Ambil Data Lama
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    if (!$course) {
        die("Kursus tidak ditemukan.");
    }
}

// PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $slug        = trim($_POST['slug']);
    $level       = $_POST['level'];
    $status      = $_POST['status'];
    $description = $_POST['description'];
    $duration    = $_POST['duration'];
    
    // Logic Upload Gambar
    $imageName = $course['image'] ?? null; // Default pakai gambar lama (kalau edit)

    if (!empty($_FILES['image']['name'])) {
        $fileTmp   = $_FILES['image']['tmp_name'];
        $fileName  = $_FILES['image']['name'];
        $fileSize  = $_FILES['image']['size'];
        $fileExt   = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($fileExt, $allowed)) {
            $error = "Format gambar harus JPG, PNG, atau WEBP.";
        } elseif ($fileSize > 2000000) { // Max 2MB
            $error = "Ukuran gambar maksimal 2MB.";
        } else {
            // Buat nama unik: waktu_slug.ext
            $newFileName = time() . '_' . rand(100,999) . '.' . $fileExt;
            $uploadPath  = 'uploads/' . $newFileName; // Simpan di folder uploads/

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Jika edit & ada gambar lama, hapus gambar lama (opsional, biar hemat space)
                if ($imageName && file_exists('uploads/' . $imageName)) {
                    unlink('uploads/' . $imageName);
                }
                $imageName = $newFileName;
            } else {
                $error = "Gagal mengupload gambar. Pastikan folder 'uploads/' ada.";
            }
        }
    }

    if (empty($error)) {
        if (empty($title) || empty($slug)) {
            $error = "Judul dan Slug wajib diisi.";
        } else {
            try {
                if ($id > 0) {
                    // UPDATE
                    $sql = "UPDATE courses SET title=?, slug=?, image=?, level=?, status=?, description=?, duration=? WHERE id=?";
                    $params = [$title, $slug, $imageName, $level, $status, $description, $duration, $id];
                } else {
                    // INSERT BARU
                    $sql = "INSERT INTO courses (title, slug, image, level, status, description, duration, lessons) VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
                    $params = [$title, $slug, $imageName, $level, $status, $description, $duration];
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                header("Location: index.php?page=admin&msg=saved"); // Kembali ke dashboard
                exit;
            } catch (PDOException $e) {
                $error = "Gagal menyimpan: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container my-5" style="max-width: 700px;">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?= $id > 0 ? 'Edit Kursus' : 'Tambah Kursus Baru' ?></h5>
        </div>
        <div class="card-body">
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label class="form-label">Gambar Sampul (Thumbnail)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <div class="form-text">Format: JPG, PNG, WEBP. Maks 2MB. Biarkan kosong jika tidak ingin mengubah gambar.</div>
                    
                    <?php if (!empty($course['image'])): ?>
                        <div class="mt-2">
                            <label class="small text-muted">Gambar Saat Ini:</label><br>
                            <img src="uploads/<?= htmlspecialchars($course['image']) ?>" alt="Thumbnail" style="height: 100px; border-radius: 8px; border: 1px solid #ddd;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Judul Kursus</label>
                    <input type="text" name="title" class="form-control" required
                           value="<?= htmlspecialchars($course['title'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug (URL Unik)</label>
                    <input type="text" name="slug" class="form-control" required placeholder="contoh: belajar-php-dasar"
                           value="<?= htmlspecialchars($course['slug'] ?? '') ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select">
                            <option value="Pemula" <?= ($course['level'] ?? '') == 'Pemula' ? 'selected' : '' ?>>Pemula</option>
                            <option value="Menengah" <?= ($course['level'] ?? '') == 'Menengah' ? 'selected' : '' ?>>Menengah</option>
                            <option value="Mahir" <?= ($course['level'] ?? '') == 'Mahir' ? 'selected' : '' ?>>Mahir</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Tersedia" <?= ($course['status'] ?? '') == 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="Segera Hadir" <?= ($course['status'] ?? '') == 'Segera Hadir' ? 'selected' : '' ?>>Segera Hadir</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Durasi (Teks)</label>
                    <input type="text" name="duration" class="form-control" placeholder="Contoh: 2 Jam"
                           value="<?= htmlspecialchars($course['duration'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi Singkat</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php?page=admin" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Kursus</button>
                </div>
            </form>
        </div>
    </div>
</div>