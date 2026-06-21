// assets/js/tasks.js — Dashboard task interactions

document.addEventListener('DOMContentLoaded', () => {

  /* ─── New Task Modal ──────────────────────────────── */
  const modalOverlay = document.getElementById('newTaskModal');
  const openBtn      = document.getElementById('openNewTask');
  const closeBtn     = document.getElementById('closeModal');
  const cancelBtn    = document.getElementById('cancelTask');
  const titleInput   = document.getElementById('taskTitle');
  const suggestArea  = document.getElementById('suggestArea');
  const addedList    = document.getElementById('addedMiniTasks');
  const stepInput    = document.getElementById('stepInput');
  const addStepBtn   = document.getElementById('addStepBtn');
  const addAllBtn    = document.getElementById('addAllSuggestions');

  let currentSuggestions = [];

  function openModal() {
    modalOverlay.classList.add('open');
    titleInput.focus();
  }
  function closeModal() {
    modalOverlay.classList.remove('open');
    document.getElementById('newTaskForm').reset();
    addedList.innerHTML = '';
    suggestArea.innerHTML = '';
    currentSuggestions = [];
  }

  openBtn?.addEventListener('click', openModal);
  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  modalOverlay?.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });

  /* ─── AI Suggestions on title input ──────────────── */
  let debounceTimer;
  titleInput?.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const val = titleInput.value.trim();
    if (val.length < 3) { suggestArea.innerHTML = ''; currentSuggestions = []; return; }
    debounceTimer = setTimeout(() => fetchSuggestions(val), 500);
  });

  async function fetchSuggestions(title) {
    suggestArea.innerHTML = '<span style="font-size:.8rem;color:var(--text-muted)">Generating suggestions…</span>';
    try {
      const res  = await fetch('../includes/suggestions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title })
      });
      const data = await res.json();
      currentSuggestions = data.suggestions || [];
      renderSuggestions();
    } catch {
      suggestArea.innerHTML = '';
    }
  }

  function renderSuggestions() {
    suggestArea.innerHTML = currentSuggestions
      .map(s => `<button type="button" class="suggest-chip" data-title="${escHtml(s)}">${escHtml(s)}</button>`)
      .join('');
    suggestArea.querySelectorAll('.suggest-chip').forEach(chip => {
      chip.addEventListener('click', () => addMiniTask(chip.dataset.title));
    });
  }

  addAllBtn?.addEventListener('click', () => {
    currentSuggestions.forEach(s => addMiniTask(s));
  });

  /* ─── Add mini-task manually ──────────────────────── */
  addStepBtn?.addEventListener('click', () => {
    const val = stepInput.value.trim();
    if (!val) return;
    addMiniTask(val);
    stepInput.value = '';
    stepInput.focus();
  });

  stepInput?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); addStepBtn.click(); }
  });

  function addMiniTask(title) {
    if (!title) return;
    const id  = 'mt_' + Date.now() + Math.random().toString(36).slice(2,5);
    const row = document.createElement('div');
    row.className = 'mini-task';
    row.innerHTML = `
      <input type="checkbox" disabled style="pointer-events:none">
      <label>${escHtml(title)}</label>
      <input type="hidden" name="mini_tasks[]" value="${escHtml(title)}">
      <button type="button" class="icon-btn mini-task-del" title="Remove">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    `;
    row.querySelector('.mini-task-del').addEventListener('click', () => row.remove());
    addedList.appendChild(row);
  }

  /* ─── Inline mini-task checkbox toggle (dashboard) ── */
  document.querySelectorAll('.mini-task-check').forEach(cb => {
    cb.addEventListener('change', async function () {
      const miniId   = this.dataset.id;
      const taskId   = this.dataset.taskId;
      const completed = this.checked ? 1 : 0;
      const row = this.closest('.mini-task');

      row.classList.toggle('done', this.checked);

      await fetch('../includes/toggle_mini.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mini_id: miniId, completed })
      });

      // Refresh progress ring for this task
      refreshTaskProgress(taskId);
    });
  });

  async function refreshTaskProgress(taskId) {
    const res  = await fetch(`../includes/task_progress.php?task_id=${taskId}`);
    const data = await res.json();
    const ring = document.querySelector(`[data-task-ring="${taskId}"]`);
    if (ring) {
      ring.dataset.progress = data.percent;
      ring.innerHTML = progressRingSVG(data.percent, 52);
    }
  }

  /* ─── Expand / collapse task body ────────────────── */
  document.querySelectorAll('.task-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const body = btn.closest('.task-item').querySelector('.task-body');
      body.classList.toggle('open');
      btn.querySelector('.chevron')?.classList.toggle('rotated');
    });
  });

  /* ─── Filter tabs ─────────────────────────────────── */
  document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      const filter = this.dataset.filter;
      document.querySelectorAll('.task-item').forEach(item => {
        item.style.display = (filter === 'all' || item.dataset.status === filter) ? '' : 'none';
      });
    });
  });

  /* ─── Search ──────────────────────────────────────── */
  document.getElementById('taskSearch')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.task-item').forEach(item => {
      const text = item.querySelector('.task-title')?.textContent.toLowerCase() || '';
      item.style.display = text.includes(q) ? '' : 'none';
    });
  });

  /* ─── Suggest chips on dashboard task body ─────────── */
  document.querySelectorAll('.inline-suggest-chip').forEach(chip => {
    chip.addEventListener('click', async function () {
      const taskId = this.dataset.taskId;
      const title  = this.dataset.title;
      const res    = await fetch('../includes/add_mini.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: taskId, title })
      });
      if (res.ok) location.reload();
    });
  });

  /* ─── Delete task ─────────────────────────────────── */
  document.querySelectorAll('.delete-task-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
      if (!confirm('Delete this task and all its steps?')) return;
      const id = this.dataset.id;
      await fetch('../includes/delete_task.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: id })
      });
      this.closest('.task-item').remove();
    });
  });

  /* ─── Utility ─────────────────────────────────────── */
  function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
});
