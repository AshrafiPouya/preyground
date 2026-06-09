<?php
// Lab C5 — IFS & Wildcard Bypass
// Vulnerability config: keyword blacklist (cat, space, flag, bin, /etc) — bypass via ${IFS} and wildcards
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'c5';
$flag_file = 'lab_c5.txt';

$hints = [
    1 => "No spaces and no <code>cat</code> allowed — but the shell has its own whitespace variable.",
    2 => "<code>\${IFS}</code> substitutes for space; wildcards like <code>/???/c?t</code> can spell out <code>cat</code> without typing it.",
    3 => "Try: <code>127.0.0.1;/???/ca?\${IFS}/?????/lab_c5.txt</code> — <code>/???/ca?</code> glob-expands to <code>/bin/cat</code> without containing the literal strings 'cat' or 'bin', <code>\${IFS}</code> substitutes for space, and <code>?????</code> (5 wildcards) matches the 5-char flags directory.",
];

$host    = '';
$output  = null;
$blocked = ['cat', ' ', 'flag', 'bin', '/etc'];
$was_filtered = false;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /cmd_c5.php"); exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) mark_solved($lab_id);
    else $_SESSION[flag_error_key($lab_id)] = true;
    header("Location: /cmd_c5.php"); exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /cmd_c5.php"); exit;
}

// Process host parameter
if (isset($_GET['host']) && $_GET['host'] !== '') {
    $host     = $_GET['host'];
    $original = $host;
    // DELIBERATELY VULNERABLE — keyword blacklist is incomplete; ${IFS} and wildcards bypass it
    $host = str_ireplace($blocked, '', $host);
    $was_filtered = ($host !== $original);
    log_payload($lab_id, $original, 'host parameter');
    $output = shell_exec("ping -c 1 " . $host . " 2>&1");
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab C5 — IFS & Wildcard Bypass';
include __DIR__ . '/includes/header.php';
?>
<div class="page lab-page">
  <?php if ($solved): ?><div class="solved-banner"><div class="solved-banner-icon">🎉</div><div><div class="solved-banner-text">Lab Complete!</div><div class="solved-banner-sub">You bypassed keyword and space filters using shell IFS substitution and wildcards.</div></div></div><?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-advanced">Advanced</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB C5</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview · 🎯 <?= stars(5) ?> Hunt</span>
      </div>
      <h1>IFS &amp; Wildcard Bypass</h1>
      <p class="lab-header-desc">A ping tool bans <code>cat</code>, spaces, <code>flag</code>, <code>bin</code>, and <code>/etc</code>. Use shell internals — <code>${IFS}</code> and glob wildcards — to read the flag without using the blocked terms.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/cmd_c5_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0"><input type="hidden" name="action" value="reset"><button type="submit" class="btn btn-danger btn-sm">Reset</button></form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Ping Tool -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Network Diagnostics (Hardened++)</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET /diagnostics?host=<?= h($_GET['host'] ?? '') ?></span>
        </div>
        <div class="panel-body">
          <form method="get" action="/cmd_c5.php" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <input type="text" name="host" value="<?= h($_GET['host'] ?? '') ?>" placeholder="Enter host (e.g. 127.0.0.1)" style="flex:1;min-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm">Ping</button>
          </form>
          <?php if ($was_filtered): ?>
          <div style="background:rgba(210,153,34,0.1);border:1px solid rgba(210,153,34,0.3);color:var(--yellow);padding:8px 12px;border-radius:4px;font-size:0.8rem;margin-bottom:12px;">🛡 Filter applied — blocked terms were stripped from input</div>
          <?php endif; ?>
          <?php if ($output !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">$ ping -c 1 <?= h($host) ?></div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--text);font-size:0.8rem;"><?= h($output) ?></pre>
          </div>
          <?php else: ?>
          <p style="font-size:0.85rem;color:var(--text-muted);">Enter a host above to run a connectivity test.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Source -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// Keyword blacklist — incomplete by design</span><br>
            <span class="query">$blocked = [<span style="color:var(--orange)">'cat'</span>, <span style="color:var(--orange)">' '</span>, <span style="color:var(--orange)">'flag'</span>, <span style="color:var(--orange)">'bin'</span>, <span style="color:var(--orange)">'/etc'</span>];<br>
$host = str_ireplace($blocked, <span style="color:var(--orange)">''</span>, $_GET[<span style="color:var(--orange)">'host'</span>]);<br>
$output = shell_exec(<span style="color:var(--orange)">"ping -c 1 "</span> . $host . <span style="color:var(--orange)">" 2&amp;>&amp;1"</span>);</span>
          </div>
        </div>
      </div>

      <!-- Active Blocked Terms -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Active Blocked Terms</span></div>
        <div class="panel-body">
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
            <?php foreach ($blocked as $term): ?>
            <span style="font-family:var(--font-mono);font-size:0.8rem;background:rgba(248,81,73,0.1);border:1px solid rgba(248,81,73,0.3);color:var(--red);padding:4px 10px;border-radius:4px;"><?= h($term === ' ' ? '[SPACE]' : $term) ?></span>
            <?php endforeach; ?>
          </div>
          <p style="font-size:0.8rem;color:var(--text-dim);">These strings are removed (case-insensitive). Shell internals like <code>${IFS}</code> and glob patterns are not blocked.</p>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Confirm command injection using a shell separator after a valid IP.</div>
          <div>2. Use <code>${IFS}</code> in place of spaces and wildcard patterns to reference <code>cat</code> and <code>/flags/lab_c5.txt</code>.</div>
          <div>3. Read the flag in the output and submit it below.</div>
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
