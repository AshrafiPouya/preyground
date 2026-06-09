<?php
// Lab R6 — Capstone: Chained RCE (Expert)
// Mechanism: blacklist upload filter (.php only) + disable_functions bypass via proc_open/file_get_contents
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'r6';
$flag_file = 'lab_r6.txt';

$hints = [
    1 => "The upload filter blocks <code>.php</code> — but PHP can execute many other extensions. What extension is missing from the blacklist?",
    2 => "Once your file runs, <code>system()</code> and <code>exec()</code> are disabled. PHP has other ways to read files and run processes — check what's NOT in the disable list.",
    3 => "Upload as <code>.phtml</code> (not in the blacklist). Inside, use <code>file_get_contents('/flags/lab_r6.txt')</code> or <code>proc_open()</code> — neither is blocked.",
];

$upload_error   = null;
$uploaded_file  = null;
$exec_output    = null;
$exec_error     = null;
$exec_file      = null;

$blocked_extensions  = ['.php'];
$disabled_functions  = ['system', 'exec', 'shell_exec', 'passthru', 'popen'];
$uploads_dir         = __DIR__ . '/uploads_r6/';

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /rce_r6.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /rce_r6.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    // Remove uploaded files
    foreach (glob($uploads_dir . '*') as $f) {
        if (is_file($f)) unlink($f);
    }
    header("Location: /rce_r6.php");
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    log_payload($lab_id, $_FILES['avatar']['name'], 'file upload (extension: .' . $ext . ')');
    // DELIBERATELY VULNERABLE — blocks .php only (case-sensitive blacklist, misses .phtml, .php5, .phar, etc.)
    if (in_array('.' . $ext, $blocked_extensions)) {
        $upload_error = "File type not allowed: ." . htmlspecialchars($ext, ENT_QUOTES, 'UTF-8');
    } else {
        $name = basename($_FILES['avatar']['name']);
        $dest = $uploads_dir . $name;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
            $uploaded_file = $name;
        } else {
            $upload_error = "Failed to move uploaded file.";
        }
    }
}

// Handle execute request
if (isset($_GET['exec']) && $_GET['exec'] !== '') {
    $safe_name = basename($_GET['exec']);
    $file_path = $uploads_dir . $safe_name;
    log_payload($lab_id, $safe_name, 'exec (php -d disable_functions=...)');
    if (file_exists($file_path)) {
        $exec_file = $safe_name;
        // disable_functions blocks system,exec,shell_exec,passthru,popen — but NOT proc_open or file_get_contents
        $exec_output = shell_exec(
            'php -d disable_functions=system,exec,shell_exec,passthru,popen ' .
            escapeshellarg($file_path) . ' 2>&1'
        );
    } else {
        $exec_error = "File not found in uploads: " . htmlspecialchars($safe_name, ENT_QUOTES, 'UTF-8');
    }
}

