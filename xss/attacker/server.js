'use strict';
const express = require('express');
const path = require('path');

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: false }));

// CORS — allow victim to POST exfil here
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.sendStatus(204);
  next();
});

// ── Exfil log (in-memory, last 100 entries) ────────────────────────────
const exfilLog = [];

app.post('/log', (req, res) => {
  const entry = {
    ts: new Date().toISOString(),
    ip: req.ip,
    ...req.body,
  };
  exfilLog.unshift(entry);
  if (exfilLog.length > 100) exfilLog.length = 100;
  console.log('[attacker /log]', entry);
  res.json({ ok: true });
});

app.get('/log', (req, res) => {
  res.json(exfilLog);
});

// ── JSONP endpoint — usable for CSP bypass (Lab 19) ───────────────────
app.get('/lab19-jsonp', (req, res) => {
  const cb = /^[a-zA-Z0-9_]+$/.test(req.query.callback) ? req.query.callback : 'callback';
  res.setHeader('Content-Type', 'application/javascript');
  res.send(`${cb}();`);
});

// ── Static attacker pages ──────────────────────────────────────────────
app.use(express.static(path.join(__dirname, 'public')));

app.listen(3001, () => console.log('[attacker] http://localhost:3001'));
