<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — Classic Command Injection';
$lab_id = 'cmd_c1';
include __DIR__ . '/includes/header.php';
?>
<div class="interview-page">

  <div style="margin-bottom:8px;">
    <a href="/cmd_c1.php" class="btn btn-ghost btn-sm">&larr; Back to Lab C1</a>
  </div>

  <h1>Classic Command Injection</h1>
  <p class="subtitle">Interview prep for Lab C1 — what to say, what to know, what not to say.</p>

  <!-- 30-Second Answer -->
  <div class="interview-section">
    <h2>30-Second Answer</h2>
    <div class="thirty-sec">
      "Command injection is when user input reaches an OS shell unsanitized, letting an attacker append their own system commands using shell metacharacters."
    </div>
  </div>

  <!-- Common Interview Questions -->
  <div class="interview-section">
    <h2>Common Interview Questions</h2>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q1">
      <label class="qa-question" for="q1">What's the difference between command injection and code injection?</label>
      <div class="qa-answer">
        Command injection runs OS commands in a shell process — you're talking directly to the operating system. Code injection executes within the application's own runtime (for example, <code>eval()</code> in PHP or Python). Both are RCE vectors, but at different levels of the stack. Command injection gives you a shell; code injection gives you application-level execution that may or may not escalate further.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q2">
      <label class="qa-question" for="q2">Which characters enable command injection?</label>
      <div class="qa-answer">
        The most common are: <code>;</code> (sequence), <code>|</code> (pipe), <code>&amp;</code> (background / AND), <code>&amp;&amp;</code> (logical AND), backtick <code>`cmd`</code> (command substitution), <code>$(cmd)</code> (command substitution), newline <code>\n</code> — and in some contexts <code>&lt;</code> <code>&gt;</code> <code>&gt;&gt;</code> for I/O redirection. None of these needs to be present — some bypasses use only spaces and flag injection.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q3">
      <label class="qa-question" for="q3">How do you fix command injection?</label>
      <div class="qa-answer">
        The safest fix is to avoid shell commands entirely — use native library functions (ping via ICMP sockets, DNS via resolver libraries, etc.). If shelling out is truly unavoidable, use parameterized APIs: call <code>escapeshellarg()</code> on <em>every</em> argument, never <code>escapeshellcmd()</code> alone (it leaves spaces, enabling argument injection). Additionally, validate input against a strict allowlist (e.g. only allow IP address format) before passing it anywhere near a shell.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q4">
      <label class="qa-question" for="q4">What is blind command injection?</label>
      <div class="qa-answer">
        Blind command injection is when the injected command executes server-side but its output is never returned in the HTTP response. Confirmation requires side-channels: timing (inject <code>; sleep 5</code> and measure delay), out-of-band (DNS/HTTP callbacks to an attacker-controlled host), or writing output to an accessible file path. It is just as dangerous as reflected injection — exfiltration is still possible, just slower.
      </div>
    </div>
  </div>

  <!-- Dev Fix -->
  <div class="interview-section">
    <h2>Dev Fix</h2>
    <p>Never shell out with user input. Use native library functions — for ping, open an ICMP socket; for DNS, call the resolver API. If shelling is genuinely required, use <code>escapeshellarg()</code> on every argument and validate input against an allowlist before touching the shell at all.</p>
    <div class="terminal" style="margin-top:12px;">
      <span class="comment">// Wrong — raw input in shell</span><br>
      <span style="color:var(--red);">shell_exec("ping -c 1 " . $_GET['host']);</span><br><br>
      <span class="comment">// Correct — escapeshellarg wraps each argument</span><br>
      <span style="color:var(--accent);">$host = escapeshellarg($_GET['host']);<br>
shell_exec("ping -c 1 " . $host);</span><br><br>
      <span class="comment">// Best — allowlist first, then escape</span><br>
      <span style="color:var(--accent);">if (!preg_match('/^[a-zA-Z0-9.\-]+$/', $_GET['host'])) die("Invalid host");<br>
shell_exec("ping -c 1 " . escapeshellarg($_GET['host']));</span>
    </div>
  </div>

  <!-- One-liners to Remember -->
  <div class="interview-section">
    <h2>One-Liners to Remember</h2>
    <div class="oneliner">; cat /etc/passwd</div>
    <div class="oneliner">&amp;&amp; id</div>
    <div class="oneliner">| whoami</div>
    <div class="oneliner">$(cat /etc/passwd)</div>
  </div>

  <!-- Red Flags -->
  <div class="interview-section">
    <h2>Red Flags (Things NOT to Say)</h2>
    <div class="red-flag-item">
      <span>&#9888;</span>
      <span><strong>"Just remove semicolons"</strong> — many other metacharacters bypass that filter: pipes, backticks, <code>$()</code>, newlines, and more. Blacklisting is a losing game.</span>
    </div>
  </div>

  <div style="text-align:center;margin-top:48px;">
    <a href="/cmd_c1.php" class="btn btn-primary">Back to Lab C1 &rarr;</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
