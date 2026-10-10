<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Reports';
$db = getDB();

$range = $_GET['range'] ?? 'month';
$filterDate = match($range) {
    'quarter' => date('Y-m-d', strtotime('-3 months')),
    'all'     => '2000-01-01',
    default   => date('Y-m-01'),
};
$filterLabel = match($range) {
    'quarter' => 'Last 3 Months',
    'all'     => 'All Time',
    default   => 'This Month',
};

// Category-wise complaint count
$stmt = $db->prepare("
    SELECT cat.name AS cat_name, COUNT(c.complaint_id) AS cnt
    FROM categories cat
    LEFT JOIN complaints c ON cat.category_id = c.category_id AND DATE(c.created_at) >= ?
    GROUP BY cat.category_id, cat.name ORDER BY cnt DESC
");
$stmt->execute([$filterDate]);
$categoryData = $stmt->fetchAll();

// Monthly trend last 6 months
$stmt2 = $db->prepare("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS mon,
           DATE_FORMAT(created_at,'%b %Y') AS mon_label,
           COUNT(*) AS cnt
    FROM complaints WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mon ORDER BY mon ASC
");
$stmt2->execute();
$monthlyData = $stmt2->fetchAll();

// Status distribution
$stmt3 = $db->prepare("
    SELECT status, COUNT(*) AS cnt FROM complaints
    WHERE DATE(created_at) >= ? GROUP BY status ORDER BY cnt DESC
");
$stmt3->execute([$filterDate]);
$statusData = $stmt3->fetchAll();

// Summary per category: total, resolved, avg_rating
$stmt4 = $db->prepare("
    SELECT cat.name AS cat_name,
           COUNT(c.complaint_id) AS total,
           SUM(CASE WHEN c.status = 'resolved' THEN 1 ELSE 0 END) AS resolved,
           ROUND(AVG(f.rating), 1) AS avg_rating
    FROM categories cat
    LEFT JOIN complaints c ON cat.category_id = c.category_id AND DATE(c.created_at) >= ?
    LEFT JOIN feedback f ON c.complaint_id = f.complaint_id
    GROUP BY cat.category_id, cat.name ORDER BY total DESC
");
$stmt4->execute([$filterDate]);
$summaryData = $stmt4->fetchAll();

$totalComplaints = array_sum(array_column($summaryData, 'total'));
$totalResolved   = array_sum(array_column($summaryData, 'resolved'));
$overallAvgRating = count($summaryData) > 0
    ? round(array_sum(array_filter(array_column($summaryData, 'avg_rating'), fn($v) => $v !== null)) / max(1, count(array_filter(array_column($summaryData, 'avg_rating')))), 1)
    : 0;

$chartJSON = json_encode([
    'categories' => $categoryData,
    'monthly'    => $monthlyData,
    'status'     => $statusData,
]);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="margin-bottom:20px">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h4><i class="bi bi-bar-chart-line"></i>Reports</h4>
            <p>Category, trend, and status analysis &mdash; <strong><?= $filterLabel ?></strong></p>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="?range=month"   style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid <?= $range==='month'  ?'#4f46e5' : '#e2e8f0' ?>;background:<?= $range==='month'  ?'#4f46e5':'#fff' ?>;color:<?= $range==='month'  ?'#fff':'#64748b' ?>">This Month</a>
            <a href="?range=quarter" style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid <?= $range==='quarter'?'#4f46e5' : '#e2e8f0' ?>;background:<?= $range==='quarter'?'#4f46e5':'#fff' ?>;color:<?= $range==='quarter'?'#fff':'#64748b' ?>">Last 3 Months</a>
            <a href="?range=all"     style="padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;border:1.5px solid <?= $range==='all'    ?'#4f46e5' : '#e2e8f0' ?>;background:<?= $range==='all'    ?'#4f46e5':'#fff' ?>;color:<?= $range==='all'    ?'#fff':'#64748b' ?>">All Time</a>
        </div>
    </div>
</div>

<!-- Top stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-icon primary"><i class="bi bi-chat-dots"></i></div>
            <div class="stat-value"><?= $totalComplaints ?></div>
            <div class="stat-label">Total Complaints</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?= $totalResolved ?></div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="bi bi-star"></i></div>
            <div class="stat-value"><?= $overallAvgRating ?: 'N/A' ?></div>
            <div class="stat-label">Avg Rating</div>
        </div>
    </div>
</div>

<!-- Charts row 1 -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold bg-light"><i class="bi bi-bar-chart me-2"></i>Category-wise Complaints</div>
            <div class="card-body" style="position:relative;height:300px"><canvas id="categoryChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold bg-light"><i class="bi bi-pie-chart me-2"></i>Status Distribution</div>
            <div class="card-body" style="position:relative;height:300px"><canvas id="statusChart"></canvas></div>
        </div>
    </div>
</div>

<!-- Monthly trend -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header fw-semibold bg-light"><i class="bi bi-graph-up me-2"></i>Monthly Complaint Trend (Last 6 Months)</div>
    <div class="card-body" style="position:relative;height:280px"><canvas id="monthlyChart"></canvas></div>
</div>

<!-- Summary table -->
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold bg-light"><i class="bi bi-table me-2"></i>Summary by Category</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Category</th><th>Total</th><th>Resolved</th><th>Pending</th><th>Resolution %</th><th>Avg Rating</th></tr>
            </thead>
            <tbody>
                <?php foreach ($summaryData as $row):
                    $pending = $row['total'] - $row['resolved'];
                    $rate    = $row['total'] > 0 ? round($row['resolved'] / $row['total'] * 100, 1) : 0;
                    $rc      = $row['avg_rating'] >= 4 ? 'success' : ($row['avg_rating'] >= 3 ? 'warning' : 'danger');
                ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($row['cat_name']) ?></td>
                    <td><?= $row['total'] ?></td>
                    <td><span class="badge bg-success"><?= $row['resolved'] ?></span></td>
                    <td><span class="badge bg-warning text-dark"><?= $pending ?></span></td>
                    <td>
                        <div class="progress" style="height:6px;width:100px">
                            <div class="progress-bar bg-success" style="width:<?= $rate ?>%"></div>
                        </div>
                        <small><?= $rate ?>%</small>
                    </td>
                    <td>
                        <?php if ($row['avg_rating']): ?>
                            <span class="badge bg-<?= $rc ?>"><?= $row['avg_rating'] ?> <i class="bi bi-star-fill"></i></span>
                        <?php else: ?>
                            <span class="text-muted small">N/A</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const chartData = <?= $chartJSON ?>;
const COLORS = ['#2563eb','#7c3aed','#10b981','#f59e0b','#ef4444','#06b6d4','#84cc16'];
const STATUS_COLORS = { pending:'#f59e0b', assigned:'#2563eb', in_progress:'#7c3aed', resolved:'#10b981', closed:'#6b7280' };

// Category bar chart
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: chartData.categories.map(d => d.cat_name),
        datasets: [{ label: 'Complaints', data: chartData.categories.map(d => d.cnt),
            backgroundColor: COLORS, borderRadius: 6 }]
    },
    options: { responsive:true, maintainAspectRatio:false, indexAxis:'y',
        plugins:{ legend:{display:false} }, scales:{ x:{ beginAtZero:true, ticks:{stepSize:1} } } }
});

// Status doughnut
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: chartData.status.map(d => d.status.replace('_',' ')),
        datasets: [{ data: chartData.status.map(d => d.cnt),
            backgroundColor: chartData.status.map(d => STATUS_COLORS[d.status] || '#999'),
            borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{position:'bottom'} } }
});

// Monthly line chart
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: chartData.monthly.map(d => d.mon_label),
        datasets: [{ label:'Complaints', data: chartData.monthly.map(d => d.cnt),
            borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,0.1)',
            fill:true, tension:0.4, borderWidth:3,
            pointBackgroundColor:'#2563eb', pointBorderColor:'#fff', pointBorderWidth:2, pointRadius:5 }]
    },
    options: { responsive:true, maintainAspectRatio:false,
        scales:{ y:{ beginAtZero:true, ticks:{stepSize:1} } } }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
