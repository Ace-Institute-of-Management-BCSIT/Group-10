<?php
// pages/dashboard.php — Main task dashboard

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

$db     = getDB();
$userId = currentUserId();

/* ── Handle new task form submission ─────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_task') {
    $title      = trim($_POST['title'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $priority   = in_array($_POST['priority'] ?? '', ['low','medium','high']) ? $_POST['priority'] : 'medium';
    $dueDate    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $miniTasks  = $_POST['mini_tasks'] ?? [];

    if ($title) {
        $stmt = $db->prepare("INSERT INTO tasks (user_id, title, description, priority, due_date, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $title, $desc, $priority, $dueDate]);
        $taskId = $db->lastInsertId();

        foreach ($miniTasks as $mt) {
            $mt = trim($mt);
            if ($mt) {
                $db->prepare("INSERT INTO mini_tasks (task_id, title) VALUES (?, ?)")->execute([$taskId, $mt]);
            }
        }
        setFlash('success', 'Task created successfully!');
    }
    redirect('dashboard.php');
}

/* ── Fetch stats ─────────────────────────────────────── */
$statsQ = $db->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending')     AS pending,
        SUM(status = 'in_progress') AS in_progress,
        SUM(status = 'completed')   AS completed
    FROM tasks WHERE user_id = ?
");
$statsQ->execute([$userId]);
$stats = $statsQ->fetch();

