<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isDashboardPage = $currentPage === 'index.php';
$isProfilePage = $currentPage === 'profil.php';
$isPortfolioPage = $currentPage === 'portofolio.php';
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="index.php">
        <span class="brand-mark"><i class="bi bi-journal-bookmark-fill"></i></span>
        <span><b>LOGBOOK</b><small>MAGANG</small></span>
    </a>
    <div class="sidebar-label">MENU UTAMA</div>
    <a class="nav-link <?= $isDashboardPage ? 'active' : '' ?>" href="index.php" <?= $isDashboardPage ? 'aria-current="page"' : '' ?>><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
    <a class="nav-link" href="index.php#daftar-kegiatan" data-sidebar-anchor="daftar-kegiatan"><i class="bi bi-calendar2-week"></i>Dokumentasi Kegiatan</a>
    <a class="nav-link <?= $isPortfolioPage ? 'active' : '' ?>" href="portofolio.php" <?= $isPortfolioPage ? 'aria-current="page"' : '' ?>><i class="bi bi-briefcase-fill"></i>Hasil Kerja / Portofolio</a>
    <a class="nav-link" href="index.php#filter-kegiatan" data-sidebar-anchor="filter-kegiatan"><i class="bi bi-funnel"></i>Filter &amp; Rekap</a>
    <div class="sidebar-label sidebar-section-label">INFORMASI TIM</div>
    <a class="nav-link <?= $isProfilePage ? 'active' : '' ?>" href="profil.php" <?= $isProfilePage ? 'aria-current="page"' : '' ?>><i class="bi bi-person-vcard"></i>Profil Tim</a>
    <div class="sidebar-bottom">
        <div class="company-icon"><i class="bi bi-buildings-fill"></i></div>
        <div><b>PT Telkom Akses</b><small>Malang</small></div>
    </div>
</aside>
