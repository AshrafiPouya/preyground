<?php
// Lab S4 — Engine Fingerprinting: Smarty (Advanced)
// Vulnerability: user input concatenated into Smarty template string; no security policy allows PHP built-ins as modifiers
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

$lab_id   = 's4';
$flag_file = 'lab_s4.txt';

$hints = [
    1 => "The Twig payloads from previous labs don't work here. Different template engines use different syntax — start by probing for the engine.",
    2 => 'Try <code>{$smarty.version}</code> — if it returns a version string like <code>3.1.x</code>, you know the engine. Then look for Smarty-specific execution primitives.',
    3 => "Smarty allows PHP built-in functions as template modifiers when security is disabled. Try: <code>{'cat /flags/lab_s4.txt'|shell_exec}</code> or <code>{'/flags/lab_s4.txt'|file_get_contents}</code>",
];

$name         = '';
$output       = null;
$smarty_error = null;

// Ensure Smarty working dirs exist
foreach (['/tmp/smarty_tpl', '/tmp/smarty_compile', '/tmp/smarty_cache', '/tmp/smarty_config'] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /ssti_s4.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /ssti_s4.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /ssti_s4.php");
    exit;
}

// Render greeting via Smarty
if (isset($_GET['name']) && $_GET['name'] !== '') {
    $name = $_GET['name'];
    log_payload($lab_id, $name, 'name parameter');

    $smarty = new \Smarty();
    $smarty->setTemplateDir('/tmp/smarty_tpl/');
    $smarty->setCompileDir('/tmp/smarty_compile/');
    $smarty->setCacheDir('/tmp/smarty_cache/');
    $smarty->setConfigDir('/tmp/smarty_config/');
    // No security policy set — PHP built-in functions usable as modifiers — DELIBERATELY VULNERABLE
    $smarty->error_reporting = 0;

    try {
        // DELIBERATELY VULNERABLE: Smarty createTemplate with user input
        $tpl = $smarty->createTemplate('string:Hello ' . $name . '!');
        $output = $smarty->fetch($tpl);
    } catch (Throwable $e) {
        $smarty_error = $e->getMessage();
        $output = null;
    }
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab S4 — Engine Fingerprinting: Smarty';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You fingerprinted the Smarty template engine and exploited its modifier system to call a PHP built-in function and read an arbitrary server file.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-advanced">Advanced</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB S4</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview · 🎯 <?= stars(4) ?> Hunt</span>
      </div>
      <h1>Engine Fingerprinting: Smarty</h1>
      <p class="lab-header-desc">Same injection point — different engine. This app uses <strong>Smarty</strong>, not Twig. Twig payloads won't work. Fingerprint the engine using engine-specific probe payloads, then exploit Smarty's modifier system to call PHP built-in functions directly.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/ssti_s4_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
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
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">Powered by Smarty v4 &nbsp;|&nbsp; GET /greeting?name=<?= h($name) ?></span>
        </div>
        <div class="panel-body">
          <form method="get" action="/ssti_s4.php" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <input type="text" name="name" value="<?= h($name) ?>" placeholder="Enter your name (e.g. Alice)" style="flex:1;min-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm">Greet Me</button>
          </form>

          <?php if ($output !== null && $output !== ''): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">// Rendered output</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--green, #4ade80);font-size:0.9rem;"><?= h($output) ?></pre>
          </div>
          <?php elseif ($smarty_error !== null): ?>
          <div class="terminal">
            <div style="color:var(--red);font-size:0.7rem;margin-bottom:8px;">Template Error</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--red);font-size:0.8rem;"><?= h($smarty_error) ?></pre>
          </div>
          <?php elseif ($name !== ''): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">// Rendered output</div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--green, #4ade80);font-size:0.9rem;">(empty output)</pre>
          </div>
          <?php else: ?>
          <p class="text-muted" style="font-size:0.85rem;">Enter a name above to receive your personalized greeting.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Engine Detection Table -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Engine Probe Reference</span></div>
        <div class="panel-body">
          <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:12px;">Use these payloads to identify the template engine. The response tells you which engine is running.</p>
          <table style="width:100%;font-size:0.8rem;border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid var(--border);color:var(--text-dim);">
                <th style="text-align:left;padding:6px 8px;">Payload</th>
                <th style="text-align:left;padding:6px 8px;">Twig</th>
                <th style="text-align:left;padding:6px 8px;">Smarty</th>
              </tr>
            </thead>
            <tbody style="color:var(--text-muted);">
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:6px 8px;font-family:var(--font-mono);color:var(--orange);">{{7*7}}</td>
                <td style="padding:6px 8px;color:var(--green, #4ade80);">49</td>
                <td style="padding:6px 8px;">literal <code>{{7*7}}</code></td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:6px 8px;font-family:var(--font-mono);color:var(--orange);">{7*7}</td>
                <td style="padding:6px 8px;">error</td>
                <td style="padding:6px 8px;">error / invalid tag</td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:6px 8px;font-family:var(--font-mono);color:var(--orange);">{$smarty.version}</td>
                <td style="padding:6px 8px;">error</td>
                <td style="padding:6px 8px;color:var(--green, #4ade80);">4.x version string</td>
              </tr>
              <tr>
                <td style="padding:6px 8px;font-family:var(--font-mono);color:var(--orange);">{'id'|shell_exec}</td>
                <td style="padding:6px 8px;">error</td>
                <td style="padding:6px 8px;color:var(--green, #4ade80);">command output</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// greeting.php</span><br>
            <span class="query">$smarty = new Smarty();<br>
<span style="color:var(--text-dim)">// No security policy — PHP built-ins usable as modifiers — DELIBERATELY VULNERABLE</span><br>
<br>
$name = $_GET[<span style="color:var(--orange)">'name'</span>];<br>
<br>
<span style="color:var(--text-dim)">// VULNERABLE: Smarty createTemplate with user input</span><br>
$tpl = $smarty-&gt;createTemplate(<span style="color:var(--orange)">'string:Hello ' . $name . '!'</span>);<br>
echo $smarty-&gt;fetch($tpl);</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Probe the engine — confirm it's Smarty, not Twig, using <code>{$smarty.version}</code>.</div>
          <div>2. Exploit Smarty's modifier system: when no security policy is set, any PHP built-in can be used as a modifier.</div>
          <div>3. Read <code>/flags/lab_s4.txt</code> using <code>{'/flags/lab_s4.txt'|file_get_contents}</code> or execute a command with <code>{'cat /flags/lab_s4.txt'|shell_exec}</code>.</div>
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
