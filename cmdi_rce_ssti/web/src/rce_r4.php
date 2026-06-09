<?php
// Lab R4 — Local File Inclusion → RCE (Intermediate)
// Mechanism: user-controlled include path + writable /tmp → LFI to RCE via path traversal
require_once __DIR__ . '/includes/helpers.php';

$lab_id    = 'r4';
$flag_file = 'lab_r4.txt';

$hints = [
    1 => "You choose which file the server includes — and included files get <em>executed</em> as PHP. Can you point it at a file that contains your code?",
    2 => "There's a note-saving feature. Notes are saved as PHP files in <code>/tmp/</code>. If you could include a note, its PHP content would execute.",
    3 => "Save a note containing <code>&lt;?php system('cat /flags/lab_r4.txt'); ?&gt;</code>, get the note ID from the response, then use path traversal: <code>?page=../../tmp/{note_id}.php</code>.",
];

$page         = '';
$page_content = null;
$page_error   = null;
$note_msg     = null;
$note_saved_id = null;

// Handle hint reveal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_hint'])) {
    reveal_hint($lab_id, (int)$_POST['reveal_hint']);
    header("Location: /rce_r4.php");
    exit;
}

// Handle flag submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    if (check_flag($_POST['flag'], $flag_file)) {
        mark_solved($lab_id);
    } else {
        $_SESSION[flag_error_key($lab_id)] = true;
    }
    header("Location: /rce_r4.php");
    exit;
}

// Handle reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    unset($_SESSION['payloads'][$lab_id]);
    header("Location: /rce_r4.php");
    exit;
}

// Feature B: Save note (creates attacker-controlled PHP file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_content'])) {
    $note = $_POST['note_content'];
    log_payload($lab_id, $note, 'note_content (saved to /tmp/)');
    $note_id = 'note_' . bin2hex(random_bytes(4));
    file_put_contents('/tmp/' . $note_id . '.php', $note);
    $note_saved_id = $note_id;
    $note_msg = "Note saved as: /tmp/{$note_id}.php";
}

// Feature A: Page viewer with LFI
$page = $_GET['page'] ?? 'home.php';
if ($page !== '') {
    log_payload($lab_id, $page, 'page parameter (LFI)');
    // DELIBERATELY VULNERABLE — user controls the path, file is included and PHP-executed
    $page_path = __DIR__ . '/pages/' . $page;
    if (file_exists($page_path)) {
        ob_start();
        include $page_path;
        $page_content = ob_get_clean();
    } else {
        $page_error = "Page not found: " . htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
    }
}

$solved         = is_solved($lab_id);
$hints_revealed = hints_revealed($lab_id);
$payload_log    = get_payload_log($lab_id);
$flag_error     = !empty($_SESSION[flag_error_key($lab_id)]);
unset($_SESSION[flag_error_key($lab_id)]);

$page_title = 'Lab R4 — LFI to RCE';
include __DIR__ . '/includes/header.php';
?>

<div class="page lab-page">

  <?php if ($solved): ?>
  <div class="solved-banner">
    <div class="solved-banner-icon">🎉</div>
    <div>
      <div class="solved-banner-text">Lab Complete!</div>
      <div class="solved-banner-sub">You chained a file upload with Local File Inclusion to achieve Remote Code Execution.</div>
    </div>
  </div>
  <?php endif; ?>

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-intermediate">Intermediate</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R4</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview · 🎯 <?= stars(4) ?> Hunt</span>
      </div>
      <h1>Local File Inclusion → RCE</h1>
      <p class="lab-header-desc">A template viewer includes pages by filename — with no path validation. A separate note-saving feature writes attacker-controlled content to <code>/tmp/</code>. Chain them together to achieve RCE.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r4_interview.php" class="btn btn-ghost btn-sm">Interview Prep</a>
      <form method="post" style="margin:0">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset payload log?')">Reset</button>
      </form>
    </div>
  </div>

  <div class="lab-body">
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Template Viewer -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Template Viewer</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">GET /rce_r4.php?page=<?= h($page) ?></span>
        </div>
        <div class="panel-body">
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
            <a href="/rce_r4.php?page=home.php" class="btn btn-ghost btn-sm">home.php</a>
            <a href="/rce_r4.php?page=about.php" class="btn btn-ghost btn-sm">about.php</a>
            <a href="/rce_r4.php?page=contact.php" class="btn btn-ghost btn-sm">contact.php</a>
          </div>
          <form method="get" action="/rce_r4.php" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
            <input type="text" name="page" value="<?= h($page) ?>" placeholder="Page name (e.g. home.php)" style="flex:1;min-width:260px;">
            <button type="submit" class="btn btn-primary btn-sm">View Page</button>
          </form>
          <div style="font-family:var(--font-mono);font-size:0.72rem;color:var(--text-dim);margin-bottom:10px;">
            Including: <code><?= h(__DIR__ . '/pages/' . $page) ?></code>
          </div>
          <?php if ($page_content !== null): ?>
          <div class="terminal">
            <div style="color:var(--text-dim);font-size:0.7rem;margin-bottom:8px;">Rendered output:</div>
            <div style="color:var(--text);font-size:0.85rem;"><?= $page_content ?></div>
          </div>
          <?php elseif ($page_error): ?>
          <div class="alert alert-error"><?= $page_error ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Save Note -->
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">Save Note</span>
          <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">POST note_content → /tmp/note_XXXX.php</span>
        </div>
        <div class="panel-body">
          <form method="post" action="/rce_r4.php" style="display:flex;flex-direction:column;gap:8px;">
            <textarea name="note_content" rows="4" placeholder="Note content..." style="font-family:var(--font-mono);font-size:0.85rem;resize:vertical;"></textarea>
            <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-start;">Save Note</button>
          </form>
          <?php if ($note_msg): ?>
          <div class="alert alert-success" style="margin-top:10px;">
            <?= h($note_msg) ?>
            <?php if ($note_saved_id): ?>
            <br><span style="font-family:var(--font-mono);font-size:0.78rem;">Include it with: <strong>?page=../../tmp/<?= h($note_saved_id) ?>.php</strong></span>
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
            <span class="comment">// Feature A — Page viewer (LFI)</span><br>
            <span class="query">$page = $_GET[<span style="color:var(--orange)">'page'</span>] ?? <span style="color:var(--orange)">'home'</span>;<br>
$page_path = __DIR__ . <span style="color:var(--orange)">'/pages/'</span> . $page; <span style="color:var(--text-dim)">// no sanitization</span><br>
if (file_exists($page_path)) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;include $page_path; <span style="color:var(--text-dim)">// PHP-executes the file</span><br>
}</span><br><br>
            <span class="comment">// Feature B — Note saving</span><br>
            <span class="query">$note_id = <span style="color:var(--orange)">'note_'</span> . bin2hex(random_bytes(4));<br>
file_put_contents(<span style="color:var(--orange)">'/tmp/'</span> . $note_id . <span style="color:var(--orange)">'.php'</span>, $_POST[<span style="color:var(--orange)">'note_content'</span>]);</span>
          </div>
        </div>
      </div>

      <!-- Objective -->
      <div class="panel">
        <div class="panel-header"><span class="panel-title">Objective</span></div>
        <div class="panel-body" style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
          <div>1. Use the note-saving feature to write PHP code to <code>/tmp/</code>.</div>
          <div>2. Use path traversal in the <code>?page=</code> parameter to include your note file.</div>
          <div>3. Read the flag at <code>/flags/lab_r4.txt</code> and submit it below.</div>
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
