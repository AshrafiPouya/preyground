<?php
require_once __DIR__ . '/includes/helpers.php';

// Handle progress reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    session_destroy();
    header('Location: /index.php');
    exit;
}

$topics = [
    [
        'id' => 'cmd', 'label' => 'Command Injection',
        'color' => 'var(--orange)',
        'desc' => 'Injecting shell metacharacters into OS commands. From classic semicolons to blind timing attacks and argument injection.',
        'labs' => [
            ['id'=>'c1','title'=>'Classic Command Injection','difficulty'=>'beginner','interview'=>4,'hunt'=>3,'path'=>'/cmd_c1.php','desc'=>'Direct shell_exec concatenation. Chain commands with semicolons to read the flag file.','concepts'=>['shell_exec','Metacharacters','; && |','Command chaining']],
            ['id'=>'c2','title'=>'Filtered Metacharacters','difficulty'=>'intermediate','interview'=>4,'hunt'=>3,'path'=>'/cmd_c2.php','desc'=>'A blacklist strips ;, &, and | — but the shell has many more separators.','concepts'=>['Blacklist bypass','$()','Backticks','Command substitution']],
            ['id'=>'c3','title'=>'Blind Command Injection','difficulty'=>'advanced','interview'=>3,'hunt'=>4,'path'=>'/cmd_c3.php','desc'=>'Output is never shown. Confirm via sleep timing, then exfiltrate through the feedback log.','concepts'=>['Blind CMDi','sleep()','Side-channel','OOB exfil']],
            ['id'=>'c4','title'=>'Argument Injection','difficulty'=>'intermediate','interview'=>3,'hunt'=>4,'path'=>'/cmd_c4.php','desc'=>'Metacharacters are sanitized but spaces remain — inject curl flags to read arbitrary files.','concepts'=>['Argument injection','-o flag','escapeshellcmd','curl abuse']],
            ['id'=>'c5','title'=>'IFS & Wildcard Bypass','difficulty'=>'advanced','interview'=>3,'hunt'=>3,'path'=>'/cmd_c5.php','desc'=>'Spaces and command names are blocked. Use ${IFS}, wildcards, and brace expansion.','concepts'=>['${IFS}','Wildcards','Brace expansion','Keyword filter']],
        ],
    ],
    [
        'id' => 'rce', 'label' => 'Remote Code Execution',
        'color' => 'var(--red)',
        'desc' => 'Executing attacker-controlled code in the application runtime. From eval() to deserialization, file upload, and LFI chains.',
        'labs' => [
            ['id'=>'r1','title'=>'eval() Code Injection','difficulty'=>'beginner','interview'=>5,'hunt'=>3,'path'=>'/rce_r1.php','desc'=>'User input is passed to PHP eval(). Inject arbitrary PHP to read the flag.','concepts'=>['eval()','Code injection','PHP RCE','__import__']],
            ['id'=>'r2','title'=>'File Upload → Webshell','difficulty'=>'intermediate','interview'=>5,'hunt'=>5,'path'=>'/rce_r2.php','desc'=>'No file validation. Upload a .php webshell to the web-served uploads dir.','concepts'=>['File upload','Webshell','No validation','move_uploaded_file']],
            ['id'=>'r3','title'=>'Insecure Deserialization','difficulty'=>'advanced','interview'=>4,'hunt'=>4,'path'=>'/rce_r3.php','desc'=>'A cookie is passed to unserialize(). Craft a LogWriter gadget object to write a webshell.','concepts'=>['unserialize()','PHP object injection','__destruct','Gadget chain']],
            ['id'=>'r4','title'=>'LFI → Code Execution','difficulty'=>'intermediate','interview'=>4,'hunt'=>5,'path'=>'/rce_r4.php','desc'=>'A template viewer includes user-controlled paths. Poison a writable file, then include it.','concepts'=>['LFI','Log poisoning','include','Path traversal']],
            ['id'=>'r5','title'=>'Dynamic Callable Abuse','difficulty'=>'advanced','interview'=>3,'hunt'=>3,'path'=>'/rce_r5.php','desc'=>'A text utility calls a user-chosen function via call_user_func(). Supply a dangerous callable.','concepts'=>['call_user_func','Callable injection','system()','passthru()']],
            ['id'=>'r6','title'=>'Capstone: Chained RCE','difficulty'=>'expert','interview'=>4,'hunt'=>5,'path'=>'/rce_r6.php','desc'=>'Upload filter + disable_functions. Chain an extension bypass with alternative exec primitives.','concepts'=>['Extension bypass','disable_functions','proc_open','Chaining']],
        ],
    ],
    [
        'id' => 'ssti', 'label' => 'SSTI',
        'color' => 'var(--purple)',
        'desc' => 'Server-Side Template Injection — user input rendered by a template engine, escalating from arithmetic evaluation to full RCE.',
        'labs' => [
            ['id'=>'s1','title'=>'Detection & Basic Evaluation','difficulty'=>'beginner','interview'=>3,'hunt'=>4,'path'=>'/ssti_s1.php','desc'=>'Input concatenated into a Twig template string. Confirm SSTI via {{7*7}}, then access the flag variable.','concepts'=>['Jinja2/Twig','{{7*7}}','Detection','Template variables']],
            ['id'=>'s2','title'=>'SSTI → RCE','difficulty'=>'advanced','interview'=>4,'hunt'=>4,'path'=>'/ssti_s2.php','desc'=>'Same Twig sink. Flag is only in a file — traverse PHP objects to reach exec primitives.','concepts'=>['Twig RCE','registerUndefinedFilterCallback','_self.env','Object traversal']],
            ['id'=>'s3','title'=>'Blind SSTI','difficulty'=>'intermediate','interview'=>3,'hunt'=>4,'path'=>'/ssti_s3.php','desc'=>'Template renders but output is stored server-side. Confirm via timing, exfiltrate via preview.','concepts'=>['Blind SSTI','Side-channel','Write-to-readable','OOB']],
            ['id'=>'s4','title'=>'Engine Fingerprinting: Smarty','difficulty'=>'advanced','interview'=>3,'hunt'=>4,'path'=>'/ssti_s4.php','desc'=>'This app uses Smarty, not Twig. Probe for engine-specific syntax, then exploit {php} blocks.','concepts'=>['Smarty','Engine fingerprinting','{php}','{$smarty.version}']],
            ['id'=>'s5','title'=>'Capstone: SSTI in the Wild','difficulty'=>'expert','interview'=>4,'hunt'=>5,'path'=>'/ssti_s5.php','desc'=>'Multiple inputs, one SSTI sink. {{ }} is filtered — find the sink, bypass via {%set%}.','concepts'=>['Recon','Filter bypass','{%set%}','Methodology']],
        ],
    ],
];

