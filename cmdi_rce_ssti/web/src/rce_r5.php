<?php
// Lab R5 — Dynamic Callable Abuse (Advanced)
// Mechanism: call_user_func() with user-controlled function name → RCE / arbitrary file read
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'r5';
$flag_file = 'lab_r5.txt';

$hints = [
    1 => "The app lets you choose <em>which function</em> processes your text. <code>strtoupper</code> is one option — but what other PHP functions are callable?",
    2 => "Functions like <code>system</code>, <code>passthru</code>, <code>file_get_contents</code> are all valid PHP callables. What would <code>file_get_contents('/flags/lab_r5.txt')</code> return?",
    3 => "Try: <code>?transform=file_get_contents&amp;data=/flags/lab_r5.txt</code> — the 'data' becomes the argument to the function you chose.",
];

$fn         = 'strtoupper';
$data       = 'hello world';
$result     = null;
$call_error = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /rce_r5.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /rce_r5.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /rce_r5.php");
    exit;
}

// Text Transformer — vulnerable logic
if (isset($_GET['transform']) || isset($_GET['data'])) {
    $fn   = $_GET['transform'] ?? 'strtoupper';
    $data = $_GET['data'] ?? 'hello world';
    if ($fn !== '' && $data !== '') {
        log_payload($lab_id, "transform={$fn}&data={$data}", 'call_user_func');
        try {
            // DELIBERATELY VULNERABLE — user controls which PHP function is called
            $result = call_user_func($fn, $data);
        } catch (Throwable $e) {
            $call_error = $e->getMessage();
        }
    }
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$safe_transforms = ['strtoupper', 'strtolower', 'strlen', 'str_rot13', 'md5'];

$page_title = 'Lab R5 — Dynamic Callable Abuse';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You abused <code>call_user_func()</code> with a user-controlled callable to achieve arbitrary PHP function execution.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-advanced">Advanced</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R5</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview · 🎯 <?= stars(4) ?> Hunt</span>
      </div>
      <h1>Dynamic Callable Abuse</h1>
      <p class="lab-header-desc">A text transformation utility passes a user-selected function name directly to <code>call_user_func()</code>. Any PHP callable can be substituted — including <code>system</code>, <code>passthru</code>, and <code>file_get_contents</code>.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r5_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset payload log?')">Reset</button>
      </form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Text Transformer App -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Text Transformer</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET /rce_r5.php?transform=<?= h($fn) ?>&amp;data=<?= h($data) ?></span>
        </div>
        <div class="panel-body">
          <form method="get" action="/rce_r5.php" style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
              <div style="display:flex;flex-direction:column;gap:4px;flex:0 0 auto;">
                <label style="font-size:0.75rem;color:var(--text-dim);">Preset transforms</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <?php foreach ($safe_transforms as $t): ?>
                  <a href="/rce_r5.php?transform=<?= urlencode($t) ?>&amp;data=<?= urlencode($data) ?>" class="btn btn-ghost btn-sm" style="font-size:0.75rem;"><?= h($t) ?></a>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <div style="display:flex;flex-direction:column;gap:4px;flex:1;min-width:160px;">
                <label style="font-size:0.75rem;color:var(--text-dim);">Function name (transform)</label>
                <input type="text" name="transform" value="<?= h($fn) ?>" placeholder="e.g. strtoupper" style="font-family:var(--font-mono);">
              </div>
              <div style="display:flex;flex-direction:column;gap:4px;flex:2;min-width:220px;">
                <label style="font-size:0.75rem;color:var(--text-dim);">Input data</label>
                <input type="text" name="data" value="<?= h($data) ?>" placeholder="Text to transform">
              </div>
              <div style="display:flex;align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">Run</button>
              </div>
            </div>
          </form>

          <?php if ($result !== null || $call_error): ?>
          <div class="terminal" style="margin-top:14px;">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">
              call_user_func(<span style="color:var(--orange)">'<?= h($fn) ?>'</span>, <span style="color:var(--orange)">'<?= h($data) ?>'</span>)
            </div>
            <?php if ($call_error): ?>
            <pre style="margin:0;white-space:pre-wrap;color:var(--red);font-size:0.8rem;"><?= h($call_error) ?></pre>
            <?php else: ?>
            <pre style="margin:0;white-space:pre-wrap;color:var(--text);font-size:0.8rem;"><?= h((string)$result) ?></pre>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// text_transformer.php</span><br>
            <span class="query">$fn   = $_GET[<span style="color:var(--orange)">'transform'</span>] ?? <span style="color:var(--orange)">'strtoupper'</span>;<br>
$data = $_GET[<span style="color:var(--orange)">'data'</span>] ?? <span style="color:var(--orange)">'hello world'</span>;<br><br>
<span style="color:var(--text-dim)">// VULNERABLE: user controls which PHP function is called</span><br>
$result = call_user_func($fn, $data);<br>
echo $result;</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Identify a PHP function that can read files or execute system commands.</div>
          <div>2. Supply it as the <code>transform</code> parameter and target <code>/flags/lab_r5.txt</code> as the data.</div>
          <div>3. Capture the flag and submit it below.</div>
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
