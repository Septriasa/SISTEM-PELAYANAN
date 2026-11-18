<?php
session_start();
date_default_timezone_set("Asia/Jakarta");

$host    = "localhost";
$user    = "root";
$pass    = "";          
$nama_db = "ksop_db";

try {
    $koneksi = mysqli_connect($host, $user, $pass, $nama_db);
} catch (Exception $e) {
    die("Koneksi database gagal. Pastikan MySQL di Laragon sudah menyala dan file database/ksop.sql sudah dijalankan lewat HeidiSQL.");
}

mysqli_set_charset($koneksi, "utf8mb4");
mysqli_query($koneksi, "SET time_zone = '+07:00'");
