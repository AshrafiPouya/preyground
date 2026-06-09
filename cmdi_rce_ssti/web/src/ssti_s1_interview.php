<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'S1 Interview Prep — SSTI Detection';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="interview-page">

    <div style="margin-bottom:24px;">
      <a href="/ssti_s1.php" style="font-size:0.82rem;color:var(--text-muted);">&larr; Back to Lab S1</a>
      &nbsp;&nbsp;
      <a href="/index.php" style="font-size:0.82rem;color:var(--text-muted);">Hub</a>
    </div>

    <div class="interview-section">
      <h1 style="font-size:1.4rem;margin-bottom:6px;">Lab S1 — SSTI Detection &amp; Basic Evaluation</h1>
      <p style="color:var(--text-muted);font-size:0.875rem;">Interview Prep · Server-Side Template Injection</p>
    </div>

    <!-- 30-Second Explanation -->
    <div class="interview-section">
      <h2>30-Second Explanation</h2>
      <div class="thirty-sec">
        SSTI is when user input is embedded in a server-side template and evaluated by the engine, letting an attacker run template expressions — often escalating to RCE. When an app concatenates user input directly into a template string instead of passing it as a safe variable, any template syntax in the input is interpreted and executed by the engine.
      </div>
    </div>

    <!-- Common Interview Questions -->
    <div class="interview-section">
      <h2>Common Interview Questions</h2>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s1q1">
        <label class="qa-question" for="s1q1">How do you detect SSTI?</label>
        <div class="qa-answer">
          The standard detection technique is the <strong>math probe</strong>: submit <code>{{7*7}}</code> as input. If the response contains <code>49</code> rather than the literal string <code>{{7*7}}</code>, the template engine evaluated your input — confirming SSTI. Use engine-specific probe payloads to fingerprint which engine is running: <code>{{7*7}}</code> for Twig/Jinja2, <code>{$smarty.version}</code> for Smarty, <code>${7*7}</code> for FreeMarker, <code>&lt;%= 7*7 %&gt;</code> for ERB.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s1q2">
        <label class="qa-question" for="s1q2"><code>{{7*7}}</code> vs <code>${7*7}</code> vs <code>&lt;%= 7*7 %&gt;</code> — what do these identify?</label>
        <div class="qa-answer">
          Each syntax fingerprints a different engine family:
          <ul style="margin-top:8px;padding-left:1.2em;line-height:1.9;">
            <li><code>{{7*7}}</code> → <strong>Jinja2</strong> (Python) or <strong>Twig</strong> (PHP) — returns <code>49</code></li>
            <li><code>${7*7}</code> → <strong>FreeMarker</strong> or <strong>Thymeleaf</strong> — returns <code>49</code></li>
            <li><code>&lt;%= 7*7 %&gt;</code> → <strong>ERB</strong> (Ruby) or <strong>EJS</strong> (Node) — returns <code>49</code></li>
            <li><code>{$smarty.version}</code> → <strong>Smarty</strong> — returns version string</li>
            <li><code>{{7*'7'}}</code> → distinguishes Jinja2 (returns <code>49</code>) from Twig (returns <code>7777777</code>)</li>
          </ul>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s1q3">
        <label class="qa-question" for="s1q3">What is the difference between SSTI and XSS?</label>
        <div class="qa-answer">
          <strong>XSS</strong> injects into client-side HTML or JavaScript that is evaluated <em>in the user's browser</em>. The impact is browser-side: stealing cookies, redirecting, defacement. <strong>SSTI</strong> injects into server-side templates evaluated <em>on the server</em>. The impact is server-side: reading server files, environment variables, executing OS commands (RCE), pivoting to internal systems. SSTI is generally much higher severity than XSS. A common junior mistake in interviews is conflating the two.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s1q4">
        <label class="qa-question" for="s1q4">What is the root cause of SSTI?</label>
        <div class="qa-answer">
          SSTI occurs when a developer uses string concatenation to build a template from user input instead of using the template engine's safe rendering API. The correct pattern is to pass user data as a <strong>variable</strong> into a <strong>static template</strong>:<br><br>
          <strong>Vulnerable:</strong> <code>$twig->createTemplate("Hello " . $name . "!")</code><br>
          <strong>Safe:</strong> <code>$twig->render('greeting.twig', ['name' => $name])</code><br><br>
          In the safe version, <code>$name</code> is data — it can never contain executable template syntax because the template structure is fixed.
        </div>
      </div>
    </div>

    <!-- One-Liners -->
    <div class="interview-section">
      <h2>Detection One-Liners</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Basic SSTI probe (Twig / Jinja2)</div>
          <div class="oneliner">{{7*7}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Distinguish Jinja2 vs Twig</div>
          <div class="oneliner">{{7*'7'}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Access template variable (S1-style)</div>
          <div class="oneliner">{{flag}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Smarty probe</div>
          <div class="oneliner">{$smarty.version}</div>
        </div>
      </div>
    </div>

    <!-- Developer Fix -->
    <div class="interview-section">
      <h2>Developer Fix</h2>
      <div style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;">
        <p style="margin-bottom:10px;"><strong>Never concatenate user input into a template string.</strong> Pass data as variables to a static template:</p>
        <div class="terminal" style="margin-bottom:12px;">
          <span class="comment">// VULNERABLE</span><br>
          <span style="color:var(--red);">$template = $twig->createTemplate("Hello " . $name . "!");</span><br>
          <br>
          <span class="comment">// SAFE — template is static, name is data</span><br>
          <span style="color:var(--green, #4ade80);">$output = $twig->render('greeting.html.twig', ['name' => $name]);</span>
        </div>
        <p>Additional mitigations: enable the Twig <strong>Sandbox extension</strong> with a strict allowlist if user-defined templates are a business requirement; validate and sanitize all inputs; follow the principle of least privilege for the web process user.</p>
      </div>
    </div>

    <!-- Red Flags -->
    <div class="interview-section">
      <h2>Interview Red Flags</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div class="red-flag-item">&#9888; Confusing SSTI with XSS — one is server-side, one is client-side. A frequent junior mistake.</div>
        <div class="red-flag-item">&#9888; Not knowing what template engines are or how they differ from raw string formatting.</div>
        <div class="red-flag-item">&#9888; Saying "just sanitize the input" without understanding why concatenation into a template is fundamentally unsafe.</div>
        <div class="red-flag-item">&#9888; Not mentioning the math-probe technique as the first detection step.</div>
      </div>
    </div>

    <div style="margin-top:24px;">
      <a href="/ssti_s1.php" class="btn btn-primary btn-sm">Go to Lab S1</a>
      &nbsp;
      <a href="/index.php" class="btn btn-ghost btn-sm">Hub</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
