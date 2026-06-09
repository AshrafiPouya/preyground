<?php
require_once __DIR__ . '/includes/helpers.php';
$page_title = 'Interview Prep — Dynamic Callable Abuse';
$lab_id     = 'r5';
include __DIR__ . '/includes/header.php';
?>

<div class="page interview-page">

  <div class="lab-header">
    <div class="lab-header-info">
      <div class="lab-header-meta">
        <span class="badge badge-advanced">Advanced</span>
        <span style="font-family:var(--font-mono);font-size:0.7rem;color:var(--text-dim)">LAB R5 — INTERVIEW PREP</span>
        <span style="font-size:0.7rem;color:var(--text-dim)">🎤 <?= stars(5) ?> Interview relevance</span>
      </div>
      <h1>Dynamic Callable Abuse: Interview Prep</h1>
      <p class="lab-header-desc">How to discuss PHP code-execution sinks and callable abuse at a senior/security-engineer level.</p>
    </div>
    <div class="lab-header-actions">
      <a href="/rce_r5.php" class="btn btn-ghost btn-sm">Back to Lab</a>
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
          "PHP's <code>call_user_func()</code> takes a callable as its first argument. If that callable is user-controlled, the attacker can supply any PHP function — including <code>system</code>, <code>passthru</code>, or <code>file_get_contents</code> — achieving RCE or arbitrary file read. The fix is to never pass user-controlled values to <code>call_user_func</code> and to use a strict allowlist of permitted function names."
        </blockquote>
      </div>
    </div>

    <!-- Common Qs -->
    <div class="panel">
      <div class="panel-header"><span class="panel-title">Common Interview Questions</span></div>
      <div class="panel-body" style="display:flex;flex-direction:column;gap:18px;">

        <div class="interview-qa">
          <div class="interview-q">Name PHP code-evaluation sinks beyond <code>eval()</code>.</div>
          <div class="interview-a">
            <ul style="margin:4px 0 0;padding-left:20px;display:flex;flex-direction:column;gap:6px;">
              <li><code>assert(string)</code> — evaluates a string as PHP code (PHP &lt;8 behaviour).</li>
              <li><code>create_function()</code> — deprecated; internally uses <code>eval</code>.</li>
              <li><code>preg_replace('/pattern/e', $replacement, $subject)</code> — the <code>/e</code> modifier eval'd the replacement (PHP &lt;7 only).</li>
              <li><code>call_user_func($fn, $arg)</code> and <code>call_user_func_array($fn, $args)</code> — call arbitrary callables.</li>
              <li>Dynamic calls: <code>$fn($arg)</code> — any variable can be used as a function name.</li>
              <li>Variable variables: <code>$$varname</code> — enables indirect variable access, occasionally chained into code exec.</li>
            </ul>
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">What is the <code>/e</code> modifier in <code>preg_replace</code>?</div>
          <div class="interview-a">
            A PHP &lt;7 feature where the replacement string was <code>eval</code>'d after backreferences were substituted. Classic RCE sink:
            <div class="terminal" style="margin-top:8px;">
              <span class="query"><span style="color:var(--text-dim)">// PHP &lt;7 — replacement is eval'd</span><br>
preg_replace(<span style="color:var(--orange)">'/.*/e'</span>, $_GET[<span style="color:var(--orange)">'cmd'</span>], <span style="color:var(--orange)">''</span>);</span>
            </div>
            The <code>/e</code> modifier was removed in PHP 7. Use <code>preg_replace_callback()</code> instead.
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">How do you find callable-abuse sinks in a codebase?</div>
          <div class="interview-a">
            Grep for dangerous patterns:
            <div class="terminal" style="margin-top:8px;">
              <span class="query"><span style="color:var(--text-dim)"># Find call_user_func with non-literal first argument</span><br>
grep -rn <span style="color:var(--orange)">"call_user_func\s*(\$"</span> .<br>
grep -rn <span style="color:var(--orange)">"call_user_func_array\s*(\$"</span> .<br>
<span style="color:var(--text-dim)"># Dynamic variable-function calls</span><br>
grep -rn <span style="color:var(--orange)">"\$[a-z_]\+([^)]*\$_"</span> .</span>
            </div>
            Then trace dataflow from user-controlled sources (<code>$_GET</code>, <code>$_POST</code>, <code>$_REQUEST</code>) to each sink.
          </div>
        </div>

        <div class="interview-qa">
          <div class="interview-q">How should developers fix callable injection?</div>
          <div class="interview-a">
            <strong>Never pass user-controlled values to <code>call_user_func</code>.</strong> Use a strict allowlist:
            <div class="terminal" style="margin-top:8px;">
              <span class="query">$allowed = [<span style="color:var(--orange)">'strtoupper'</span>, <span style="color:var(--orange)">'strtolower'</span>, <span style="color:var(--orange)">'md5'</span>];<br>
$fn = $_GET[<span style="color:var(--orange)">'transform'</span>] ?? <span style="color:var(--orange)">'strtoupper'</span>;<br>
if (!in_array($fn, $allowed, true)) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;http_response_code(400); die(<span style="color:var(--orange)">"Invalid transform"</span>);<br>
}<br>
$result = $fn($data); <span style="color:var(--text-dim)">// safe — function name validated</span></span>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Sink reference -->
    <div class="panel">
      <div class="panel-header"><span class="panel-title">PHP Code-Exec Sinks — Quick Reference</span></div>
      <div class="panel-body">
        <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
          <thead>
            <tr style="border-bottom:1px solid var(--border);">
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">Sink</th>
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">Still present?</th>
              <th style="text-align:left;padding:6px 10px;color:var(--text-dim);font-weight:600;">Notes</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">eval()</td>
              <td style="padding:6px 10px;color:var(--green);">PHP 8</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Classic; easy to grep</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">assert(string)</td>
              <td style="padding:6px 10px;color:var(--orange);">PHP 8 (warns)</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Still works in PHP 7; deprecated string form</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">preg_replace /e</td>
              <td style="padding:6px 10px;color:var(--red);">PHP &lt;7 only</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Removed in PHP 7</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">call_user_func()</td>
              <td style="padding:6px 10px;color:var(--green);">PHP 8</td>
              <td style="padding:6px 10px;color:var(--text-muted);">This lab; any callable accepted</td>
            </tr>
            <tr style="border-bottom:1px solid var(--border-subtle);">
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">create_function()</td>
              <td style="padding:6px 10px;color:var(--red);">Removed PHP 8</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Internally wraps eval</td>
            </tr>
            <tr>
              <td style="padding:6px 10px;font-family:var(--font-mono);font-size:0.8rem;">$fn()</td>
              <td style="padding:6px 10px;color:var(--green);">PHP 8</td>
              <td style="padding:6px 10px;color:var(--text-muted);">Dynamic variable-function call</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
