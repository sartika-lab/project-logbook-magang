<?php
$currentPageName = isset($current_page_name) && is_string($current_page_name) && $current_page_name !== ''
    ? $current_page_name
    : 'Dashboard';
$topbarLabel = isset($topbar_label) && is_string($topbar_label) && $topbar_label !== ''
    ? $topbar_label
    : $currentPageName;
?>
<header class="topbar">
    <button class="menu-toggle" id="menuToggle" type="button" aria-label="Buka menu"><i class="bi bi-list"></i></button>
    <div class="breadcrumb-text">
        <span>Logbook Magang</span>
        <i class="bi bi-chevron-right"></i>
        <b id="breadcrumbPageName"><?= htmlspecialchars($currentPageName, ENT_QUOTES, 'UTF-8') ?></b>
    </div>
    <div class="topbar-right"><span class="live-dot"></span><span class="local-label"><?= htmlspecialchars($topbarLabel, ENT_QUOTES, 'UTF-8') ?></span><div class="avatar">LM</div></div>
</header>
