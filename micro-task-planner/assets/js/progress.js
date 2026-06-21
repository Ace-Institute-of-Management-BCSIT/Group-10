// assets/js/progress.js — Animated SVG progress rings

/**
 * Render an SVG circular progress ring.
 * @param {number} percent  0-100
 * @param {number} size     diameter in px (default 52)
 * @returns {string}  SVG HTML string
 */
function progressRingSVG(percent, size = 52) {
  const r     = (size / 2) - 5;
  const circ  = 2 * Math.PI * r;
  const dash  = circ * (percent / 100);
  const gap   = circ - dash;
  const cx    = size / 2;
  const cy    = size / 2;
  const color = percent >= 100 ? '#1abc9c' : percent > 0 ? '#1abc9c' : '#e2e8e6';

  return `
    <svg class="progress-ring" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
      <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#e2e8e6" stroke-width="4"/>
      <circle cx="${cx}" cy="${cy}" r="${r}" fill="none"
        stroke="${color}" stroke-width="4"
        stroke-dasharray="${dash} ${gap}"
        stroke-linecap="round"
        transform="rotate(-90 ${cx} ${cy})"
        style="transition: stroke-dasharray .4s ease"/>
      <text x="${cx}" y="${cy}" text-anchor="middle" dominant-baseline="central"
        font-size="${size < 50 ? 9 : 11}" font-weight="700" fill="var(--text-primary)" class="progress-ring-label">
        ${percent}%
      </text>
    </svg>
  `;
}

/**
 * Update all progress rings on the page.
 * Looks for elements with data-progress attribute.
 */
function initProgressRings() {
  document.querySelectorAll('[data-progress]').forEach(el => {
    const pct = parseInt(el.dataset.progress, 10) || 0;
    el.innerHTML = progressRingSVG(pct, parseInt(el.dataset.size || 52));
  });
}

document.addEventListener('DOMContentLoaded', initProgressRings);
