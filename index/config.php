<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dsn  = 'mysql:host=localhost;dbname=quic1934_kursus;charset=utf8mb4';
$user = 'quic1934_zenhkm';
$pass = '03Maret1990';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}