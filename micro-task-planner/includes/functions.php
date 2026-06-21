<?php
// includes/functions.php — Reusable helper functions

/**
 * Sanitize a string for safe HTML output.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Return a flash message stored in session and clear it.
 */
function flashMessage(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Set a flash message in the session.
 * @param string $type  'success' | 'error'
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Format a date to a readable string.
 */
function formatDate(string $date): string {
    return date('d M Y', strtotime($date));
}

/**
 * Calculate task completion percentage.
 */
function taskProgress(int $completed, int $total): int {
    if ($total === 0) return 0;
    return (int)round(($completed / $total) * 100);
}

/**
 * Return a CSS badge class based on priority.
 */
function priorityBadge(string $priority): string {
    return match($priority) {
        'high'   => 'badge-high',
        'medium' => 'badge-medium',
        'low'    => 'badge-low',
        default  => 'badge-medium',
    };
}

/**
 * Update user streak — call whenever a mini-task is completed.
 */
function updateStreak(int $userId): void {
    $db = getDB();
    $today = date('Y-m-d');

    $stmt = $db->prepare("SELECT * FROM user_streaks WHERE user_id = ?");
    $stmt->execute([$userId]);
    $streak = $stmt->fetch();

    if (!$streak) {
        $db->prepare("INSERT INTO user_streaks (user_id, current_streak, last_active_date) VALUES (?, 1, ?)")
           ->execute([$userId, $today]);
        return;
    }

    $lastDate = $streak['last_active_date'];
    $diff = (strtotime($today) - strtotime($lastDate)) / 86400;

    if ($diff === 0) return; // already active today
    if ($diff === 1) {
        // consecutive day
        $db->prepare("UPDATE user_streaks SET current_streak = current_streak + 1, last_active_date = ? WHERE user_id = ?")
           ->execute([$today, $userId]);
    } else {
        // streak broken
        $db->prepare("UPDATE user_streaks SET current_streak = 1, last_active_date = ? WHERE user_id = ?")
           ->execute([$today, $userId]);
    }
}
