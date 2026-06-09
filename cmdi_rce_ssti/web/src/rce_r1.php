<?php
// Lab R1 — eval() Code Injection
// Vulnerability: user input passed directly to eval()
require_once __DIR__ . '/includes/helpers.php';

$lab_id   = 'r1';
$flag_file = 'lab_r1.txt';

$hints = [
    1 => "Whatever you type becomes PHP code, not just arithmetic. What PHP functions could you smuggle in?",
    2 => "Functions like <code>system()</code>, <code>file_get_contents()</code>, and <code>passthru()</code> are valid PHP. You can call them inside the eval.",
    3 => "Try: <code>1; \$result = file_get_contents('/flags/lab_r1.txt')</code> — terminate the assignment, read the file, assign it to <code>\$result</code>.",
];

$expr       = '';
$result     = null;
$eval_error = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /rce_r1.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /rce_r1.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /rce_r1.php");
    exit;
}

// Run eval
if (isset($_GET['expr']) && $_GET['expr'] !== '') {
    $expr = $_GET['expr'];
    log_payload($lab_id, $expr, 'expr parameter');
    // DELIBERATELY VULNERABLE — user input passed directly to eval()
    try {
        eval("\$result = {$expr};");
    } catch (Throwable $e) {
        $eval_error = $e->getMessage();
    }
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab R1 — eval() Code Injection';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You injected PHP code into an eval() call to read a protected flag file.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-beginner">Beginner</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R1</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview · 🎯 <?= stars(3) ?> Hunt</span>
      </div>
      <h1>eval() Code Injection</h1>
      <p class="lab-header-desc">A "math expression calculator" evaluates your formula using PHP's <code>eval()</code> — with your input dropped in raw. Escape the arithmetic context and execute arbitrary PHP code.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r1_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
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
          <span class="panel-title">Formula Calculator</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET /calculator?expr=<?= h($expr) ?></span>
        </div>
        <div class="panel-body">
          <form method="get" action="/rce_r1.php" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <input type="text" name="expr" value="<?= h($expr) ?>" placeholder="Enter a formula (e.g. 2 + 2 * 10)" style="flex:1;min-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm">Calculate</button>
          </form>

          <?php if ($expr !== ''): ?>
          <div class="terminal" style="margin-bottom:12px;">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:6px;">// eval'd expression</div>
            <pre style="margin:0;color:var(--orange);font-size:0.85rem;"><?= h("\$result = {$expr};") ?></pre>
          </div>
          <?php endif; ?>

          <?php if ($result !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:6px;">Result</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--green, #4ade80);font-size:0.85rem;"><?= h((string)$result) ?></pre>
          </div>
          <?php elseif ($eval_error !== null): ?>
          <div class="terminal">
            <div style="color:var(--red);font-size:0.7rem;margin-bottom:6px;">PHP Error</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--red);font-size:0.8rem;"><?= h($eval_error) ?></pre>
          </div>
          <?php elseif ($expr === ''): ?>
          <p class="text-muted" style="font-size:0.85rem;">Enter a formula above to evaluate it.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// calculator.php</span><br>
            <span class="query">$expr = $_GET[<span style="color:var(--orange)">'expr'</span>];<br>
<span style="color:var(--text-dim)">// no sanitization — raw input passed to eval()</span><br>
<span style="color:var(--red)">eval(</span><span style="color:var(--orange)">"\$result = {${'expr'}};"</span><span style="color:var(--red)">)</span>;<br>
echo <span style="color:var(--orange)">"Result: $result"</span>;</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Confirm code execution by calling a PHP function (e.g. <code>phpinfo()</code> or <code>system('id')</code>).</div>
          <div>2. Read the flag file at <code>/flags/lab_r1.txt</code> using PHP's file functions.</div>
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
