<?php
$basePath = $basePath ?? (function_exists('bdr_base_path') ? bdr_base_path() : '.');
?>
                </main>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $basePath ?>/assets/js/script.js"></script>
    <?php if (!empty($pageScripts)): ?>
        <?php foreach ((array)$pageScripts as $script): ?>
            <script src="<?= $basePath ?>/<?= ltrim($script, '/') ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>