/* ── Fetch all tasks with mini-task counts ───────────── */
$tasksQ = $db->prepare("
    SELECT t.*,
           COUNT(mt.id)                          AS total_steps,
           SUM(mt.is_completed)                  AS done_steps
    FROM tasks t
    LEFT JOIN mini_tasks mt ON mt.task_id = t.id
    WHERE t.user_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$tasksQ->execute([$userId]);
$tasks = $tasksQ->fetchAll();

/* ── Fetch mini-tasks for all task ids ───────────────── */
$taskIds = array_column($tasks, 'id');
$miniMap = [];
if ($taskIds) {
    $in      = implode(',', array_fill(0, count($taskIds), '?'));
    $miniQ   = $db->prepare("SELECT * FROM mini_tasks WHERE task_id IN ($in) ORDER BY id");
    $miniQ->execute($taskIds);
    foreach ($miniQ->fetchAll() as $m) {
        $miniMap[$m['task_id']][] = $m;
    }
}

$flash = flashMessage();
$userName = $_SESSION['user_name'] ?? 'there';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Micro Task Planner</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body>
<div class="app-shell">

  <!-- ── Sidebar ───────────────────────────────────── -->
  <?php include '../includes/sidebar.php'; ?>

  <!-- ── Main ──────────────────────────────────────── -->
  <div class="main-content">
    <!-- Topbar -->
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
      <!-- Header row -->
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
        <div>
          <h1>Dashboard</h1>
          <p style="color:var(--text-muted);font-size:.9rem;margin-top:.2rem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2" style="vertical-align:middle;margin-right:.3rem">
              <path d="M12 2L2 7l10 5 10-5-10-5z"/>
            </svg>
            Done is better than perfect.
          </p>
        </div>
        <button class="btn btn-primary" id="openNewTask">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New task
        </button>
      </div>

      <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>

      <!-- Stat cards -->
      <div class="stat-grid">
        <?php
        $statItems = [
          ['ALL TASKS',   $stats['total'] ?? 0,       'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2'],
          ['PENDING',     $stats['pending'] ?? 0,     'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zM12 6v6l4 2'],
          ['IN PROGRESS', $stats['in_progress'] ?? 0, 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'],
          ['COMPLETED',   $stats['completed'] ?? 0,   'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4 12 14.01l-3-3'],
        ];
        foreach ($statItems as [$label, $value, $path]): ?>
        <div class="card stat-card">
          <div>
            <div class="stat-label"><?= $label ?></div>
            <div class="stat-value"><?= $value ?></div>
          </div>
          <div class="stat-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="<?= $path ?>"/>
            </svg>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Filter bar -->
      <div class="filter-bar">
        <div class="filter-tabs">
          <button class="filter-tab active" data-filter="all">All</button>
          <button class="filter-tab" data-filter="pending">Pending</button>
          <button class="filter-tab" data-filter="in_progress">In progress</button>
          <button class="filter-tab" data-filter="completed">Completed</button>
        </div>
        <div class="search-box">
          <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input id="taskSearch" type="text" class="input" placeholder="Search tasks or steps..." style="padding-left:2.2rem;width:260px">
        </div>
      </div>

      <!-- Task list -->
      <div class="task-list">
        <?php if (empty($tasks)): ?>
          <div class="card" style="text-align:center;padding:3rem;color:var(--text-muted)">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5" style="margin:0 auto 1rem">
              <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2"/>
            </svg>
            <p style="font-weight:600;margin-bottom:.5rem">No tasks yet</p>
            <p style="font-size:.88rem">Click "+ New task" to create your first task.</p>
          </div>
        <?php endif; ?>

        <?php foreach ($tasks as $task):
          $minis   = $miniMap[$task['id']] ?? [];
          $total   = count($minis);
          $done    = array_sum(array_column($minis, 'is_completed'));
          $percent = taskProgress($done, $total);
        ?>
        <div class="task-item" data-status="<?= e($task['status']) ?>">
          <div class="task-header task-toggle">
            <!-- Progress ring -->
            <div data-progress="<?= $percent ?>" data-size="52" data-task-ring="<?= $task['id'] ?>"></div>

            <div class="task-info">
              <div class="task-title"><?= e($task['title']) ?></div>
              <?php if ($task['description']): ?>
                <div class="task-desc"><?= e(mb_strimwidth($task['description'], 0, 80, '…')) ?></div>
              <?php endif; ?>
              <div class="task-meta">
                <span class="badge <?= priorityBadge($task['priority']) ?>"><?= e($task['priority']) ?></span>
                <?php if ($task['due_date']): ?>
                  <span class="task-date">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <?= formatDate($task['due_date']) ?>
                  </span>
                <?php endif; ?>
                <span style="font-size:.78rem;color:var(--text-muted)"><?= $done ?> / <?= $total ?> steps</span>
              </div>
            </div>

            <div class="task-actions">
              <button class="icon-btn chevron">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </button>
              <button class="icon-btn delete-task-btn" data-id="<?= $task['id'] ?>" title="Delete task">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                  <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Expanded mini-task body -->
          <div class="task-body">
            <!-- Suggested chips (first 3 unfilled suggestions) -->
            <?php
            // Simple static suggestions based on common next steps
            $suggestions = getSuggestionsForTask($task['title']);
            $existingTitles = array_column($minis, 'title');
            $notAdded = array_filter($suggestions, fn($s) => !in_array($s, $existingTitles));
            $notAdded = array_slice(array_values($notAdded), 0, 3);
            if ($notAdded):
            ?>
            <div class="suggested-label">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
              </svg>
              Suggested:
              <?php foreach ($notAdded as $s): ?>
                <button class="suggest-chip inline-suggest-chip" data-task-id="<?= $task['id'] ?>" data-title="<?= e($s) ?>"><?= e($s) ?></button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Mini-tasks -->
            <div class="mini-tasks">
              <?php foreach ($minis as $mt): ?>
              <div class="mini-task <?= $mt['is_completed'] ? 'done' : '' ?>">
                <input type="checkbox"
                       class="mini-task-check"
                       data-id="<?= $mt['id'] ?>"
                       data-task-id="<?= $task['id'] ?>"
                       <?= $mt['is_completed'] ? 'checked' : '' ?>>
                <label><?= e($mt['title']) ?></label>
              </div>
              <?php endforeach; ?>
            </div>

            <!-- Quick add step -->
            <div class="add-mini">
              <input type="text" class="input" placeholder="Add a mini-task…" id="stepInline_<?= $task['id'] ?>">
              <button class="btn btn-primary btn-sm" onclick="addInlineMini(<?= $task['id'] ?>)">Add</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     NEW TASK MODAL
══════════════════════════════════════ -->
<div class="modal-overlay" id="newTaskModal">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title">New task</div>
        <div class="modal-subtitle">Break a goal into bite-sized steps. We'll suggest mini-tasks based on your title.</div>
      </div>
      <button class="modal-close" id="closeModal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <form id="newTaskForm" method="POST" action="dashboard.php">
      <input type="hidden" name="action" value="create_task">

      <div class="form-group">
        <label for="taskTitle">Title</label>
        <input id="taskTitle" name="title" class="input" type="text" placeholder="e.g. Launch portfolio website" required>
      </div>

      <div class="form-group">
        <label for="taskDesc">Description</label>
        <textarea id="taskDesc" name="description" class="input" placeholder="Optional details about this goal…"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group" style="margin-bottom:0">
          <label for="taskPriority">Priority</label>
          <select id="taskPriority" name="priority" class="input">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label for="taskDue">Due date</label>
          <input id="taskDue" name="due_date" class="input" type="date">
        </div>
      </div>

      <!-- Suggestions area -->
      <div style="margin-top:1.25rem">
        <div class="modal-suggest-header">
          <span class="modal-suggest-label">Mini-tasks</span>
          <button type="button" class="add-all-link" id="addAllSuggestions">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2L2 7l10 5 10-5-10-5z"/>
            </svg>
            Add all suggestions
          </button>
        </div>
        <div class="modal-suggest-chips" id="suggestArea"></div>
        <div id="addedMiniTasks" style="display:flex;flex-direction:column;gap:.4rem;margin-bottom:.75rem"></div>

        <div class="add-mini">
          <input id="stepInput" type="text" class="input" placeholder="Add a step…">
          <button type="button" class="btn btn-outline btn-sm" id="addStepBtn">Add</button>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" id="cancelTask">Cancel</button>
        <button type="submit" class="btn btn-primary">Create task</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/progress.js"></script>
<script src="../assets/js/tasks.js"></script>
<script src="../assets/js/notify.js"></script>
<script>
// Inline add mini-task from expanded panel
async function addInlineMini(taskId) {
  const inp = document.getElementById('stepInline_' + taskId);
  const val = inp.value.trim();
  if (!val) return;
  const res = await fetch('../includes/add_mini.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ task_id: taskId, title: val })
  });
  if (res.ok) { notify('Step added!'); location.reload(); }
  else notify('Could not add step.', 'error');
}
</script>
</body>
</html>
<?php
/**
 * Quick in-PHP suggestion generator (fallback before AI endpoint is wired).
 * Returns an array of suggested mini-task titles based on keywords in the task title.
 */
function getSuggestionsForTask(string $title): array {
    $t = strtolower($title);
    if (str_contains($t, 'study') || str_contains($t, 'learn'))
        return ['Open textbook or course material','Skim the chapter to get an overview','Read carefully and take notes','Solve practice questions','Review mistakes and summarize key points'];
    if (str_contains($t, 'portfolio') || str_contains($t, 'website') || str_contains($t, 'web'))
        return ['Pick a domain name','Design the hero section','Write project case studies','Deploy to production','Set up custom domain'];
    if (str_contains($t, 'exercise') || str_contains($t, 'workout') || str_contains($t, 'gym'))
        return ['Warm up for 5 minutes','Complete main workout','Cool down and stretch','Log workout stats','Drink water and recover'];
    if (str_contains($t, 'read') || str_contains($t, 'book'))
        return ['Find a quiet reading spot','Set a reading timer','Read assigned pages','Highlight key passages','Write a short summary'];
    return ['Define the goal clearly','Break into smaller steps','Set a deadline','Start with the first step','Review progress'];
}
?>
