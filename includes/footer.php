        </main>
    </div><!-- akhir .main -->
</div><!-- akhir .app -->

<!-- Tempat munculnya jendela popup (modal) -->
<div id="modal" hidden></div>

<?php
// kalau ada pesan sukses / gagal dari halaman sebelumnya, tampilkan di sini
$pesan = ambil_pesan();
if ($pesan) {
?>
    <div id="toast" class="toast<?= ($pesan['tipe'] == 'err') ? ' err' : '' ?>" role="status"><?= e($pesan['teks']) ?></div>
<?php } ?>

<script src="js/app.js"></script>
<?php
// JavaScript khusus halaman (diisi di tiap halaman lewat $js_tambahan)
if (isset($js_tambahan)) {
    foreach ($js_tambahan as $file_js) {
        echo '<script src="js/' . $file_js . '"></script>' . "\n";
    }
}
?>
</body>
</html>