// List uploaded files
$uploaded_files = array_filter(array_map('basename', glob($uploads_dir . '*') ?: []), fn($f) => $f !== '');

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab R6 — Chained RCE (Capstone)';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You chained an extension filter bypass with a disable_functions bypass to achieve full RCE. Expert-level technique.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-expert">Expert</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R6</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview · 🎯 <?= stars(5) ?> Hunt</span>
      </div>
      <h1>Capstone: Chained RCE</h1>
      <p class="lab-header-desc">A hardened support portal blocks <code>.php</code> uploads and runs submitted files with <code>disable_functions</code> set. Two weaknesses remain: an incomplete extension blacklist and a gap in the disabled-functions list. Chain both to read the flag.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r6_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset and delete uploads?')">Reset</button>
      </form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Upload Panel -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Avatar Upload</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">POST multipart/form-data</span>
        </div>
        <div class="panel-body">
          <div style="display:flex;gap:8px;margin-bottom:12px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:0.8rem;color:var(--text-dim);">Blocked extensions:</span>
            <?php foreach ($blocked_extensions as $be): ?>
            <span style="font-family:var(--font-mono);font-size:0.78rem;background:rgba(239,68,68,0.12);color:var(--red);padding:2px 8px;border-radius:4px;"><?= h($be) ?></span>
            <?php endforeach; ?>
          </div>
          <form method="post" action="/rce_r6.php" enctype="multipart/form-data" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <input type="file" name="avatar" style="flex:1;min-width:200px;">
            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
          </form>
          <?php if ($upload_error): ?>
          <div class="alert alert-error" style="margin-top:10px;"><?= $upload_error ?></div>
          <?php endif; ?>
          <?php if ($uploaded_file): ?>
          <div class="alert alert-success" style="margin-top:10px;">
            Uploaded: <code><?= h($uploaded_file) ?></code>
            — <a href="/rce_r6.php?exec=<?= urlencode($uploaded_file) ?>" class="btn btn-ghost btn-sm" style="display:inline;">Execute</a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Uploaded Files List -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Uploaded Files</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= count($uploaded_files) ?> file(s)</span>
        </div>
        <div class="panel-body">
          <?php if ($uploaded_files): ?>
          <div style="display:flex;flex-direction:column;gap:6px;">
            <?php foreach ($uploaded_files as $uf): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:6px 8px;background:var(--surface-alt);border-radius:4px;">
              <span style="font-family:var(--font-mono);font-size:0.8rem;flex:1;color:var(--text);"><?= h($uf) ?></span>
              <a href="/rce_r6.php?exec=<?= urlencode($uf) ?>" class="btn btn-ghost btn-sm">Execute</a>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-muted" style="font-size:0.85rem;">No files uploaded yet.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Execution Output -->
      <?php if ($exec_output !== null || $exec_error): ?>
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Execution Output</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= h($exec_file ?? '') ?></span>
        </div>
        <div class="panel-body">
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">
              $ php -d disable_functions=system,exec,shell_exec,passthru,popen <?= h($exec_file ?? '') ?>
            </div>
            <?php if ($exec_error): ?>
            <pre style="margin:0;white-space:pre-wrap;color:var(--red);font-size:0.8rem;"><?= h($exec_error) ?></pre>
            <?php else: ?>
            <pre style="margin:0;white-space:pre-wrap;color:var(--text);font-size:0.8rem;"><?= h((string)$exec_output) ?></pre>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// Upload filter — blocks .php only (incomplete blacklist)</span><br>
            <span class="query">$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));<br>
if (in_array(<span style="color:var(--orange)">'.'</span> . $ext, [<span style="color:var(--orange)">' .php'</span>])) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;die(<span style="color:var(--orange)">"Not allowed"</span>); <span style="color:var(--text-dim)">// .phtml, .phar, .php5 pass through</span><br>
}<br>
move_uploaded_file($tmp, $uploads_dir . $name);</span><br><br>
            <span class="comment">// Execute — disable_functions leaves proc_open unblocked</span><br>
            <span class="query">$output = shell_exec(<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:var(--orange)">'php -d disable_functions=system,exec,shell_exec,passthru,popen '</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;. escapeshellarg($file_path) . <span style="color:var(--orange)">' 2>&amp;1'</span><br>
);</span>
          </div>
          <div style="margin-top:12px;font-size:0.8rem;color:var(--text-dim);">
            Disabled: <span style="font-family:var(--font-mono);">system, exec, shell_exec, passthru, popen</span>
            <span style="margin-left:10px;color:var(--green);">NOT disabled: <code>proc_open</code>, <code>file_get_contents</code></span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Bypass the extension filter by uploading a file with an extension not in the blacklist.</div>
          <div>2. Craft file contents that read <code>/flags/lab_r6.txt</code> without using the blocked functions.</div>
          <div>3. Use the Execute link to run your uploaded file and capture the flag.</div>
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
