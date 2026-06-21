<?php
// pages/analytics.php — Productivity analytics

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

$db     = getDB();
$userId = currentUserId();

/* ── Stats ───────────────────────────────────────────── */
$statsQ = $db->prepare("
    SELECT
        COUNT(DISTINCT t.id)            AS total_tasks,
        SUM(t.status = 'completed')     AS completed_tasks,
        SUM(t.status = 'in_progress')   AS in_progress_tasks,
        SUM(t.status = 'pending')       AS pending_tasks,
        COUNT(mt.id)                    AS total_steps,
        SUM(mt.is_completed)            AS done_steps
    FROM tasks t
    LEFT JOIN mini_tasks mt ON mt.task_id = t.id
    WHERE t.user_id = ?
");
$statsQ->execute([$userId]);
$stats = $statsQ->fetch();

$total      = (int)$stats['total_tasks'];
$completed  = (int)$stats['completed_tasks'];
$inProgress = (int)$stats['in_progress_tasks'];
$pending    = (int)$stats['pending_tasks'];
$totalSteps = (int)$stats['total_steps'];
$doneSteps  = (int)$stats['done_steps'];
$compRate   = $total > 0 ? round(($completed / $total) * 100) : 0;

// Streak
$streakQ = $db->prepare("SELECT current_streak FROM user_streaks WHERE user_id = ?");
$streakQ->execute([$userId]);
$streak = (int)($streakQ->fetchColumn() ?: 0);

/* ── Weekly activity (mini-tasks completed per day) ───── */
$weeklyQ = $db->prepare("
    SELECT DATE(mt.created_at) AS day, COUNT(*) AS cnt
    FROM mini_tasks mt
    JOIN tasks t ON t.id = mt.task_id
    WHERE t.user_id = ? AND mt.is_completed = 1
      AND mt.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(mt.created_at)
");
$weeklyQ->execute([$userId]);
$weeklyRaw = $weeklyQ->fetchAll(PDO::FETCH_KEY_PAIR);

// Build 7-day array Mon→Sun
$weekDays = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($date));
    $weekDays[] = ['label' => $label, 'count' => (int)($weeklyRaw[$date] ?? 0)];
}
$maxWeekly = max(array_column($weekDays, 'count') ?: [1]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics — Micro Task Planner</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body>
<div class="app-shell">
  <?php include '../includes/sidebar.php'; ?>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
        </svg>
        Workspace
      </div>
      <div class="topbar-right">
        <a href="../index.php">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          </svg>
          Landing
        </a>
      </div>
    </header>

    <div class="page-body">
      <h1 style="margin-bottom:.3rem">Analytics</h1>
      <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:1.75rem">Your productivity at a glance.</p>

      <!-- Stat cards -->
      <div class="analytics-stats">
        <?php
        $aStats = [
          ['COMPLETION RATE', $compRate . '%',        'M22 12h-4l-3 9L9 3l-3 9H2'],
          ['STEPS COMPLETED', "$doneSteps / $totalSteps", 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zM12 6v6l4 2'],
          ['ACTIVE TASKS',    $inProgress + $pending, 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zM12 6v6l4 2'],
          ['STREAK',          $streak . ' days',      'M12 2c0 6-8 10-8 10s8 4 8 10c0-6 8-10 8-10S12 8 12 2z'],
        ];
        foreach ($aStats as [$label, $value, $path]): ?>
        <div class="card analytics-card">
          <div class="a-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="<?= $path ?>"/>
            </svg>
          </div>
          <div class="a-label"><?= $label ?></div>
          <div class="a-value"><?= $value ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Charts -->
      <div class="charts-grid">
        <!-- Donut: task status distribution -->
        <div class="card chart-card">
          <div class="chart-title">Task status</div>
          <div class="chart-subtitle">Distribution across all tasks</div>
          <div class="donut-wrap">
            <svg width="160" height="160" viewBox="0 0 160 160" id="donutChart">
              <?php
              $donutData = [
                ['label' => 'Completed',   'value' => $completed,  'color' => '#1abc9c'],
                ['label' => 'In progress', 'value' => $inProgress, 'color' => '#3dd6b5'],
                ['label' => 'Pending',     'value' => $pending,    'color' => '#e2e8e6'],
              ];
              $sum = array_sum(array_column($donutData, 'value')) ?: 1;
              $cx = 80; $cy = 80; $r = 55; $circ = 2 * M_PI * $r;
              $offset = 0;
              foreach ($donutData as $seg):
                  $pct  = $seg['value'] / $sum;
                  $dash = $circ * $pct;
                  $gap  = $circ - $dash;
              ?>
              <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none"
                stroke="<?= $seg['color'] ?>" stroke-width="28"
                stroke-dasharray="<?= $dash ?> <?= $gap ?>"
                stroke-dashoffset="-<?= $offset ?>"
                transform="rotate(-90 <?= $cx ?> <?= $cy ?>)"/>
              <?php $offset += $dash; endforeach; ?>
              <!-- Hole -->
              <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="40" fill="var(--surface)"/>
            </svg>
            <div class="donut-legend">
              <?php foreach ($donutData as $seg): ?>
              <div class="legend-item">
                <div class="legend-dot" style="background:<?= $seg['color'] ?>"></div>
                <?= $seg['label'] ?> (<?= $seg['value'] ?>)
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Bar: weekly activity -->
        <div class="card chart-card">
          <div class="chart-title">Weekly activity</div>
          <div class="chart-subtitle">Mini-tasks completed this week</div>
          <svg width="100%" height="160" viewBox="0 0 300 150" preserveAspectRatio="xMidYMid meet" style="overflow:visible">
            <?php
            $barW  = 22;
            $barGap = (300 - count($weekDays) * $barW) / (count($weekDays) + 1);
            $maxH  = 110;
            foreach ($weekDays as $i => $day):
                $x   = $barGap + $i * ($barW + $barGap);
                $barH = $maxWeekly > 0 ? ($day['count'] / $maxWeekly) * $maxH : 0;
                $y   = $maxH - $barH + 10;
            ?>
            <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barW ?>" height="<?= max(3, $barH) ?>"
                  rx="5" fill="<?= $day['count'] > 0 ? '#1abc9c' : '#e2e8e6' ?>"/>
            <text x="<?= $x + $barW/2 ?>" y="138" text-anchor="middle" font-size="10" fill="var(--text-muted)"><?= $day['label'] ?></text>
            <?php if ($day['count'] > 0): ?>
            <text x="<?= $x + $barW/2 ?>" y="<?= $y - 4 ?>" text-anchor="middle" font-size="9" fill="var(--text-primary)" font-weight="600"><?= $day['count'] ?></text>
            <?php endif; ?>
            <?php endforeach; ?>
            <!-- Y axis lines -->
            <?php for ($i = 0; $i <= 4; $i++): $ly = 10 + ($i * $maxH / 4); ?>
            <line x1="0" y1="<?= $ly ?>" x2="300" y2="<?= $ly ?>" stroke="var(--border)" stroke-width=".5"/>
            <?php endfor; ?>
          </svg>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/notify.js"></script>
</body>
</html>
