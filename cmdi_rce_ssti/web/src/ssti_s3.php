<?php
// Lab S3 — Blind SSTI (Intermediate)
// Vulnerability: Twig renders user input but output is stored, not shown
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

$lab_id   = 's3';
$flag_file = 'lab_s3.txt';

$hints = [
    1 => "You get the same confirmation no matter what you submit. But is the template engine still running? Can you make it do something observable?",
    2 => "The rendered output is stored somewhere. If you inject a payload that produces output, you can recover it through the preview endpoint.",
    3 => "Use the same Twig RCE gadget from S2 as your display name. Then visit <code>?preview=1</code> to see the stored rendered output — which will contain your command's output.",
];

$submit_msg = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /ssti_s3.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /ssti_s3.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /ssti_s3.php");
    exit;
}

// Handle display_name update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['display_name'])) {
    $name = $_POST['display_name'];
    log_payload($lab_id, $name, 'display_name (POST)');

    $loader = new \Twig\Loader\ArrayLoader([]);
    $twig   = new \Twig\Environment($loader);

    try {
        // DELIBERATELY VULNERABLE: renders but NEVER shows output to user
        $tpl      = $twig->createTemplate("Welcome back, " . $name);
        $rendered = $tpl->render([]);
        // Store to file — not shown to user
        file_put_contents('/tmp/welcome_preview.txt', $rendered);
        $submit_msg = "Display name updated successfully!";
    } catch (\Twig\Error\Error $e) {
        // Hide error too — blind
        $submit_msg = "Display name updated!";
    }
}

// Admin debug preview (simulates admin seeing rendered output)
$preview_content = null;
if (isset($_GET['preview'])) {
    $preview_content = @file_get_contents('/tmp/welcome_preview.txt');
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab S3 — Blind SSTI';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You executed blind SSTI — injecting into a template whose output is never shown directly, then recovering the result through a secondary channel.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-intermediate">Intermediate</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB S3</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview · 🎯 <?= stars(4) ?> Hunt</span>
      </div>
      <h1>Blind SSTI</h1>
      <p class="lab-header-desc">An account settings page renders your display name through Twig — but the output is never returned to you. The rendered result is stored server-side. Find the secondary channel that exposes it.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/ssti_s3_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset payload log?')">Reset</button>
      </form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Simulated App: Account Settings -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Account Settings</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">POST /settings</span>
        </div>
        <div class="panel-body">
          <?php if ($submit_msg): ?>
          <div class="alert alert-success" style="margin-bottom:14px;"><?= h($submit_msg) ?></div>
          <?php endif; ?>

          <form method="post" action="/ssti_s3.php" style="display:flex;flex-direction:column;gap:12px;max-width:480px;">
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:4px;">Display Name</label>
              <input type="text" name="display_name" placeholder="Enter your display name" style="width:100%;box-sizing:border-box;">
            </div>
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:4px;">Email</label>
              <input type="text" placeholder="user@example.com (not processed)" style="width:100%;box-sizing:border-box;" disabled>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-start;">Save Settings</button>
          </form>

          <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
            <span style="font-size:0.75rem;color:var(--text-dim);">Debug:</span>
            <a href="/ssti_s3.php?preview=1" style="font-size:0.8rem;margin-left:8px;color:var(--purple);">View rendered welcome message [Admin Preview &rarr;]</a>
          </div>
        </div>
      </div>

      <?php if (isset($_GET['preview'])): ?>
      <!-- Preview Panel -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Admin Preview — Rendered Welcome Message</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET ?preview=1</span>
        </div>
        <div class="panel-body">
          <?php if ($preview_content !== false && $preview_content !== null && $preview_content !== ''): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">// /tmp/welcome_preview.txt</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--green, #4ade80);font-size:0.9rem;"><?= h($preview_content) ?></pre>
          </div>
          <?php else: ?>
          <p class="text-muted" style="font-size:0.85rem;">No preview stored yet. Submit a display name first.</p>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// settings.php</span><br>
            <span class="query">$name = $_POST[<span style="color:var(--orange)">'display_name'</span>];<br>
<br>
<span style="color:var(--text-dim)">// VULNERABLE: renders but NEVER shows output</span><br>
$tpl = $twig-&gt;createTemplate(<span style="color:var(--orange)">"Welcome back, " . $name</span>);<br>
$rendered = $tpl-&gt;render([]);<br>
<br>
<span style="color:var(--text-dim)">// Store to file — not shown to user</span><br>
file_put_contents(<span style="color:var(--orange)">'/tmp/welcome_preview.txt'</span>, $rendered);<br>
echo <span style="color:var(--orange)">"Display name updated successfully!"</span>;</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Recognize the blind injection — the success message is always the same regardless of your input.</div>
          <div>2. Submit a Twig RCE payload as your display name to execute <code>cat /flags/lab_s3.txt</code>.</div>
          <div>3. Visit <code>?preview=1</code> to read the stored rendered output — your command's output will appear there.</div>
          <div>4. Submit the flag below.</div>
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
