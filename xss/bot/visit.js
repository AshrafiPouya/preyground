'use strict';
const puppeteer = require('puppeteer');

const VICTIM_URL   = process.env.VICTIM_URL   || 'http://localhost:3000';
const ATTACKER_URL = process.env.ATTACKER_URL || 'http://localhost:3001';
const ADMIN_TOKEN  = 'admin-hunt-secret-do-not-share';

const VISIT_INTERVAL_MS = 10_000; // visit stored-XSS pages every 10s

async function runBot() {
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: process.env.CHROME_PATH || '/usr/bin/chromium-browser',
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu',
    ],
  });

  console.log('[bot] Started. Visiting victim pages every', VISIT_INTERVAL_MS / 1000, 'seconds.');

  async function visitPage(url, label) {
    let page;
    try {
      page = await browser.newPage();
      // Set the admin cookie on the victim origin
      await page.setCookie({
        name: 'admin_token',
        value: ADMIN_TOKEN,
        domain: new URL(VICTIM_URL).hostname,
        path: '/',
        httpOnly: false,
        sameSite: 'Lax',
      });
      await page.goto(url, { waitUntil: 'networkidle2', timeout: 8000 });
      console.log(`[bot] Visited ${label}: ${url}`);
    } catch (e) {
      console.error(`[bot] Error visiting ${label}:`, e.message);
    } finally {
      if (page) await page.close();
    }
  }

  async function visitAll() {
    // Lab 5: stored XSS on comment board (admin view)
    await visitPage(`${VICTIM_URL}/lab5/admin`, 'lab5-admin');
    // Lab 7: blind XSS in admin inbox
    await visitPage(`${VICTIM_URL}/lab7/admin`, 'lab7-admin');
    // Lab 20: capstone admin inbox
    await visitPage(`${VICTIM_URL}/lab20/admin`, 'lab20-admin');
    // Lab 20: community board (comment board)
    await visitPage(`${VICTIM_URL}/lab20/comments`, 'lab20-comments');
  }

  // First run immediately, then on interval
  await visitAll();
  setInterval(visitAll, VISIT_INTERVAL_MS);
}

runBot().catch(err => {
  console.error('[bot] Fatal error:', err);
  process.exit(1);
});
