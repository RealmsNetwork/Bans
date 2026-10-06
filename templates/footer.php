<?php /* Realms Bans footer */ ?>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars(asset('assets/js/main.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <div>
                <strong><?= htmlspecialchars($config['footer_site_name'] ?: 'Realms Bans', ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="footer-muted">Public punishment history for RealmsNetwork.</span>
            </div>
            <div class="footer-meta">
                <span>&#169; <?= date('Y') ?></span>
                <span class="footer-dot">·</span>
                <a href="https://github.com/RealmsNetwork/Bans" target="_blank" rel="noopener noreferrer">THEMPGUY / RealmsNetwork</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
