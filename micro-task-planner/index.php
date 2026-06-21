<?php
// index.php — Entry point: landing page + auth routing

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// If already logged in, go to dashboard
if (!empty($_SESSION['user_id'])) {
    redirect('pages/dashboard.php');
}

$page = $_GET['page'] ?? 'landing'; // landing | signin | signup

/* ──────────────────────────────────
   POST: Handle sign-in / sign-up
────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $db     = getDB();

    if ($action === 'signup') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$name || !$email || !$password) {
            setFlash('error', 'All fields are required.');
            redirect('index.php?page=signup');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Invalid email address.');
            redirect('index.php?page=signup');
        }
        if (strlen($password) < 6) {
            setFlash('error', 'Password must be at least 6 characters.');
            redirect('index.php?page=signup');
        }

        // Check duplicate
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            setFlash('error', 'An account with that email already exists.');
            redirect('index.php?page=signup');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)")
           ->execute([$name, $email, $hash]);

        setFlash('success', 'Account created! Please sign in.');
        redirect('index.php?page=signin');
    }

    if ($action === 'signin') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            setFlash('error', 'Incorrect email or password.');
            redirect('index.php?page=signin');
        }

        loginUser($user);
        redirect('pages/dashboard.php');
    }
}

$flash = flashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Micro Task Planner</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/dark-mode.css">
</head>
<body>

<?php if ($page === 'landing'): ?>
<!-- ════════════════════════════════════════
     LANDING PAGE
════════════════════════════════════════ -->
<div class="landing">
  <nav class="landing-nav">
    <div class="nav-logo">
      <div class="logo-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
        </svg>
      </div>
      <span>Micro Task Planner</span>
    </div>
    <a href="index.php?page=signin" class="btn btn-primary">Open app &rarr;</a>
  </nav>

  <section class="hero">
    <div class="hero-left">
      <div class="hero-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/>
        </svg>
        Beat procrastination, one step at a time
      </div>
      <h1>Big goals feel easy when broken into <span class="accent">tiny wins.</span></h1>
      <p>Micro Task Planner turns overwhelming projects into a checklist of bite-sized steps — with smart recommendations, progress rings, and a dashboard that keeps you moving.</p>
      <div class="hero-ctas">
        <a href="index.php?page=signup" class="btn btn-primary btn-lg">Start planning &rarr;</a>
        <a href="index.php?page=signin" class="btn btn-outline btn-lg">See analytics</a>
      </div>
      <div class="hero-trust">
        <div class="trust-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="9 12 12 15 16 10"/></svg>
          Free account
        </div>
        <div class="trust-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="9 12 12 15 16 10"/></svg>
          Sync across sessions
        </div>
      </div>
    </div>

    <!-- Preview Card -->
    <div class="hero-preview">
      <div class="preview-date">Today</div>
      <div class="preview-title">
        Launch portfolio
        <span style="font-size:1.1rem;font-weight:800;color:var(--teal)">75%</span>
      </div>
      <div class="preview-steps">
        <div class="preview-step done">
          <div class="chk">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          Pick a domain name
        </div>
        <div class="preview-step done">
          <div class="chk">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          Design the hero section
        </div>
        <div class="preview-step done">
          <div class="chk">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          Write project case studies
        </div>
        <div class="preview-step">
          <div class="chk"></div>
          Deploy to production
        </div>
      </div>
      <div class="preview-next">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/></svg>
        Suggested next: <strong>Set up custom domain</strong>
      </div>
    </div>
  </section>
</div>

<?php elseif ($page === 'signup' || $page === 'signin'): ?>
<!-- ════════════════════════════════════════
     AUTH PAGE (Sign In / Sign Up)
════════════════════════════════════════ -->
<div class="auth-page">
  <div class="auth-logo">
    <div class="logo-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
        <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
      </svg>
    </div>
    <span>Micro Task Planner</span>
  </div>

  <div class="auth-card">
    <h1 class="auth-title">Welcome</h1>
    <p class="auth-subtitle">Sign in to view your progress and add new tasks.</p>

    <?php if ($flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Tab toggle -->
    <div class="auth-tabs">
      <button class="auth-tab <?= $page === 'signin' ? 'active' : '' ?>"
              onclick="location.href='index.php?page=signin'">Sign in</button>
      <button class="auth-tab <?= $page === 'signup' ? 'active' : '' ?>"
              onclick="location.href='index.php?page=signup'">Sign up</button>
    </div>

    <?php if ($page === 'signup'): ?>
    <!-- SIGN UP FORM -->
    <form method="POST" action="index.php?page=signup">
      <input type="hidden" name="action" value="signup">
      <div class="form-group">
        <label for="name">Full name</label>
        <input id="name" name="name" type="text" class="input" placeholder="Your name" required autocomplete="name">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" class="input" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="input" placeholder="········" required autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;border-radius:8px;padding:.7rem">
        Create account &rarr;
      </button>
    </form>

    <?php else: ?>
    <!-- SIGN IN FORM -->
    <form method="POST" action="index.php?page=signin">
      <input type="hidden" name="action" value="signin">
      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" class="input" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" class="input" placeholder="········" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;border-radius:8px;padding:.7rem">
        Sign in &rarr;
      </button>
    </form>
    <?php endif; ?>

    <div class="auth-divider">or</div>
    <button class="btn-google" type="button">
      <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      Continue with Google
    </button>
  </div>
</div>
<?php endif; ?>

<script src="assets/js/notify.js"></script>
</body>
</html>
