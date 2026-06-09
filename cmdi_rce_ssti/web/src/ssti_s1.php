<?php
// Lab S1 — SSTI Detection & Basic Evaluation (Beginner)
// Vulnerability: user input concatenated into Twig template string
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

$lab_id   = 's1';
$flag_file = 'lab_s1.txt';

$hints = [
    1 => "Your name is being rendered by a template engine, not just printed. Template engines can do more than display text — they can evaluate expressions.",
    2 => "If <code>{{7*7}}</code> comes back as <code>49</code> (not the literal string <code>{{7*7}}</code>), the engine is evaluating your input. This is the SSTI detection technique.",
    3 => "The flag is passed as a template variable named <code>flag</code>. Once you confirm evaluation with <code>{{7*7}}</code>, try accessing <code>{{flag}}</code> directly.",
];

$name       = '';
$output     = null;
$twig_error = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /ssti_s1.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /ssti_s1.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /ssti_s1.php");
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
        // Flag is a template variable — accessible via {{flag}}
        $output = $template->render(['flag' => trim(file_get_contents('/flags/lab_s1.txt'))]);
    } catch (\Twig\Error\Error $e) {
        $twig_error = $e->getMessage();
    }
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab S1 — SSTI Detection & Basic Evaluation';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You detected Server-Side Template Injection and accessed a flag variable through the Twig template engine.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-beginner">Beginner</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB S1</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview · 🎯 <?= stars(2) ?> Hunt</span>
      </div>
      <h1>SSTI Detection &amp; Basic Evaluation</h1>
      <p class="lab-header-desc">A personalized greeting app concatenates your name directly into a Twig template string. Probe the input to confirm template evaluation, then access the <code>flag</code> variable exposed in the template context.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/ssti_s1_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
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
          <form method="get" action="/ssti_s1.php" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
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
<span style="color:var(--text-dim)">// Flag is a template variable — accessible via {{flag}}</span><br>
$output = $template-&gt;render([<span style="color:var(--orange)">'flag'</span> =&gt; file_get_contents(<span style="color:var(--orange)">'/flags/lab_s1.txt'</span>)]);<br>
echo $output;</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Confirm SSTI by injecting <code>{{7*7}}</code> as your name — if the output shows <code>49</code>, the engine is evaluating your input.</div>
          <div>2. The flag is passed as a template variable. Access it using the correct Twig expression.</div>
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
