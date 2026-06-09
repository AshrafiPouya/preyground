'use strict';
const express = require('express');
const session = require('express-session');
const cookieParser = require('cookie-parser');
const http = require('http');
const WebSocket = require('ws');
const fs = require('fs');
const path = require('path');

const app = express();
const server = http.createServer(app);

// ── Flags ──────────────────────────────────────────────────────────────
const flagsDir = path.join(__dirname, 'flags');
const FLAGS = {
  x1: fs.readFileSync(path.join(flagsDir, 'lab_x1.txt'), 'utf8').trim(),
  x2: fs.readFileSync(path.join(flagsDir, 'lab_x2.txt'), 'utf8').trim(),
  x3: fs.readFileSync(path.join(flagsDir, 'lab_x3.txt'), 'utf8').trim(),
  x4: fs.readFileSync(path.join(flagsDir, 'lab_x4.txt'), 'utf8').trim(),
  x5: fs.readFileSync(path.join(flagsDir, 'lab_x5.txt'), 'utf8').trim(),
};

// ── Middleware ──────────────────────────────────────────────────────────
app.use(cookieParser());
app.use(express.urlencoded({ extended: false }));
app.use(express.json());
app.use(session({
  secret: 'hunt-labs-secret-xorigin',
  resave: false,
  saveUninitialized: false,
  cookie: { httpOnly: true, sameSite: 'none', secure: false },
  name: 'hunt_sess',
}));

// ── Auth helpers ────────────────────────────────────────────────────────
function seedSession(req) {
  req.session.user = 'victim_user';
  req.session.authenticated = true;
}

// Auto-seed a session so learners don't need to log in manually
app.use((req, res, next) => {
  if (!req.session.authenticated) seedSession(req);
  next();
});

// ── Static ──────────────────────────────────────────────────────────────
app.use(express.static(path.join(__dirname, 'public')));

// ── Lab X1 — CORS Origin Reflection ────────────────────────────────────
// VULNERABLE: reflects any Origin + credentials: true
app.get('/api/x1/account', (req, res) => {
  const origin = req.headers.origin;
  if (origin) {
    res.header('Access-Control-Allow-Origin', origin);          // reflects attacker origin
    res.header('Access-Control-Allow-Credentials', 'true');
  }
  res.json({ user: req.session.user || 'guest', flag: FLAGS.x1 });
});

app.options('/api/x1/account', (req, res) => {
  const origin = req.headers.origin;
  if (origin) {
    res.header('Access-Control-Allow-Origin', origin);
    res.header('Access-Control-Allow-Credentials', 'true');
    res.header('Access-Control-Allow-Methods', 'GET');
    res.header('Access-Control-Allow-Headers', 'Content-Type');
  }
  res.sendStatus(204);
});

// ── Lab X2 — CORS Null Origin / Weak Regex ─────────────────────────────
// VULNERABLE: trusts null + substring check (origin.includes('localhost:3000'))
// Bypass 1: sandboxed iframe → Origin: null
// Bypass 2: the check uses includes('localhost') not exact match, so localhost:3001 (attacker) could pass
// To make both bypasses work cleanly: trust null + check includes('trusted-corp')
// and document that a domain like evil-trusted-corp.attacker.io would also pass the regex
app.get('/api/x2/account', (req, res) => {
  const origin = req.headers.origin || '';
  // DELIBERATELY VULNERABLE: trusts null + weak substring check
  const allowed = origin === 'null' || origin.includes('localhost:3000');
  // Note: 'localhost:3000' substring check — demonstrate null bypass since attacker is :3001
  if (allowed) {
    res.header('Access-Control-Allow-Origin', origin);
    res.header('Access-Control-Allow-Credentials', 'true');
  }
  res.json({ user: req.session.user || 'guest', flag: FLAGS.x2 });
});

app.options('/api/x2/account', (req, res) => {
  const origin = req.headers.origin || '';
  const allowed = origin === 'null' || origin.includes('localhost:3000');
  if (allowed) {
    res.header('Access-Control-Allow-Origin', origin);
    res.header('Access-Control-Allow-Credentials', 'true');
    res.header('Access-Control-Allow-Methods', 'GET');
    res.header('Access-Control-Allow-Headers', 'Content-Type');
  }
  res.sendStatus(204);
});

// ── Lab X3 — postMessage missing origin check ───────────────────────────
// No server endpoint needed — vulnerability is entirely in victim's front-end JS (x3.html)

// ── Lab X4 — Reverse Tabnabbing ────────────────────────────────────────
// Victim renders user-supplied links without rel="noopener"
// POST /api/x4/submit-link stores the link; GET /x4.html renders it
let x4UserLink = '';

app.post('/api/x4/submit-link', express.urlencoded({ extended: false }), (req, res) => {
  x4UserLink = req.body.url || '';
  res.redirect('/x4.html');
});

app.get('/api/x4/link', (req, res) => {
  res.json({ url: x4UserLink });
});

// X4: phishing "credentials" captured endpoint — learner posts stolen token here
app.post('/api/x4/capture', express.json(), (req, res) => {
  // When the phishing page sends back the token, respond with the flag
  const token = req.body.token || '';
  if (token === 'victim_session_abc123') {
    res.json({ flag: FLAGS.x4 });
  } else {
    res.json({ error: 'wrong token' });
  }
});

// X4: credential endpoint that auto-sends the victim's session token when re-logging in
app.post('/api/x4/login', express.urlencoded({ extended: false }), (req, res) => {
  // Simulates the victim re-logging in on a phishing page
  res.json({ token: 'victim_session_abc123', flag: FLAGS.x4 });
});

// ── Lab X5 — Cross-Site WebSocket Hijacking ─────────────────────────────
const wss = new WebSocket.Server({ server, path: '/ws/x5' });

wss.on('connection', (socket, req) => {
  // VULNERABLE: no Origin check — any cross-origin page can connect
  const cookieHeader = req.headers.cookie || '';
  const hasSession = cookieHeader.includes('hunt_sess');
  // Send the flag to any connected authenticated client (cookie auto-sent by browser)
  if (hasSession) {
    socket.send(JSON.stringify({ type: 'feed', message: 'Welcome to the live feed!', flag: FLAGS.x5 }));
  } else {
    socket.send(JSON.stringify({ type: 'feed', message: 'No session — please log in.' }));
  }
});

// ── Start ────────────────────────────────────────────────────────────────
server.listen(3000, () => console.log('[victim] http://localhost:3000'));
