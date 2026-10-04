<?php
require_once __DIR__ . '/../includes/page-guard.php';
require_once __DIR__ . '/../config/database.php';
bdr_require_page_role(['staff']);
$pageTitle = "Reports";
$pageDescription = "Review requests status and summaries";
$role = $_SESSION['role'] ?? 'staff';
$name = $_SESSION['name'] ?? 'Staff';
$basePath = '..';

$notifications = [];
$summary = $pdo->query("SELECT COUNT(*) AS received, SUM(LOWER(status) = 'released') AS claimed, SUM(LOWER(status) = 'pending') AS pending, SUM(LOWER(status) = 'rejected') AS cancelled FROM requests")->fetch() ?: [];
$turnaround = $pdo->query("SELECT AVG(DATEDIFF(h.updated_at, q.request_date)) AS avg_days, AVG(DATEDIFF(h.updated_at, q.request_date) <= d.processing_days) AS on_time FROM requests q JOIN document_types d ON d.document_id=q.document_id JOIN request_history h ON h.request_id=q.request_id AND LOWER(h.status)='released'")->fetch() ?: [];
$summary['avg_turnaround'] = number_format((float) ($turnaround['avg_days'] ?? 0), 1) . ' days';
$summary['on_time_pct'] = (int) round(100 * (float) ($turnaround['on_time'] ?? 0));
$monthly = $pdo->query("SELECT DATE_FORMAT(request_date, '%b') AS label, COUNT(*) AS value FROM requests WHERE request_date >= DATE_SUB(CURRENT_DATE, INTERVAL 5 MONTH) GROUP BY YEAR(request_date), MONTH(request_date), DATE_FORMAT(request_date, '%b') ORDER BY YEAR(request_date), MONTH(request_date)")->fetchAll();
$byType = $pdo->query("SELECT d.document_name AS label, COUNT(q.request_id) AS value, CONCAT(d.processing_days, IF(d.processing_days=1, ' business day', ' business days')) AS processing FROM document_types d LEFT JOIN requests q ON q.document_id=d.document_id GROUP BY d.document_id, d.document_name, d.processing_days ORDER BY value DESC, d.document_name")->fetchAll();

// Compute the first/last month labels once, as plain strings, before
// any HTML — avoids re-deriving an index inside the markup.
$firstLabel = $monthly[0]['label'] ?? '';
$summary += ['received' => 0, 'claimed' => 0, 'pending' => 0, 'cancelled' => 0];
$lastMonth  = end($monthly) ?: ['label' => '', 'value' => 0];
$lastLabel  = $lastMonth['label'];
reset($monthly);

// Line-chart geometry: points spread evenly across the width, scaled
// between the lowest and highest monthly value.
$chartW = 600; $chartH = 200; $padL = 16; $padR = 16; $padTop = 16; $padBottom = 26;
$n = count($monthly);
$values = array_column($monthly, 'value');
$maxVal = $values ? max($values) : 1;
$minVal = $values ? min($values) : 0;
$range  = max(1, $maxVal - $minVal);
$stepX  = $n > 1 ? ($chartW - $padL - $padR) / ($n - 1) : 0;

$points = [];
foreach ($monthly as $i => $m) {
    $x = $padL + $stepX * $i;
    $y = $padTop + ($chartH - $padTop - $padBottom) * (1 - ($m['value'] - $minVal) / $range);
    $points[] = ['x' => round($x, 1), 'y' => round($y, 1), 'label' => $m['label'], 'value' => $m['value']];
}
$polyline = implode(' ', array_map(fn($p) => $p['x'] . ',' . $p['y'], $points));
$floorY = $chartH - $padBottom + 8;
$areaPoints = $points ? $polyline . ' ' . end($points)['x'] . ',' . $floorY . ' ' . $points[0]['x'] . ',' . $floorY : '';
reset($points);

?>

