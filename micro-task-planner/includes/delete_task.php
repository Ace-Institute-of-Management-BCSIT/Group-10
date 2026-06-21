<?php
// includes/delete_task.php — AJAX: delete a task and its mini-tasks

require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');
requireAuth();

$data   = json_decode(file_get_contents('php://input'), true);
$taskId = (int)($data['task_id'] ?? 0);
$userId = currentUserId();
$db     = getDB();

// Verify ownership
$stmt = $db->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$taskId, $userId]);
if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['ok' => false]); exit; }

// mini_tasks deleted via ON DELETE CASCADE
$db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$taskId]);

echo json_encode(['ok' => true]);
