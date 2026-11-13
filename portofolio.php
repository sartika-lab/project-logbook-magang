<?php
require __DIR__ . '/config.php';
require __DIR__ . '/include/portfolio-storage.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$portfolioCategories = [
    'Looker Studio' => [
        'eyebrow' => 'DASHBOARD OPERASIONAL',
        'title' => 'Interactive Dashboard',
        'description' => 'Dashboard operasional untuk menyajikan data secara ringkas dan mudah ditelusuri.',
        'icon' => 'bi-bar-chart-line',
    ],
    'Google Form' => [
        'eyebrow' => 'ADMINISTRASI OPERASIONAL',
        'title' => 'Digitalisasi Form & Administrasi',
        'description' => 'Penyederhanaan proses pencatatan dan pengelolaan alat kerja.',
        'icon' => 'bi-ui-checks-grid',
    ],
    'LARAISA / Audit' => [
        'eyebrow' => 'AUDIT INFRASTRUKTUR LAPANGAN',
        'title' => 'Management Database & Field Infrastructure Audit',
        'description' => 'Dokumentasi dan pemutakhiran data untuk mendukung kualitas infrastruktur jaringan.',
        'icon' => 'bi-diagram-3',
    ],
];
$portfolioGroups = array_fill_keys(array_keys($portfolioCategories), []);
$portfolioResult = $conn->query('SELECT id, kategori, judul, deskripsi, gambar, urutan FROM portofolio ORDER BY urutan ASC, id ASC');
while ($portfolioItem = $portfolioResult->fetch_assoc()) {
    if (isset($portfolioGroups[$portfolioItem['kategori']])) {
        $portfolioGroups[$portfolioItem['kategori']][] = $portfolioItem;
    }
}
$conn->close();
$current_page_name = 'Hasil Kerja / Portofolio';
$topbar_label = 'Portofolio Operasional';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portofolio hasil kerja dan digitalisasi operasional selama magang di PT Telkom Akses SA Malang.">
    <title>Hasil Kerja &amp; Portofolio | Logbook Magang</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/style.css?v=20261113-3" rel="stylesheet">
    <link href="assets/portfolio.css?v=20261113-4" rel="stylesheet">
    <link href="assets/motion.css?v=20261113-2" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/include/sidebar.php'; ?>

    <main class="main-content">
        <?php require __DIR__ . '/include/header.php'; ?>

        <div class="content-wrap portfolio-content">
            <section class="portfolio-hero">
                <div class="portfolio-hero-copy">
                    <div class="eyebrow"><span></span>HASIL KERJA MAGANG</div>
                    <h1>Dashboard Monitoring Kinerja</h1>
                    <p>Hasil rancang bangun visualisasi data operasional di PT Telkom Akses SA Malang.</p>
                </div>
                <div class="portfolio-hero-icon" aria-hidden="true"><i class="bi bi-briefcase"></i></div>
            </section>

            <?php foreach ($portfolioCategories as $category => $section): ?>
                <?php $items = $portfolioGroups[$category]; ?>
                <section class="portfolio-section" aria-labelledby="section-<?= h(md5($category)) ?>">
                    <div class="portfolio-section-heading">
                        <div>
                            <span class="section-kicker"><?= h($section['eyebrow']) ?></span>
                            <h2 id="section-<?= h(md5($category)) ?>"><?= h($section['title']) ?></h2>
                            <p><?= h($section['description']) ?></p>
                        </div>
                        <?php if ($category === 'Looker Studio'): ?>
                            <span class="portfolio-section-icon"><i class="bi <?= h($section['icon']) ?>"></i> <?= h($category) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($items): ?>
                        <div class="<?= $category === 'Looker Studio' ? 'portfolio-dashboard-grid' : 'portfolio-work-grid' ?>">
                            <?php foreach ($items as $item): ?>
                                <?php
                                $imageName = basename((string) $item['gambar']);
                                $imagePath = __DIR__ . '/assets/img/portofolio/' . $imageName;
                                $hasImage = $imageName !== '' && is_file($imagePath);
                                $imageSource = $hasImage ? 'assets/img/portofolio/' . rawurlencode($imageName) : '';
                                ?>
                                <article
                                    class="portfolio-card portfolio-detail-trigger <?= $category === 'Looker Studio' ? 'dashboard-card' : 'work-card' ?>"
                                    role="button"
                                    tabindex="0"
                                    aria-haspopup="dialog"
                                    aria-label="Lihat detail <?= h($item['judul']) ?>"
                                    data-title="<?= h($item['judul']) ?>"
                                    data-description="<?= h($item['deskripsi']) ?>"
                                    data-image="<?= h($imageSource) ?>"
                                    data-image-alt="<?= h('Screenshot ' . $item['judul']) ?>">
                                    <?php if ($hasImage): ?>
                                        <div class="portfolio-image-wrap">
                                            <img class="portfolio-image" src="<?= h($imageSource) ?>" alt="Screenshot <?= h($item['judul']) ?>" loading="lazy">
                                        </div>
                                    <?php else: ?>
                                        <div class="portfolio-image-placeholder" aria-hidden="true"><i class="bi <?= h($section['icon']) ?>"></i></div>
                                    <?php endif; ?>
                                    <div class="portfolio-card-content">
                                        <span class="portfolio-card-index"><?= h($category) ?></span>
                                        <h3><?= h($item['judul']) ?></h3>
                                        <p><?= h($item['deskripsi']) ?></p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="portfolio-empty-state"><i class="bi bi-folder2-open"></i><span>Belum ada hasil kerja pada kategori ini.</span></div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <footer class="portfolio-footer">Portofolio hasil kerja · PT Telkom Akses SA Malang</footer>
        </div>
    </main>
</div>
<div class="portfolio-detail-modal" id="portfolioDetailModal" role="dialog" aria-modal="true" aria-labelledby="portfolioDetailTitle" hidden>
    <div class="portfolio-detail-dialog">
        <button class="portfolio-detail-close" id="portfolioDetailClose" type="button" aria-label="Tutup detail"><i class="bi bi-x-lg"></i></button>
        <div class="portfolio-detail-image-wrap" id="portfolioDetailImageWrap" hidden>
            <img id="portfolioDetailImage" src="" alt="">
        </div>
        <div class="portfolio-detail-copy">
            <span class="section-kicker">DETAIL HASIL KERJA MAGANG</span>
            <h2 id="portfolioDetailTitle"></h2>
            <p id="portfolioDetailDescription"></p>
        </div>
    </div>
</div>
<script src="assets/app.js?v=20261113-5"></script>
<script src="assets/portfolio.js?v=20261113-3"></script>
</body>
</html>
