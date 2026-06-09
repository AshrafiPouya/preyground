<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — Metacharacter Filter Bypass';
$lab_id = 'cmd_c2';
include __DIR__ . '/includes/header.php';
?>
<div class="interview-page">

  <div style="margin-bottom:8px;">
    <a href="/cmd_c2.php" class="btn btn-ghost btn-sm">&larr; Back to Lab C2</a>
  </div>

  <h1>Metacharacter Filter Bypass</h1>
  <p class="subtitle">Interview prep for Lab C2 — bypassing character blacklists in command injection.</p>

  <!-- 30-Second Answer -->
  <div class="interview-section">
    <h2>30-Second Answer</h2>
    <div class="thirty-sec">
      "Command injection is when user input reaches an OS shell unsanitized, letting an attacker append system commands via metacharacters. Filter bypass exploits the fact that there are more shell operators than any blacklist covers — removing <code>;</code>, <code>&amp;</code>, and <code>|</code> still leaves command substitution via <code>$()</code> and backticks."
    </div>
  </div>

  <!-- Common Interview Questions -->
  <div class="interview-section">
    <h2>Common Interview Questions</h2>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q1">
      <label class="qa-question" for="q1">What's the difference between command injection and code injection?</label>
      <div class="qa-answer">
        Command injection reaches the OS shell — you get system-level command execution. Code injection executes inside the application runtime (e.g. PHP <code>eval()</code>). Both are RCE vectors but at different layers. Command injection requires a shell boundary; code injection only requires the application to interpret user-controlled strings as code.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q2">
      <label class="qa-question" for="q2">How do you bypass character filters in command injection?</label>
      <div class="qa-answer">
        Common bypass techniques when specific characters are blocked:<br><br>
        <strong>Command substitution:</strong> <code>$(cmd)</code> or backtick <code>`cmd`</code> — if <code>;</code>, <code>&amp;</code>, <code>|</code> are blocked but these are not.<br>
        <strong>Newline:</strong> URL-encode as <code>%0a</code> — often missed by character-level filters.<br>
        <strong>IFS abuse:</strong> <code>${IFS}</code> replaces spaces when space is blocked.<br>
        <strong>Wildcards:</strong> <code>/???/c?t</code> resolves to <code>/bin/cat</code> without typing those words.<br>
        <strong>Brace expansion:</strong> <code>{cat,/etc/passwd}</code> in some shell contexts.<br>
        The takeaway: blacklisting is fundamentally flawed — use allowlists and parameterized APIs.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q3">
      <label class="qa-question" for="q3">How do you fix command injection?</label>
      <div class="qa-answer">
        Avoid shell commands entirely — use native library functions. If shelling out is required, use <code>escapeshellarg()</code> on every argument (not <code>escapeshellcmd()</code> — it leaves spaces, enabling argument injection). Apply a strict allowlist before passing any input near a shell. Never rely on blacklisting metacharacters.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q4">
      <label class="qa-question" for="q4">What is blind command injection?</label>
      <div class="qa-answer">
        Blind command injection is when the injected command executes but output is never returned in the response. Exploitation requires side-channels: timing delays (<code>; sleep 5</code>), out-of-band DNS/HTTP callbacks, or writing output to an accessible file path. It is equally dangerous — exfiltration is just slower.
      </div>
    </div>
  </div>

  <!-- Dev Fix -->
  <div class="interview-section">
    <h2>Dev Fix</h2>
    <p>Strip-based blacklists are always incomplete. The shell has dozens of ways to chain commands. The only reliable fix is parameterization plus allowlist validation.</p>
    <div class="terminal" style="margin-top:12px;">
      <span class="comment">// Wrong — blacklist is always incomplete</span><br>
      <span style="color:var(--red);">$host = str_replace([';','&amp;','|'], '', $_GET['host']);<br>
shell_exec("ping -c 2 " . $host); // $(id) still works</span><br><br>
      <span class="comment">// Correct — allowlist + escapeshellarg</span><br>
      <span style="color:var(--accent);">if (!preg_match('/^[a-zA-Z0-9.\-]+$/', $_GET['host'])) die("Invalid");<br>
shell_exec("ping -c 2 " . escapeshellarg($_GET['host']));</span>
    </div>
  </div>

  <!-- One-liners to Remember -->
  <div class="interview-section">
    <h2>One-Liners to Remember</h2>
    <div class="oneliner">127.0.0.1$(id)</div>
    <div class="oneliner">127.0.0.1`id`</div>
    <div class="oneliner">127.0.0.1%0aid  (URL-encoded newline)</div>
    <div class="oneliner">127.0.0.1$(cat /etc/passwd)</div>
  </div>

  <!-- Red Flags -->
  <div class="interview-section">
    <h2>Red Flags (Things NOT to Say)</h2>
    <div class="red-flag-item">
      <span>&#9888;</span>
      <span><strong>"Just filter more characters"</strong> — blacklisting is a losing game. The shell has too many separator and substitution mechanisms. Use allowlists and native APIs instead.</span>
    </div>
  </div>

  <div style="text-align:center;margin-top:48px;">
    <a href="/cmd_c2.php" class="btn btn-primary">Back to Lab C2 &rarr;</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