$solved = $_SESSION['solved'] ?? [];
$solved_count = count($solved);

$page_title = 'Hunt Labs — CMDi · RCE · SSTI';
include __DIR__ . '/includes/header.php';
?>

<div class="page">
  <div class="hero">
    <div class="hero-eyebrow">// hands-on security training</div>
    <h1>CMDi · RCE · SSTI<br>Hunt Labs</h1>
    <p class="hero-sub">16 progressively harder labs covering Command Injection, Remote Code Execution, and Server-Side Template Injection. Find it, exploit it, understand it.</p>
    <div class="hero-stats">
      <div class="stat"><div class="stat-num">16</div><div class="stat-label">Labs</div></div>
      <div class="stat"><div class="stat-num"><?= $solved_count ?></div><div class="stat-label">Solved</div></div>
      <div class="stat"><div class="stat-num">3</div><div class="stat-label">Topics</div></div>
      <div class="stat"><div class="stat-num">16</div><div class="stat-label">Interview Guides</div></div>
    </div>
  </div>

  <?php foreach ($topics as $topic): ?>
  <div class="labs-section">
    <div class="section-title" style="color:<?= $topic['color'] ?>">
      // <?= h($topic['label']) ?>
    </div>
    <p style="color:var(--text-muted);font-size:0.875rem;margin-bottom:24px;max-width:680px;">
      <?= h($topic['desc']) ?>
    </p>
    <div class="lab-grid">
      <?php foreach ($topic['labs'] as $lab):
        $is_solved = in_array($lab['id'], $solved, true);
      ?>
      <div class="lab-card <?= $is_solved ? 'solved' : '' ?>" style="<?= $is_solved ? '' : "--topic-color:{$topic['color']}" ?>">
        <div class="lab-card-header">
          <span class="lab-number">LAB <?= strtoupper(h($lab['id'])) ?></span>
          <div class="lab-badges">
            <span class="badge badge-<?= h($lab['difficulty']) ?>"><?= h($lab['difficulty']) ?></span>
            <?php if ($is_solved): ?><span class="badge badge-solved">✓ solved</span><?php endif; ?>
          </div>
        </div>
        <div class="lab-title"><?= h($lab['title']) ?></div>
        <div class="lab-desc"><?= h($lab['desc']) ?></div>
        <div class="lab-ratings">
          <div class="rating-item">
            <div class="rating-label">🎤 Interview</div>
            <div class="rating-stars"><?= stars($lab['interview']) ?></div>
          </div>
          <div class="rating-item">
            <div class="rating-label">🎯 Hunt</div>
            <div class="rating-stars"><?= stars($lab['hunt']) ?></div>
          </div>
        </div>
        <div class="lab-concepts">
          <?php foreach ($lab['concepts'] as $c): ?>
          <span class="concept-tag"><?= h($c) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="lab-actions">
          <a href="<?= h($lab['path']) ?>" class="btn btn-primary btn-sm">
            <?= $is_solved ? 'Review Lab' : 'Start Lab' ?>
          </a>
          <a href="<?= h(str_replace('.php', '_interview.php', $lab['path'])) ?>" class="btn btn-ghost btn-sm">Interview Prep</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if ($solved_count > 0): ?>
  <div style="padding:20px 0;text-align:center;border-top:1px solid var(--border-soft);margin-top:20px;">
    <form method="post" onsubmit="return confirm('Reset all progress?')">
      <input type="hidden" name="action" value="reset">
      <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--text-dim)">Reset All Progress</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
