<?php
// $page_title — set before including this file
// $lab_id     — set before including (optional)
// $lab_topic  — 'cmd' | 'rce' | 'ssti' (optional)
$page_title = $page_title ?? 'Hunt Labs';
$solved_n   = solved_count();
$total_n    = 16;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($page_title) ?> | Hunt Labs</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<nav>
  <div class="nav-inner">
    <a class="nav-logo" href="/index.php">
      <span class="bracket">[</span>HUNT<span style="color:var(--text-muted)">_</span>LABS<span class="bracket">]</span>
    </a>
    <div class="nav-links">
      <a href="/index.php">Hub</a>
      <?php if (!empty($lab_id)): ?>
      <a href="/<?= h($lab_id) ?>_interview.php">Interview Prep</a>
      <?php endif; ?>
    </div>
    <span class="nav-progress"><?= $solved_n ?>/<?= $total_n ?> solved</span>
  </div>
</nav>

<main>
