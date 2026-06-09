<?php
// Lab C1 — Classic Command Injection
// Vulnerability config: disable_functions = (empty)
// Mechanism: shell_exec() with raw user input concatenated
require_once __DIR__ . '/includes/helpers.php';

$lab_id = 'c1';
$flag_file = 'lab_c1.txt';

$hints = [
    1 => "Your input is handed directly to a real shell. Shells let you execute more than one command per line.",
    2 => "Characters like <code>;</code>, <code>&&</code>, and <code>|</code> chain commands together. What happens if you add one after a valid hostname?",
    3 => "Try: <code>127.0.0.1; cat /flags/lab_c1.txt</code> — the ping runs, then your command. The output appears below the ping result.",
];

$host   = '';
$output = null;
$elapsed_ms = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /cmd_c1.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /cmd_c1.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /cmd_c1.php");
    exit;
}

// Run ping
if (isset($_GET['host']) && $_GET['host'] !== '') {
    $host = $_GET['host'];
    log_payload($lab_id, $host, 'host parameter');
    $t0 = microtime(true);
    // DELIBERATELY VULNERABLE — user input concatenated directly into shell command
    $output = shell_exec("ping -c 2 -W 1 " . $host . " 2>&1");
    $elapsed_ms = (int)((microtime(true) - $t0) * 1000);
}

$solved           = is_solved($lab_id);
$hints_revealed   = hints_revealed($lab_id);
$payload_log      = get_payload_log($lab_id);
$flag_error       = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab C1 — Classic Command Injection';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You injected shell metacharacters into an OS command to read a protected flag file.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-beginner">Beginner</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB C1</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview · 🎯 <?= stars(3) ?> Hunt</span>
      </div>
      <h1>Classic Command Injection</h1>
      <p class="lab-header-desc">A network diagnostics page runs <code>ping</code> against a user-supplied hostname via <code>shell_exec()</code> — with no sanitization. Inject shell metacharacters to run your own commands.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/cmd_c1_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset payload log?')">Reset</button>
      </form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Simulated App -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Network Diagnostics</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET /diagnostics?host=<?= h($host) ?></span>
        </div>
        <div class="panel-body">
          <form method="get" action="/cmd_c1.php" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <input type="text" name="host" value="<?= h($host) ?>" placeholder="Enter hostname or IP (e.g. 127.0.0.1)" style="flex:1;min-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm">Ping</button>
          </form>
          <?php if ($output !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">$ ping -c 2 -W 1 <?= h($host) ?> <span style="float:right"><?= $elapsed_ms ?>ms</span></div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--text);font-size:0.8rem;"><?= h($output) ?></pre>
          </div>
          <?php else: ?>
          <p class="text-muted" style="font-size:0.85rem;">Enter a host above to run a connectivity test.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// diagnostics.php</span><br>
            <span class="query">$host = $_GET[<span style="color:var(--orange)">'host'</span>];<br>
<span style="color:var(--text-dim)">// no sanitization — raw input into shell</span><br>
$output = shell_exec(<span style="color:var(--orange)">"ping -c 2 -W 1 "</span> . $host);<br>
echo &lt;pre&gt;$output&lt;/pre&gt;;</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Confirm the injection by making the server run a command you control.</div>
          <div>2. Read the flag file at <code>/flags/lab_c1.txt</code> by chaining your command after the ping.</div>
          <div>3. Submit the flag below.</div>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Flag Submission -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Submit Flag</span></div>
        <div class="panel-body">
          <?php if ($flag_error): ?>
          <div class="alert alert-error" data-dismiss="4000">Incorrect flag. Keep hunting.</div>
          <?php endif; ?>
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

      <!-- Hints -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Hints</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= count($hints_revealed) ?>/3 revealed</span>
        </div>
        <div class="panel-body">
          <div class="hints-list">
            <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="hint-item">
              <div class="hint-header">
                Hint <?= $i ?>
                <?php if (!in_array($i, $hints_revealed, true)): ?>
                <form method="post" style="margin:0">
                  <input type="hidden" name="reveal_hint" value="<?= $i ?>">
                  <button type="submit" class="btn btn-ghost btn-sm">Reveal</button>
                </form>
                <?php endif; ?>
              </div>
              <?php if (in_array($i, $hints_revealed, true)): ?>
              <div class="hint-content"><?= $hints[$i] ?></div>
              <?php endif; ?>
            </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>

      <!-- Payload Log -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Payload Log</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= count($payload_log) ?> entries</span>
        </div>
        <div class="panel-body">
          <div class="payload-log">
            <?php if ($payload_log): ?>
            <?php foreach (array_slice($payload_log, 0, 10) as $entry): ?>
            <div class="log-entry" title="Click to copy">
              <div class="log-payload"><?= h($entry['payload']) ?></div>
              <div class="log-context"><?= h($entry['context']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="log-empty">No payloads yet. Start testing.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
