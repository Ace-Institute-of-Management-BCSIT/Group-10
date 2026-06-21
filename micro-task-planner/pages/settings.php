<?php
// pages/settings.php — User account settings

require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

$db     = getDB();
$userId = currentUserId();

// Fetch current user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

/* ── Handle profile update ───────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if (!$name || !$email) { setFlash('error', 'Name and email are required.'); redirect('settings.php'); }
        // Check email uniqueness
        $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $userId]);
        if ($check->fetch()) { setFlash('error', 'Email already in use by another account.'); redirect('settings.php'); }
        $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?")->execute([$name, $email, $userId]);
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        setFlash('success', 'Profile updated.');
        redirect('settings.php');
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $user['password'])) { setFlash('error', 'Current password is incorrect.'); redirect('settings.php'); }
        if (strlen($new) < 6) { setFlash('error', 'New password must be at least 6 characters.'); redirect('settings.php'); }
        if ($new !== $confirm) { setFlash('error', 'Passwords do not match.'); redirect('settings.php'); }
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
        setFlash('success', 'Password changed successfully.');
        redirect('settings.php');
    }
}

$flash = flashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings — Micro Task Planner</title>
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

    <div class="page-body" style="max-width:620px">
      <h1 style="margin-bottom:1.75rem">Settings</h1>

      <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>

      <!-- Profile -->
      <div class="card" style="margin-bottom:1.25rem">
        <h2 style="margin-bottom:1.25rem">Profile</h2>
        <form method="POST" action="settings.php">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-group">
            <label for="name">Full name</label>
            <input id="name" name="name" class="input" type="text" value="<?= e($user['name']) ?>" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input id="email" name="email" class="input" type="email" value="<?= e($user['email']) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
      </div>

      <!-- Password -->
      <div class="card">
        <h2 style="margin-bottom:1.25rem">Change password</h2>
        <form method="POST" action="settings.php">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label for="current_password">Current password</label>
            <input id="current_password" name="current_password" class="input" type="password" placeholder="········" required>
          </div>
          <div class="form-group">
            <label for="new_password">New password</label>
            <input id="new_password" name="new_password" class="input" type="password" placeholder="min 6 characters" required>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm new password</label>
            <input id="confirm_password" name="confirm_password" class="input" type="password" placeholder="········" required>
          </div>
          <button type="submit" class="btn btn-primary">Update password</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="../assets/js/notify.js"></script>
</body>
</html>
