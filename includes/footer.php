</main>
</div> <!-- closes .app-shell -->

<footer class="app-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="<?= logo_url() ?>" alt="" class="footer-logo" onerror="this.style.display='none'">
            <div>
                <div class="footer-title">Lost &amp; Found Management System</div>
                <div class="footer-sub">A secure internal tool for tracking and reuniting belongings.</div>
            </div>
        </div>
        <div class="footer-copy">
            &copy; <?= date('Y') ?> <?= e($GLOBALS['_system_org'] ?? 'Organization') ?>. All rights reserved.
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>