<?php include __DIR__ . '/../page-layout/header.php' ?>
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <div class="stats-panel mb-4">
            <div class="row g-0">
                <div class="col-12 col-lg-4">
                    <div class="stat-card h-100 px-3 py-3 border-end">
                        <div class="stat-card-header mb-2">Requests Received &middot; <?= htmlspecialchars($lastLabel) ?></div>
                        <div class="d-flex align-items-end gap-2">
                            <div class="stat-card-value stat-card-value-large"><?= (int) $summary['received'] ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-card h-100 px-3 py-3 border-end">
                        <div class="stat-card-header text-secondary mb-2">Claimed</div>
                        <div class="stat-card-value"><?= (int) $summary['claimed'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-card h-100 px-3 py-3 border-end">
                        <div class="stat-card-header text-secondary mb-2">Pending</div>
                        <div class="stat-card-value"><?= (int) $summary['pending'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-card h-100 px-3 py-3 border-end">
                        <div class="stat-card-header text-secondary mb-2">Cancelled</div>
                        <div class="stat-card-value"><?= (int) $summary['cancelled'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-card h-100 px-3 py-3 border-end-0">
                        <div class="stat-card-header text-secondary mb-2">On-Time Rate</div>
                        <div class="stat-card-value"><?= (int) $summary['on_time_pct'] ?>%</div>
                    </div>
                </div>
            </div>
        </div>

        <section class="panel-white p-4 mb-4 chart-panel">
            <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0">Monthly Request Volume</h5>
                    <p class="chart-caption mb-0"><?= htmlspecialchars($firstLabel) ?> to <?= htmlspecialchars($lastLabel) ?> 2026</p>
                </div>
                <span class="chart-caption">Avg. processing: <?= htmlspecialchars($summary['avg_turnaround']) ?></span>
            </div>

            <svg viewBox="0 0 <?= $chartW ?> <?= $chartH ?>" width="100%" height="240" role="img"
                 aria-label="Monthly requests: <?= htmlspecialchars(implode(', ', array_map(fn($m) => $m['label'] . ' ' . $m['value'], $monthly))) ?>">
                <g stroke="var(--border-soft)" stroke-width="1">
                    <?php for ($i = 0; $i <= 4; $i++): $gy = $padTop + $i * (($chartH - $padTop - $padBottom) / 4); ?>
                        <line x1="0" y1="<?= round($gy, 1) ?>" x2="<?= $chartW ?>" y2="<?= round($gy, 1) ?>"></line>
                    <?php endfor; ?>
                </g>
                <?php if ($points): ?>
                <polyline points="<?= $polyline ?>" fill="none" stroke="var(--dark-green)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                <polygon points="<?= $areaPoints ?>" fill="var(--dark-green)" opacity="0.08" stroke="none"></polygon>
                <g fill="#ffffff" stroke="var(--dark-green)" stroke-width="3">
                    <?php foreach ($points as $p): ?><circle cx="<?= $p['x'] ?>" cy="<?= $p['y'] ?>" r="5"></circle><?php endforeach; ?>
                </g>
                <g fill="var(--text-dark)" font-size="13" font-weight="700" text-anchor="middle" font-family="Poppins, sans-serif">
                    <?php foreach ($points as $p): ?><text x="<?= $p['x'] ?>" y="<?= $p['y'] - 12 ?>"><?= (int) $p['value'] ?></text><?php endforeach; ?>
                </g>
                <g fill="var(--light-gray)" font-size="11" text-anchor="middle" font-family="Poppins, sans-serif">
                    <?php foreach ($points as $p): ?><text x="<?= $p['x'] ?>" y="<?= $chartH - 6 ?>"><?= htmlspecialchars($p['label']) ?></text><?php endforeach; ?>
                </g>
                <?php endif; ?>
            </svg>
        </section>

        <section class="panel-white p-4">
            <h5 class="fw-bold mb-3">Requests by Document Type &middot; <?= htmlspecialchars($lastLabel) ?></h5>
            <div class="table-responsive">
                <table class="table table-brand align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th class="text-end">Requests</th>
                            <th class="text-end">Target Processing Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byType as $t): ?>
                            <tr>
                                <td class="fw-semibold heading-green"><?= htmlspecialchars($t['label']) ?></td>
                                <td class="text-end"><?= (int) $t['value'] ?></td>
                                <td class="text-end text-muted-soft"><?= htmlspecialchars($t['processing']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
<?php include __DIR__ . '/../page-layout/footer.php' ?>
