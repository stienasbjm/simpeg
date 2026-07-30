</main><!-- /.e-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo BASE_URL; ?>public/js/main.js"></script>

<?php if (!empty($_SESSION['success_message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Swal.fire({
    title: 'Berhasil!',
    text: <?php echo json_encode($_SESSION['success_message']); ?>,
    icon: 'success',
    confirmButtonText: 'OK',
    confirmButtonColor: '#10b981',
    timer: 4000,
    timerProgressBar: true,
    customClass: { popup: 'e-swal-popup' }
  });
});
</script>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  Swal.fire({
    title: 'Perhatian / Gagal!',
    text: <?php echo json_encode($_SESSION['error_message']); ?>,
    icon: 'error',
    confirmButtonText: 'Tutup',
    confirmButtonColor: '#ef4444',
    customClass: { popup: 'e-swal-popup' }
  });
});
</script>
<?php unset($_SESSION['error_message']); endif; ?>

</body>
</html>