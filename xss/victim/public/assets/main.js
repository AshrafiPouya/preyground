'use strict';

const TOTAL_LABS = 20;
const LAB_IDS    = Array.from({length:20}, (_,i) => `lab${i+1}`);
const NS         = 'huntlabs_xss_';

function lsGet(key) { try { return JSON.parse(localStorage.getItem(NS + key)); } catch { return null; } }
function lsSet(key, val) { try { localStorage.setItem(NS + key, JSON.stringify(val)); } catch {} }

function isSolved(labId) { return !!(lsGet('solved') || {})[labId]; }
function markSolved(labId) { const s = lsGet('solved') || {}; s[labId] = true; lsSet('solved', s); updateNavCounter(); }
function solvedCount() { const s = lsGet('solved') || {}; return LAB_IDS.filter(id => s[id]).length; }

function updateNavCounter() {
  const el = document.getElementById('solved-counter');
  if (!el) return;
  const count = solvedCount();
  el.innerHTML = `<span class="solved-num">${count}</span> / ${TOTAL_LABS} solved`;
}

function checkSolvedBanner(labId) {
  if (!labId) return;
  const banner = document.getElementById('solved-banner');
  if (banner && isSolved(labId)) banner.classList.add('visible');
}

function hintsRevealed(labId) { return lsGet(`hints_${labId}`) || []; }
function revealHint(labId, n) {
  const r = hintsRevealed(labId);
  if (!r.includes(n)) { r.push(n); lsSet(`hints_${labId}`, r); }
  showHintContent(n);
}
function showHintContent(n) {
  const item = document.querySelector(`.hint-item[data-hint="${n}"]`);
  if (!item) return;
  const content = item.querySelector('.hint-content');
  if (content) content.classList.add('revealed');
  const btn = item.querySelector('.hint-reveal-btn');
  if (btn) btn.textContent = 'Revealed';
}
function restoreHints(labId) { hintsRevealed(labId).forEach(n => showHintContent(n)); }
function initHints(labId) {
  document.querySelectorAll('.hint-item').forEach(item => {
    const n   = parseInt(item.dataset.hint, 10);
    const btn = item.querySelector('.hint-reveal-btn');
    if (btn) btn.addEventListener('click', e => { e.stopPropagation(); revealHint(labId, n); });
  });
}

function logPayload(labId, payload, context) {
  const key = `log_${labId}`; const log = lsGet(key) || [];
  log.unshift({ payload, context, ts: new Date().toISOString() });
  if (log.length > 20) log.length = 20;
  lsSet(key, log);
}
function renderPayloadLog(labId) {
  const el = document.getElementById('payload-log');
  if (!el) return;
  const log = lsGet(`log_${labId}`) || [];
  if (!log.length) { el.innerHTML = '<div class="log-empty">No payloads logged yet.</div>'; return; }
  el.innerHTML = log.map(e => `<div class="log-entry" title="Click to copy">
    <div class="log-payload">${escHtml(e.payload)}</div>
    <div class="log-context">${escHtml(e.context||'')} &mdash; ${new Date(e.ts).toLocaleTimeString()}</div>
  </div>`).join('');
  el.querySelectorAll('.log-entry').forEach((el2, i) => {
    el2.addEventListener('click', () => { copyToClipboard(log[i].payload); flashCopied(el2); });
  });
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function copyToClipboard(text) {
  if (navigator.clipboard) navigator.clipboard.writeText(text).catch(()=>{});
  else { const t=document.createElement('textarea'); t.value=text; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); }
}
function flashCopied(el) {
  el.style.borderColor='var(--green)';
  setTimeout(()=>{ el.style.borderColor=''; }, 1200);
}

function submitFlag(labId, expectedFlag, inputEl, alertEl) {
  const val = (inputEl.value||'').trim();
  if (!val) return;
  if (val === expectedFlag) {
    markSolved(labId);
    showAlert(alertEl, 'success', '&#10003; Correct! Lab solved.');
    const banner = document.getElementById('solved-banner');
    if (banner) banner.classList.add('visible');
    inputEl.disabled = true;
    const btn = document.querySelector('#flag-form .btn-primary');
    if (btn) btn.disabled = true;
  } else {
    showAlert(alertEl, 'error', '&#10007; Incorrect flag. Keep trying.');
  }
}

function initFlagForm(labId, expectedFlag) {
  const form = document.getElementById('flag-form');
  const inputEl = document.getElementById('flag-input');
  const alertEl = document.getElementById('flag-alert');
  if (!form || !inputEl) return;
  if (isSolved(labId)) {
    inputEl.value = expectedFlag; inputEl.disabled = true;
    const btn = form.querySelector('.btn-primary'); if (btn) btn.disabled = true;
    if (alertEl) showAlert(alertEl, 'success', '&#10003; Already solved!');
  }
  form.addEventListener('submit', e => { e.preventDefault(); submitFlag(labId, expectedFlag, inputEl, alertEl); });
}

function showAlert(el, type, msg) {
  if (!el) return;
  el.className = `alert alert-${type} visible`;
  el.innerHTML = msg;
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('visible'), 5000);
}

document.addEventListener('DOMContentLoaded', () => {
  const match = location.pathname.match(/\/lab(\d+)/);
  const labId = match ? `lab${match[1]}` : null;
  updateNavCounter();
  if (labId) { restoreHints(labId); renderPayloadLog(labId); checkSolvedBanner(labId); initHints(labId); }
  if (location.pathname.match(/\/(index\.html|)$/)) {
    LAB_IDS.forEach(id => {
      const card = document.querySelector(`.lab-card[data-lab="${id}"]`);
      if (card && isSolved(id)) card.classList.add('is-solved');
    });
    const heroSolved = document.getElementById('hero-solved');
    if (heroSolved) heroSolved.textContent = solvedCount();
  }
});

window.HuntLabs = {
  isSolved, markSolved, solvedCount, updateNavCounter, checkSolvedBanner,
  hintsRevealed, revealHint, restoreHints, initHints,
  logPayload, renderPayloadLog,
  submitFlag, initFlagForm,
  showAlert, escHtml, copyToClipboard
};
