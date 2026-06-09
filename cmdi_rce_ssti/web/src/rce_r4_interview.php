<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — LFI to RCE';
$lab_id     = 'r4';
include __DIR__ . '/includes/header.php';
?>

<div class="page interview-page">

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-intermediate">Intermediate</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R4 — INTERVIEW PREP</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(4) ?> Interview relevance</span>
      </div>
      <h1>LFI → RCE: Interview Prep</h1>
      <p class="lab-header-desc">How to explain Local File Inclusion to RCE escalation clearly and confidently in a security interview.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r4.php" class="btn btn-ghost btn-sm">Back to Lab</a>
    </div>
  </div>

  <div class="interview-body" style="display:flex;flex-direction:column;gap:20px;max-width:860px;">

    <!-- 30-second answer -->
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">30-Second Answer</span>
        <span style="font-size:0.7rem;color:var(--text-dim);font-family:var(--font-mono)">memorise this</span>
      </div>
      <div class="panel-body">
        <blockquote style="border-left:3px solid var(--accent);padding-left:16px;margin:0;font-size:0.925rem;color:var(--text);line-height:1.7;">
          "Local File Inclusion (LFI) is when user input reaches a PHP <code>include</code> or <code>require</code> call, letting an attacker include arbitrary files from the server. LFI becomes Remote Code Execution when the attacker can include a file they control that contains PHP code — for example by uploading a file, by poisoning a log with PHP in the User-Agent header, or by using PHP stream wrappers like <code>php://input</code>."
        </blockquote>
      </div>
    </div>

    <!-- Common Qs -->
    <div class="panel">
      <div class="panel-header"><span class="panel-title">Common Interview Questions</span></div>
      <div class="panel-body" style="display:flex;flex-direction:column;gap:18px;">

        <div class="interview-qa">
          <div class="interview-q">How does LFI become RCE?</div>
          <div class="interview-a">
            LFI executes any file you include as PHP. Three main escalation paths:
            <ul style="margin:8px 0 0;padding-left:20px;display:flex;flex-direction:column;gap:6px;">
              <li><strong>File upload + include</strong> — upload a file containing <code>&lt;?php system($_GET['c']); ?&gt;</code>, then LFI to that file's path.</li>
              <li><strong>Log poisoning</strong> — inject PHP into a log file (e.g. via a malicious User-Agent), then LFI to the log path.</li>
              <li><strong>PHP stream wrappers</strong> — <code>php://input</code> treats POST body as PHP code; <code>data://text/plain,&lt;?php...?&gt;</code> passes inline PHP.</li>
            </ul>
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">What is log poisoning?</div>
          <div class="interview-a">
            Send a request with PHP code embedded in a header the server logs — typically the <code>User-Agent</code>. Apache writes it verbatim to <code>access.log</code>. Then use LFI to include the log file: PHP parses the log and executes the embedded code. Example User-Agent: <code>&lt;?php system($_GET['cmd']); ?&gt;</code>
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">What are PHP stream wrappers relevant to LFI?</div>
          <div class="interview-a">
            <ul style="margin:4px 0 0;padding-left:20px;display:flex;flex-direction:column;gap:6px;">
              <li><code>php://filter/convert.base64-encode/resource=config.php</code> — reads a file base64-encoded (useful for source disclosure even without RCE).</li>
              <li><code>php://input</code> — when included, reads raw POST body as PHP source and executes it.</li>
              <li><code>data://text/plain;base64,PD9waHAgc3lzdGVtKCRfR0VUWydjJ10pOw==</code> — passes inline base64 PHP for direct execution.</li>
            </ul>
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">How should developers fix LFI?</div>
          <div class="interview-a">
            <strong>Never use user input in <code>include</code> or <code>require</code>.</strong> Use an explicit allowlist of permitted page identifiers mapped to server-side paths:
            <div class="terminal" style="margin-top:8px;">
              <span class="query">$allowed = [<span style="color:var(--orange)">'home'</span> => <span style="color:var(--orange)">'pages/home.php'</span>, <span style="color:var(--orange)">'about'</span> => <span style="color:var(--orange)">'pages/about.php'</span>];<br>
$page = $allowed[$_GET[<span style="color:var(--orange)">'page'</span>] ?? <span style="color:var(--orange)">'home'</span>] ?? <span style="color:var(--orange)">'pages/home.php'</span>;<br>
include $page; <span style="color:var(--text-dim)">// safe — user never touches the path</span></span>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Technique cheatsheet -->
    <div class="panel">
      <div class="panel-header"><span class="panel-title">Quick Reference — LFI Escalation Paths</span></div>
      <div class="panel-body">
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
          <thead>
            <tr style="border-bottom:1px solid var(--border);">
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">Technique</th>
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">Requirement</th>
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">File upload + LFI</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Upload functionality, know path</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Most reliable; used in this lab</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">Log poisoning</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Readable log, injectable header</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Common in CTFs and older apps</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">php://input</td>
              <td style="padding:6px 10px;color:var(--text-muted);">allow_url_include=On</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Off by default in modern PHP</td>
            </tr>
            <tr>
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">data:// wrapper</td>
              <td style="padding:6px 10px;color:var(--text-muted);">allow_url_include=On</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Inline PHP, no external request</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
