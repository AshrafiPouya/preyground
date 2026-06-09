<?php
// Lab C3 — Blind Command Injection
// Vulnerability config: shell_exec with echo >> log — output never shown; timing + log exfil
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'c3';
$flag_file = 'lab_c3.txt';

$hints = [
    1 => "You get the same response no matter what — but can you make the server pause?",
    2 => "Inject <code>; sleep 5</code> after your name — if the response takes 5+ seconds, execution is confirmed.",
    3 => "Once you confirm execution: inject <code>; cp /flags/lab_c3.txt /tmp/feedback.log</code> to overwrite the log with the flag, then visit <code>?view_log=1</code> to read it.",
];

$feedback_msg = null;
$elapsed_ms   = null;
$log_contents = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /cmd_c3.php"); exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) mark_solved($lab_id);
    else $_SESSION[flag_error_key($lab_id)] = true;
    header("Location: /cmd_c3.php"); exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /cmd_c3.php"); exit;
}

// Handle feedback POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['message'])) {
    $name    = $_POST['name'];
    $message = $_POST['message'];
    log_payload($lab_id, $name, 'name field');
    $t0 = microtime(true);
    // DELIBERATELY VULNERABLE — name is unsanitized; output never shown
    shell_exec("echo " . $name . " >> /tmp/feedback.log");
    $elapsed_ms   = (int)((microtime(true) - $t0) * 1000);
    $feedback_msg = "Thanks for your feedback!";
}

// Admin debug view
if (isset($_GET['view_log']) && $_GET['view_log'] === '1') {
    $log_contents = @file_get_contents('/tmp/feedback.log');
    if ($log_contents === false) $log_contents = '(log file does not exist yet — submit feedback first)';
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab C3 — Blind Command Injection';
include __DIR__ . '/includes/header.php';
?>
<div class="page lab-page">
  <?php if ($solved): ?><div class="solved-banner"><div class="solved-banner-icon">🎉</div><div><div class="solved-banner-text">Lab Complete!</div><div class="solved-banner-sub">You exploited blind command injection using timing confirmation and log exfiltration.</div></div></div><?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-advanced">Advanced</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB C3</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview · 🎯 <?= stars(4) ?> Hunt</span>
      </div>
      <h1>Blind Command Injection</h1>
      <p class="lab-header-desc">A feedback form passes your name to <code>shell_exec()</code> — but the output is never shown. Confirm execution via timing, then exfiltrate the flag through the debug log viewer.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/cmd_c3_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0"><input type="hidden" name="action" value="reset"><button type="submit" class="btn btn-danger btn-sm">Reset</button></form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Simulated Feedback App -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Customer Feedback</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">POST /feedback</span>
        </div>
        <div class="panel-body">
          <?php if ($feedback_msg): ?>
          <div class="alert alert-success" style="margin-bottom:12px;"><?= h($feedback_msg) ?>
            <?php if ($elapsed_ms !== null): ?>
            <span style="float:right;font-family:var(--font-mono);font-size:0.75rem;color:var(--accent)">⏱ <?= $elapsed_ms ?>ms</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <form method="post" action="/cmd_c3.php" style="display:flex;flex-direction:column;gap:12px;">
            <div class="form-group">
              <label for="feedback-name">Your Name</label>
              <input type="text" id="feedback-name" name="name" placeholder="e.g. Alice" value="<?= h($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="feedback-msg">Message</label>
              <textarea id="feedback-msg" name="message" rows="3" placeholder="Tell us what you think..."><?= h($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-start;">Leave Feedback</button>
          </form>
          <?php if ($elapsed_ms !== null && !$feedback_msg): ?><?php endif; ?>
        </div>
      </div>

      <!-- Admin Debug Log Viewer -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Admin Debug View</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET ?view_log=1</span>
        </div>
        <div class="panel-body">
          <?php if ($log_contents !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">$ cat /tmp/feedback.log</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--text);font-size:0.8rem;"><?= $log_contents ?></pre>
          </div>
          <?php else: ?>
          <p style="font-size:0.85rem;color:var(--text-muted);">Append <code>?view_log=1</code> to the URL to inspect the server-side log file. <a href="/cmd_c3.php?view_log=1">View now →</a></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Source -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// feedback.php — output never returned to user</span><br>
            <span class="query">$name = $_POST[<span style="color:var(--orange)">'name'</span>];<br>
<span style="color:var(--text-dim)">// DELIBERATELY VULNERABLE — raw name concatenated</span><br>
shell_exec(<span style="color:var(--orange)">"echo "</span> . $name . <span style="color:var(--orange)">" >> /tmp/feedback.log"</span>);<br>
echo <span style="color:var(--orange)">"Thanks for your feedback!"</span>;</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Confirm blind execution by injecting a sleep and observing response time.</div>
          <div>2. Overwrite <code>/tmp/feedback.log</code> with the contents of <code>/flags/lab_c3.txt</code>.</div>
          <div>3. Visit <code>?view_log=1</code> to read the flag, then submit it below.</div>
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
