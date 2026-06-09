<?php
// Lab S5 — Capstone: SSTI in the Wild (Expert)
// Vulnerability: multi-field form, only 'bio' is Twig-rendered, {{ filtered but {% is not
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

$lab_id   = 's5';
$flag_file = 'lab_s5.txt';

$hints = [
    1 => "Not every input renders through a template engine. Test each field for template evaluation — look for the one where <code>{{7*7}}</code> should produce 49, if the filter allowed it.",
    2 => "The <code>{{</code> delimiter is filtered — but template engines have more than one type of tag. What tags use <code>{%</code> instead of <code>{{</code>?",
    3 => "The template already contains <code>{{output}}</code>. Use <code>{%set output=...%}</code> (not filtered) to set that variable with the result of your RCE gadget.",
];

// Decoy inputs — NOT templated
$username = h($_GET['username'] ?? '');
$email    = h($_GET['email'] ?? '');
$website  = h($_GET['website'] ?? '');

// Only 'bio' is SSTI-vulnerable
$bio_raw    = $_GET['bio'] ?? '';
$output     = null;
$twig_error = null;
$ran        = false;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /ssti_s5.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /ssti_s5.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /ssti_s5.php");
    exit;
}

// Process profile preview
if (isset($_GET['bio']) || isset($_GET['username'])) {
    $ran = true;

    if ($bio_raw !== '') {
        log_payload($lab_id, $bio_raw, 'bio parameter');
    }

    $loader = new \Twig\Loader\ArrayLoader([]);
    $twig   = new \Twig\Environment($loader);

    if ($bio_raw !== '') {
        // FILTER: blocks {{ and }} — but NOT {% %}
        $bio_filtered = str_replace(['{{', '}}'], '', $bio_raw);
        try {
            // The template already has {{output}} — set it via {%set%}
            $template = $twig->createTemplate(
                "<div class=\"profile-bio\">" . $bio_filtered . "</div>" .
                "<div class=\"computed\">Computed: {{output}}</div>"
            );
            $output = $template->render(['output' => '']);
        } catch (\Twig\Error\Error $e) {
            $twig_error = $e->getMessage();
        }
    }
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab S5 — Capstone: SSTI in the Wild';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You identified the SSTI sink among decoy inputs, bypassed the <code>{{</code> filter using <code>{%set%}</code>, and achieved RCE in a realistic multi-field scenario.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-expert">Expert</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB S5</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview · 🎯 <?= stars(5) ?> Hunt</span>
      </div>
      <h1>Capstone: SSTI in the Wild</h1>
      <p class="lab-header-desc">A user profile editor with four input fields. Only one is template-rendered. The <code>{{</code> delimiter is filtered — but not all Twig tags use <code>{{</code>. Find the sink, bypass the WAF, achieve RCE.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/ssti_s5_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset payload log?')">Reset</button>
      </form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Simulated App: Profile Editor -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">User Profile Editor</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET /profile</span>
        </div>
        <div class="panel-body">
          <!-- WAF status -->
          <div class="waf-status" style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:4px;background:rgba(255,100,0,0.1);border:1px solid rgba(255,100,0,0.3);font-size:0.75rem;color:var(--orange);margin-bottom:14px;">
            <span>&#9679;</span>
            <span>Active filter: strips <code>{{</code> and <code>}}</code> from bio field</span>
          </div>

          <form method="get" action="/ssti_s5.php" style="display:flex;flex-direction:column;gap:12px;max-width:520px;margin-bottom:16px;">
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:4px;">Username</label>
              <input type="text" name="username" value="<?= htmlspecialchars($_GET['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. alice_h4ck3r" style="width:100%;box-sizing:border-box;">
            </div>
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:4px;">Email</label>
              <input type="text" name="email" value="<?= htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. alice@example.com" style="width:100%;box-sizing:border-box;">
            </div>
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:4px;">Website</label>
              <input type="text" name="website" value="<?= htmlspecialchars($_GET['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. https://alice.dev" style="width:100%;box-sizing:border-box;">
            </div>
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:4px;">Bio <span style="color:var(--orange);font-size:0.7rem;">(template-rendered)</span></label>
              <textarea name="bio" rows="3" placeholder="Tell us about yourself..." style="width:100%;box-sizing:border-box;font-family:var(--font-mono);font-size:0.82rem;"><?= htmlspecialchars($_GET['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-start;">Preview Profile</button>
          </form>

          <!-- Profile Preview -->
          <?php if ($ran): ?>
          <div style="border:1px solid var(--border);border-radius:6px;padding:16px;background:var(--bg-card);margin-top:8px;">
            <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:12px;font-family:var(--font-mono);">// Profile Preview</div>
            <div style="display:grid;gap:6px;font-size:0.85rem;">
              <div><span style="color:var(--text-dim);width:80px;display:inline-block;">Username</span> <span style="color:var(--text);"><?= $username !== '' ? $username : '<em style="color:var(--text-dim)">(empty)</em>' ?></span></div>
              <div><span style="color:var(--text-dim);width:80px;display:inline-block;">Email</span> <span style="color:var(--text);"><?= $email !== '' ? $email : '<em style="color:var(--text-dim)">(empty)</em>' ?></span></div>
              <div><span style="color:var(--text-dim);width:80px;display:inline-block;">Website</span> <span style="color:var(--text);"><?= $website !== '' ? $website : '<em style="color:var(--text-dim)">(empty)</em>' ?></span></div>
              <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);">
                <div style="color:var(--text-dim);font-size:0.75rem;margin-bottom:6px;">Bio (Twig-rendered):</div>
                <?php if ($output !== null): ?>
                <div style="color:var(--green, #4ade80);font-family:var(--font-mono);font-size:0.82rem;white-space:pre-wrap;"><?= $output ?></div>
                <?php elseif ($twig_error !== null): ?>
                <div style="color:var(--red);font-family:var(--font-mono);font-size:0.78rem;white-space:pre-wrap;"><?= h($twig_error) ?></div>
                <?php else: ?>
                <em style="color:var(--text-dim);font-size:0.82rem;">(empty bio)</em>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php else: ?>
          <p class="text-muted" style="font-size:0.85rem;">Fill in the form above and click Preview Profile to see how your profile will look.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// profile.php</span><br>
            <span class="query"><span style="color:var(--text-dim)">// Decoy inputs — NOT templated (safe)</span><br>
$username = h($_GET[<span style="color:var(--orange)">'username'</span>]);<br>
$email    = h($_GET[<span style="color:var(--orange)">'email'</span>]);<br>
$website  = h($_GET[<span style="color:var(--orange)">'website'</span>]);<br>
<br>
<span style="color:var(--text-dim)">// Only 'bio' is SSTI-vulnerable</span><br>
$bio_raw = $_GET[<span style="color:var(--orange)">'bio'</span>];<br>
<br>
<span style="color:var(--text-dim)">// FILTER: blocks {{ and }} — but NOT {% %}</span><br>
$bio_filtered = str_replace([<span style="color:var(--orange)">'{{', '}}'</span>], <span style="color:var(--orange)">''</span>, $bio_raw);<br>
<br>
<span style="color:var(--text-dim)">// The template already has {{output}} — set it via {%set%}</span><br>
$template = $twig-&gt;createTemplate(<br>
&nbsp;&nbsp;<span style="color:var(--orange)">"&lt;div&gt;" . $bio_filtered . "&lt;/div&gt;"</span> .<br>
&nbsp;&nbsp;<span style="color:var(--orange)">"&lt;div&gt;Computed: {{output}}&lt;/div&gt;"</span><br>
);<br>
$output = $template-&gt;render([<span style="color:var(--orange)">'output'</span> =&gt; <span style="color:var(--orange)">''</span>]);</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Identify which field is template-rendered (probe with <code>{{7*7}}</code> — the filter will strip it; think about what that implies).</div>
          <div>2. Find a Twig tag type that is NOT filtered by the <code>{{</code>/<code>}}</code> strip.</div>
          <div>3. The template already contains <code>{{output}}</code>. Use <code>{%set output=RCE_payload%}</code> to populate it.</div>
          <div>4. Read <code>/flags/lab_s5.txt</code> and submit the flag below.</div>
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
