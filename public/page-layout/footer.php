            </div>
        </div>
    </div>
    <?php $basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.'); ?>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars($basePath) ?>/assets/js/script.js"></script>
    <?php if (!empty($pageScripts)): ?>
        <?php foreach ((array)$pageScripts as $script): ?>
            <script src="<?= htmlspecialchars($basePath) ?>/<?= ltrim($script, '/') ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
