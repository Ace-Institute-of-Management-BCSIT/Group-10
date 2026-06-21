<?php
// includes/toggle_mini.php — AJAX: toggle mini-task completion

require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

header('Content-Type: application/json');
requireAuth();

$data      = json_decode(file_get_contents('php://input'), true);
$miniId    = (int)($data['mini_id'] ?? 0);
$completed = (int)($data['completed'] ?? 0);
$userId    = currentUserId();

if (!$miniId) { echo json_encode(['ok' => false]); exit; }

// Verify ownership through task
$db   = getDB();
$stmt = $db->prepare("
    SELECT mt.id FROM mini_tasks mt
    JOIN tasks t ON t.id = mt.task_id
    WHERE mt.id = ? AND t.user_id = ?
");
$stmt->execute([$miniId, $userId]);
if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['ok' => false]); exit; }

$db->prepare("UPDATE mini_tasks SET is_completed = ? WHERE id = ?")->execute([$completed ? 1 : 0, $miniId]);

// Update parent task status
$taskQ = $db->prepare("SELECT task_id FROM mini_tasks WHERE id = ?");
$taskQ->execute([$miniId]);
$taskId = (int)$taskQ->fetchColumn();

if ($taskId) {
    $progQ = $db->prepare("SELECT COUNT(*) AS total, SUM(is_completed) AS done FROM mini_tasks WHERE task_id = ?");
    $progQ->execute([$taskId]);
    $prog   = $progQ->fetch();
    $total  = (int)$prog['total'];
    $done   = (int)$prog['done'];
    $status = $done === 0 ? 'pending' : ($done >= $total ? 'completed' : 'in_progress');
    $db->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$status, $taskId]);
}

if ($completed) updateStreak($userId);

echo json_encode(['ok' => true]);
