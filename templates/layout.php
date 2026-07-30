<?php include 'parts/header.php'; ?>

<?php
// Memuat konten halaman dinamis yang ditentukan oleh router
if (isset($content_page) && file_exists($content_page)) {
    include $content_page;
} else {
    // Fallback jika variabel tidak di-set atau file tidak ada
    include '404.php';
}
?>

<?php include 'parts/footer.php'; ?>