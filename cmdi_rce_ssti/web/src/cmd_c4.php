<?php
// Lab C4 — Argument Injection
// Vulnerability config: metacharacters stripped but spaces preserved; curl flag injection
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'c4';
$flag_file = 'lab_c4.txt';

$hints = [
    1 => "Shell metacharacters are sanitized — but spaces are not. You're still controlling what comes after <code>curl -s</code>.",
    2 => "Command-line tools take flags. What if your input started with a dash? What does <code>curl -o</code> do?",
    3 => "Try: <code>?url=-o /tmp/curl_output.txt file:///flags/lab_c4.txt</code> — then visit <code>?show_output=1</code> to read the output.",
];

$url        = '';
$safe_url   = '';
$output     = null;
$was_filtered = false;
$file_contents = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /cmd_c4.php"); exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) mark_solved($lab_id);
    else $_SESSION[flag_error_key($lab_id)] = true;
    header("Location: /cmd_c4.php"); exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /cmd_c4.php"); exit;
}

// Process URL parameter
if (isset($_GET['url']) && $_GET['url'] !== '') {
    $url      = $_GET['url'];
    // DELIBERATELY VULNERABLE — strips metacharacters but not spaces; arg injection possible
    $safe_url = preg_replace('/[;&|`$<>\'"\\\\]/', '', $url);
    $was_filtered = ($safe_url !== $url);
    log_payload($lab_id, $url, 'url parameter');
    $output = shell_exec("curl -s " . $safe_url . " 2>&1");
}

