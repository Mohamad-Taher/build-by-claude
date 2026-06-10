<?php
/**
 * Layout footer: page footer + all JS plugins + global init.
 * Optional: a page may set $extra_js (string of <script> tags) before
 * including footer.php — echoed after all plugins so jQuery is available.
 */
?>
    <!-- ============ Footer ============ -->
    <footer class="main-footer">
        <strong><?= e(get_setting('system_name', 'Car Import System')) ?></strong>
        — <?= e(t('copyright')) ?> &copy; <?= date('Y') ?>
        <div class="float-right d-none d-sm-inline-block">
            <b>Peshang AI</b>
        </div>
    </footer>

</div><!-- /.wrapper -->

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
// ---- Global UI defaults ------------------------------------------------
$(function () {
    // Select2 with the Bootstrap 4 theme (NOT inside modals — modals must
    // initialize on shown.bs.modal and destroy first; see project notes)
    $('.select2:not(.in-modal)').select2({ theme: 'bootstrap4' });

    // Language switcher in the navbar
    $('.lang-switch').on('click', function (e) {
        e.preventDefault();
        $.post('ajax/lang/switch.php', { lang: $(this).data('lang') }, function (res) {
            if (res.success) { location.reload(); }
        }, 'json');
    });
});

// SweetAlert2 helpers used across pages
function swalSuccess(msg) {
    Swal.fire({ icon: 'success', title: msg, timer: 1500, showConfirmButton: false });
}
function swalError(msg) {
    Swal.fire({ icon: 'error', title: '<?= e(t('error')) ?>', text: msg });
}
function swalConfirmDelete(callback) {
    Swal.fire({
        title: '<?= e(t('confirm_delete_title')) ?>',
        text: '<?= e(t('confirm_delete_text')) ?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: '<?= e(t('confirm_yes_delete')) ?>',
        cancelButtonText: '<?= e(t('cancel')) ?>'
    }).then(function (result) { if (result.isConfirmed) callback(); });
}
</script>

<?= $extra_js ?? '' ?>
</body>
</html>
