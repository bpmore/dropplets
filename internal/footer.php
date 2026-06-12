<?php use function Fieldnote\e; ?>
    </div>
    <?php if (!empty($needsEditor)): ?>
        <script src="<?= e($siteConfig['basePath']) ?>/static/vendor/easymde.min.js" defer></script>
    <?php endif; ?>
    <?php if (!empty($needsQr)): ?>
        <script src="<?= e($siteConfig['basePath']) ?>/static/vendor/qrcode.js" defer></script>
    <?php endif; ?>
    <script src="<?= e($siteConfig['basePath']) ?>/static/admin.js" defer></script>
</body>
</html>
