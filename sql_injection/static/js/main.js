// Copy-to-clipboard for payload entries
document.addEventListener('click', (e) => {
  if (e.target.closest('.log-entry')) {
    const entry = e.target.closest('.log-entry');
    const payload = entry.querySelector('.log-payload');
    if (payload) {
      navigator.clipboard.writeText(payload.textContent.trim()).then(() => {
        const orig = payload.style.color;
        payload.style.color = '#3fb950';
        setTimeout(() => { payload.style.color = orig; }, 500);
      }).catch(() => {});
    }
  }
});

// Timing bar animation
document.querySelectorAll('[data-elapsed]').forEach(el => {
  const ms = parseInt(el.dataset.elapsed, 10);
  const bar = el.querySelector('.timing-fill');
  if (bar) {
    const pct = Math.min((ms / 10000) * 100, 100);
    bar.style.width = Math.max(pct, 1) + '%';
    if (ms > 2000) bar.style.background = '#f85149';
    else if (ms > 500) bar.style.background = '#d29922';
  }
});

// Auto-dismiss flash messages
document.querySelectorAll('.alert[data-dismiss]').forEach(el => {
  setTimeout(() => el.remove(), parseInt(el.dataset.dismiss, 10));
});

// Smooth scroll to results when they appear
const results = document.querySelector('#results');
if (results && results.children.length > 0) {
  results.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
