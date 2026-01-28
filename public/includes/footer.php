<footer class="mt-auto py-3 bg-white border-top text-center mt-5">
    <div class="container">
        <span class="text-muted">OptionSignal Scanner &copy; <?= date('Y') ?></span>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($extraJs)) echo $extraJs; ?>
<script src="assets/js/app.js"></script>
</body>
</html>
