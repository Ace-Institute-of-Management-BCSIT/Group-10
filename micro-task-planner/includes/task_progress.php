<?php
// includes/task_progress.php — AJAX: return task completion percent

require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

header('Content-Type: application/json');
requireAuth();

$taskId = (int)($_GET['task_id'] ?? 0);
$userId = currentUserId();
$db     = getDB();

$stmt = $db->prepare("
    SELECT COUNT(*) AS total, SUM(is_completed) AS done
    FROM mini_tasks mt
    JOIN tasks t ON t.id = mt.task_id
    WHERE mt.task_id = ? AND t.user_id = ?
");
$stmt->execute([$taskId, $userId]);
$row = $stmt->fetch();

$total   = (int)$row['total'];
$done    = (int)$row['done'];
$percent = $total > 0 ? (int)round($done / $total * 100) : 0;

echo json_encode(['percent' => $percent, 'done' => $done, 'total' => $total]);
