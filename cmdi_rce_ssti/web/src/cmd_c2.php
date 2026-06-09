<?php
// Lab C2 — Filtered Metacharacters
// Vulnerability config: str_replace blacklist of ; & | — bypass with $() or backticks
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'c2';
$flag_file = 'lab_c2.txt';

$hints = [
    1 => "They removed <em>some</em> separators — but a shell has more ways to run commands than semicolons, ampersands, and pipes.",
    2 => "Command substitution <code>$(cmd)</code> and backticks <code>`cmd`</code> are not in their blacklist. What do these do in a shell?",
    3 => "Try: <code>127.0.0.1$(cat /flags/lab_c2.txt)</code> — the output of <code>cat</code> gets embedded in the ping command string.",
];

$host = '';
$output = null;
$blocked_chars = [';', '&', '|'];
$was_filtered = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /cmd_c2.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) mark_solved($lab_id);
    else $_SESSION[flag_error_key($lab_id)] = true;
    header("Location: /cmd_c2.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /cmd_c2.php"); exit;
}

if (isset($_GET['host']) && $_GET['host'] !== '') {
    $host = $_GET['host'];
    $original = $host;
    // DELIBERATELY VULNERABLE — naive blacklist; $() and backticks bypass it
    $host = str_replace([';', '&', '|'], '', $host);
    $was_filtered = ($host !== $original);
    log_payload($lab_id, $original, 'host parameter');
    $output = shell_exec("ping -c 2 -W 1 " . $host . " 2>&1");
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab C2 — Filtered Metacharacters';
include __DIR__ . '/includes/header.php';
?>
<div class="page lab-page">
  <?php if ($solved): ?><div class="solved-banner"><div class="solved-banner-icon">🎉</div><div><div class="solved-banner-text">Lab Complete!</div><div class="solved-banner-sub">You bypassed a character blacklist using command substitution.</div></div></div><?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-intermediate">Intermediate</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB C2</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview · 🎯 <?= stars(3) ?> Hunt</span>
      </div>
      <h1>Filtered Metacharacters</h1>
      <p class="lab-header-desc">Same ping tool, hardened with a blacklist that removes <code>;</code>, <code>&amp;</code>, and <code>|</code>. Not all metacharacters were blocked.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/cmd_c2_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0"><input type="hidden" name="action" value="reset"><button type="submit" class="btn btn-danger btn-sm">Reset</button></form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Network Diagnostics (Hardened)</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">Filter: strips ; &amp; |</span>
        </div>
        <div class="panel-body">
          <form method="get" action="/cmd_c2.php" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <input type="text" name="host" value="<?= h($_GET['host'] ?? '') ?>" placeholder="Enter host" style="flex:1;min-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm">Ping</button>
          </form>
          <?php if ($was_filtered): ?>
          <div class="waf-status waf-blocked">🛡 Filter applied — some characters were removed before execution</div>
          <?php endif; ?>
          <?php if ($output !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">$ ping -c 2 -W 1 <?= h($host) ?></div>
            <pre style="margin:0;white-space:pre-wrap;color:var(--text);font-size:0.8rem;"><?= h($output) ?></pre>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// Filter in place — but incomplete</span><br>
            <span class="query">$host = str_replace(<span style="color:var(--orange)">[';','&amp;','|']</span>, <span style="color:var(--orange)">''</span>, $_GET[<span style="color:var(--orange)">'host'</span>]);<br>
$output = shell_exec(<span style="color:var(--orange)">"ping -c 2 -W 1 "</span> . $host);</span>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header"><span class="panel-title">Active Filter</span></div>
        <div class="panel-body">
          <div class="blocked-terms">
            <?php foreach ($blocked_chars as $c): ?>
            <span class="blocked-term"><?= h($c) ?></span>
            <?php endforeach; ?>
          </div>
          <p style="font-size:0.8rem;color:var(--text-dim);margin-top:8px;">These characters are stripped. Others are not.</p>
        </div>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px;">
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Submit Flag</span></div>
        <div class="panel-body">
          <?php if ($flag_error): ?><div class="alert alert-error" data-dismiss="4000">Incorrect flag.</div><?php endif; ?>
          <?php if ($solved): ?><div class="alert alert-success">Flag accepted!</div>
          <?php else: ?>
          <form method="post" class="flag-form">
            <input class="flag-input" type="text" name="flag" placeholder="HUNT{...}" autocomplete="off">
            <button type="submit" class="btn btn-primary btn-full">Submit</button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header"><span class="panel-title">Hints</span><span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= count($hints_revealed) ?>/3</span></div>
        <div class="panel-body"><div class="hints-list">
          <?php for ($i = 1; $i <= 3; $i++): ?>
          <div class="hint-item">
            <div class="hint-header">Hint <?= $i ?><?php if (!in_array($i, $hints_revealed, true)): ?>
            <form method="post" style="margin:0"><input type="hidden" name="reveal_hint" value="<?= $i ?>"><button type="submit" class="btn btn-ghost btn-sm">Reveal</button></form>
            <?php endif; ?></div>
            <?php if (in_array($i, $hints_revealed, true)): ?><div class="hint-content"><?= $hints[$i] ?></div><?php endif; ?>
          </div>
          <?php endfor; ?>
        </div></div>
      </div>

      <div class="panel">
        <div class="panel-header"><span class="panel-title">Payload Log</span><span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= count($payload_log) ?></span></div>
        <div class="panel-body"><div class="payload-log">
          <?php if ($payload_log): foreach (array_slice($payload_log, 0, 10) as $e): ?>
          <div class="log-entry" title="Click to copy"><div class="log-payload"><?= h($e['payload']) ?></div><div class="log-context"><?= h($e['context']) ?></div></div>
          <?php endforeach; else: ?><div class="log-empty">No payloads yet.</div><?php endif; ?>
        </div></div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
