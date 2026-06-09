<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — Chained RCE (Capstone)';
$lab_id     = 'r6';
include __DIR__ . '/includes/header.php';
?>

<div class="page interview-page">

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-expert">Expert</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R6 — INTERVIEW PREP</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview relevance</span>
      </div>
      <h1>Chained RCE (Capstone): Interview Prep</h1>
      <p class="lab-header-desc">How to articulate a methodical approach to RCE on hardened applications — the technique that separates senior researchers from juniors.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r6.php" class="btn btn-ghost btn-sm">Back to Lab</a>
    </div>
  </div>

  <div class="interview-body" style="display:flex;flex-direction:column;gap:20px;max-width:860px;">

    <!-- 30-second answer -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">30-Second Answer</span>
        <span style="font-size:0.7rem;color:var(--text-dim);font-family:var(--font-mono)">memorise this</span>
      </div>
      <div class="panel-body">
        <blockquote style="border-left:3px solid var(--accent);padding-left:16px;margin:0;font-size:0.925rem;color:var(--text);line-height:1.7;">
          "Real RCE findings require chaining: bypass upload restrictions, land code execution, then adapt when obvious functions are blocked. I enumerate upload restrictions to find missed extensions like <code>.phtml</code> or <code>.phar</code>, upload a webshell, then check the <code>disable_functions</code> list — and reach for alternatives like <code>proc_open</code> or <code>file_get_contents</code> that developers typically forget to block. Methodology matters more than any single technique."
        </blockquote>
      </div>
    </div>

    <!-- Common Qs -->
    <div class="panel">
      <div class="panel-header"><span class="panel-title">Common Interview Questions</span></div>
      <div class="panel-body" style="display:flex;flex-direction:column;gap:18px;">

        <div class="interview-qa">
          <div class="interview-q">Walk me through achieving RCE on a hardened PHP application.</div>
          <div class="interview-a">
            <ol style="margin:4px 0 0;padding-left:20px;display:flex;flex-direction:column;gap:8px;">
              <li><strong>Enumerate upload restrictions.</strong> What extensions are blocked? Is the check case-sensitive? Does it check extension or MIME type? Try <code>.phtml</code>, <code>.php5</code>, <code>.phar</code>, double extensions (<code>shell.php.jpg</code>), and null bytes (PHP &lt;5.3).</li>
              <li><strong>Upload a webshell.</strong> Once an extension passes, upload a file that reads the flag or opens a shell. Keep it minimal to avoid signature detection.</li>
              <li><strong>Check disable_functions.</strong> Run <code>&lt;?php var_dump(ini_get('disable_functions')); ?&gt;</code> to see what is blocked.</li>
              <li><strong>Find unblocked execution primitives.</strong> <code>proc_open</code>, <code>file_get_contents</code>, <code>fopen</code> with stream wrappers, <code>dl()</code>, or FFI (PHP 8).</li>
              <li><strong>Escalate.</strong> Read the flag, establish persistence, or pivot to the OS.</li>
            </ol>
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">How do you bypass <code>disable_functions</code>?</div>
          <div class="interview-a">
            <ul style="margin:4px 0 0;padding-left:20px;display:flex;flex-direction:column;gap:6px;">
              <li><code>proc_open()</code> — often forgotten; provides full pipe-based process execution.</li>
              <li><code>file_get_contents('php://filter/...')</code> — read arbitrary files without shell access.</li>
              <li><code>dl()</code> — load a PHP extension dynamically (requires open_basedir bypass and write access).</li>
              <li><strong>FFI (PHP 8+)</strong> — call C library functions directly: <code>FFI::cdef("int system(const char *cmd);")->system("id")</code>.</li>
              <li><strong>LD_PRELOAD via mail()</strong> — classic technique: write a shared object to /tmp, set <code>putenv("LD_PRELOAD=/tmp/evil.so")</code>, trigger with <code>mail()</code> or <code>error_log()</code>.</li>
              <li><strong>imap_open() SSRF/RCE</strong> — older technique using <code>imap_open</code> with a crafted mailbox string.</li>
            </ul>
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">What extensions bypass PHP upload filters?</div>
          <div class="interview-a">
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
              <?php foreach (['.phtml', '.php5', '.php7', '.phar', '.shtml', '.pht', '.pgif'] as $ext): ?>
              <span style="font-family:var(--font-mono);font-size:0.8rem;background:rgba(239,68,68,0.12);color:var(--red);padding:2px 8px;border-radius:4px;"><?= h($ext) ?></span>
              <?php endforeach; ?>
            </div>
            <ul style="margin:4px 0 0;padding-left:20px;display:flex;flex-direction:column;gap:6px;">
              <li><strong>Double extension</strong>: <code>shell.php.jpg</code> — some servers process the first extension.</li>
              <li><strong>Null byte</strong>: <code>shell.php%00.jpg</code> — PHP &lt;5.3 truncated at null byte.</li>
              <li><strong>MIME type spoofing</strong>: set <code>Content-Type: image/jpeg</code> while uploading PHP code; bypasses MIME-only checks.</li>
              <li><strong>Case variation</strong>: <code>.PHP</code>, <code>.Php</code> — bypasses case-sensitive blacklists on Linux.</li>
            </ul>
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">How should developers harden file uploads against webshell upload?</div>
          <div class="interview-a">
            <ul style="margin:4px 0 0;padding-left:20px;display:flex;flex-direction:column;gap:6px;">
              <li>Use an <strong>allowlist</strong> (not a blocklist) of permitted extensions and check MIME type with <code>finfo</code>.</li>
              <li>Store uploads <strong>outside the web root</strong> so they cannot be directly HTTP-requested.</li>
              <li>Rename uploaded files to a random UUID — never use the user-supplied filename.</li>
              <li>Serve files through a controller that sets correct <code>Content-Type</code> and <code>Content-Disposition</code> headers, never executing them as PHP.</li>
              <li>Set PHP's <code>open_basedir</code> to restrict the directories PHP can access.</li>
            </ul>
          </div>
        </div>

      </div>
    </div>

    <!-- Extension bypass cheatsheet -->
    <div class="panel">
      <div class="panel-header"><span class="panel-title">disable_functions Bypass — Quick Reference</span></div>
      <div class="panel-body">
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
          <thead>
            <tr style="border-bottom:1px solid var(--border);">
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">Primitive</th>
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">PHP Version</th>
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">Prerequisite</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">proc_open()</td>
              <td style="padding:6px 10px;color:var(--text-muted);">All</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Not in disable_functions</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">file_get_contents()</td>
              <td style="padding:6px 10px;color:var(--text-muted);">All</td>
              <td style="padding:6px 10px;color:var(--text-muted);">File read, not command exec</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">FFI::cdef()</td>
              <td style="padding:6px 10px;color:var(--text-muted);">PHP 7.4+</td>
              <td style="padding:6px 10px;color:var(--text-muted);">ffi.enable=preload or On</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">mail() + LD_PRELOAD</td>
              <td style="padding:6px 10px;color:var(--text-muted);">All (Linux)</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Write access to /tmp, mail enabled</td>
            </tr>
            <tr>
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">dl()</td>
              <td style="padding:6px 10px;color:var(--text-muted);">All</td>
              <td style="padding:6px 10px;color:var(--text-muted);">enable_dl=On, write access</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