// Show output file
if (isset($_GET['show_output']) && $_GET['show_output'] === '1') {
    $file_contents = @file_get_contents('/tmp/curl_output.txt');
    if ($file_contents === false) $file_contents = '(output file does not exist yet — run curl with -o first)';
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab C4 — Argument Injection';
include __DIR__ . '/includes/header.php';
?>
<div class="page lab-page">
  <?php if ($solved): ?><div class="solved-banner"><div class="solved-banner-icon">🎉</div><div><div class="solved-banner-text">Lab Complete!</div><div class="solved-banner-sub">You injected curl flags past a metacharacter filter to read a protected file.</div></div></div><?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-intermediate">Intermediate</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB C4</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview · 🎯 <?= stars(3) ?> Hunt</span>
      </div>
      <h1>Argument Injection</h1>
      <p class="lab-header-desc">A URL fetcher strips shell metacharacters — but spaces survive. Inject extra <code>curl</code> flags to write the flag file to a path you can read.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/cmd_c4_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0"><input type="hidden" name="action" value="reset"><button type="submit" class="btn btn-danger btn-sm">Reset</button></form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- URL Fetcher App -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">URL Fetcher</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET /fetch?url=</span>
        </div>
        <div class="panel-body">
          <form method="get" action="/cmd_c4.php" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <input type="text" name="url" value="<?= h($_GET['url'] ?? '') ?>" placeholder="https://example.com" style="flex:1;min-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm">Fetch</button>
          </form>
          <?php if ($was_filtered): ?>
          <div class="waf-status waf-blocked" style="background:rgba(210,153,34,0.1);border:1px solid rgba(210,153,34,0.3);color:var(--yellow);padding:8px 12px;border-radius:4px;font-size:0.8rem;margin-bottom:12px;">🛡 Filter applied — shell metacharacters were stripped before execution</div>
          <?php endif; ?>
          <?php if ($output !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">$ curl -s <?= h($safe_url) ?></div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--text);font-size:0.8rem;"><?= h($output) ?></pre>
          </div>
          <?php else: ?>
          <p style="font-size:0.85rem;color:var(--text-muted);">Enter a URL above to fetch its contents.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Output File Viewer -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Output File Viewer</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET ?show_output=1</span>
        </div>
        <div class="panel-body">
          <?php if ($file_contents !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">$ cat /tmp/curl_output.txt</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--text);font-size:0.8rem;"><?= h($file_contents) ?></pre>
          </div>
          <?php else: ?>
          <p style="font-size:0.85rem;color:var(--text-muted);">Append <code>?show_output=1</code> to inspect <code>/tmp/curl_output.txt</code>. <a href="/cmd_c4.php?show_output=1">View now →</a></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Source -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// Strips metacharacters — but NOT spaces</span><br>
            <span class="query">$safe_url = preg_replace(<span style="color:var(--orange)">'/[;&|`$&lt;&gt;\'\"\\\\]/'</span>, <span style="color:var(--orange)">''</span>, $_GET[<span style="color:var(--orange)">'url'</span>]);<br>
$output = shell_exec(<span style="color:var(--orange)">"curl -s "</span> . $safe_url . <span style="color:var(--orange)">" 2&amp;>&amp;1"</span>);</span>
          </div>
        </div>
      </div>

      <!-- Active Filter -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Active Filter</span></div>
        <div class="panel-body">
          <div class="blocked-terms" style="display:flex;flex-wrap:wrap;gap:6px;">
            <?php foreach ([';', '&', '|', '`', '$', '<', '>', "'", '"', '\\'] as $c): ?>
            <span style="font-family:var(--font-mono);font-size:0.75rem;background:rgba(248,81,73,0.1);border:1px solid rgba(248,81,73,0.3);color:var(--red);padding:2px 8px;border-radius:4px;"><?= h($c) ?></span>
            <?php endforeach; ?>
          </div>
          <p style="font-size:0.8rem;color:var(--text-dim);margin-top:10px;">These characters are stripped. Spaces are not filtered.</p>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Inject extra curl flags via the <code>?url=</code> parameter to redirect output.</div>
          <div>2. Write <code>/flags/lab_c4.txt</code> to <code>/tmp/curl_output.txt</code> using <code>-o</code>.</div>
          <div>3. Visit <code>?show_output=1</code> to read the flag, then submit it below.</div>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <div class="panel">
        <div class="panel-header"><span class="panel-title">Submit Flag</span></div>
        <div class="panel-body">
          <?php if ($flag_error): ?><div class="alert alert-error" data-dismiss="4000">Incorrect flag. Keep hunting.</div><?php endif; ?>
          <?php if ($solved): ?>
          <div class="alert alert-success">Flag accepted! Lab complete.</div>
          <?php else: ?>
          <form method="post" class="flag-form">
            <input class="flag-input" type="text" name="flag" placeholder="HUNT{...}" autocomplete="off">
            <button type="submit" class="btn btn-primary btn-full">Submit</button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Hints</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= count($hints_revealed) ?>/3 revealed</span>
        </div>
        <div class="panel-body"><div class="hints-list">
          <?php for ($i = 1; $i <= 3; $i++): ?>
          <div class="hint-item">
            <div class="hint-header">Hint <?= $i ?>
              <?php if (!in_array($i, $hints_revealed, true)): ?>
              <form method="post" style="margin:0"><input type="hidden" name="reveal_hint" value="<?= $i ?>"><button type="submit" class="btn btn-ghost btn-sm">Reveal</button></form>
              <?php endif; ?>
            </div>
            <?php if (in_array($i, $hints_revealed, true)): ?><div class="hint-content"><?= $hints[$i] ?></div><?php endif; ?>
          </div>
          <?php endfor; ?>
        </div></div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Payload Log</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= count($payload_log) ?> entries</span>
        </div>
        <div class="panel-body"><div class="payload-log">
          <?php if ($payload_log): foreach (array_slice($payload_log, 0, 10) as $entry): ?>
          <div class="log-entry" title="Click to copy"><div class="log-payload"><?= h($entry['payload']) ?></div><div class="log-context"><?= h($entry['context']) ?></div></div>
          <?php endforeach; else: ?><div class="log-empty">No payloads yet. Start testing.</div><?php endif; ?>
        </div></div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
