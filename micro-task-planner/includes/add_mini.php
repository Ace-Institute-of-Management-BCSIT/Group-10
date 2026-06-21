<?php
// includes/add_mini.php — AJAX: add a new mini-task to a task

require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';

header('Content-Type: application/json');
requireAuth();

$data   = json_decode(file_get_contents('php://input'), true);
$taskId = (int)($data['task_id'] ?? 0);
$title  = trim($data['title'] ?? '');
$userId = currentUserId();

if (!$taskId || !$title) { http_response_code(400); echo json_encode(['ok' => false]); exit; }

// Verify task belongs to user
$db   = getDB();
$stmt = $db->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$taskId, $userId]);
if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['ok' => false]); exit; }

$db->prepare("INSERT INTO mini_tasks (task_id, title) VALUES (?, ?)")->execute([$taskId, $title]);
$newId = $db->lastInsertId();

// Auto-update task status to in_progress if it was pending
$db->prepare("UPDATE tasks SET status = 'in_progress' WHERE id = ? AND status = 'pending'")->execute([$taskId]);

echo json_encode(['ok' => true, 'id' => $newId]);
