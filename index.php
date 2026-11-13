<?php
require __DIR__ . '/config.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function validDateFilter($value)
{
    if (!is_string($value) || $value === '') {
        return '';
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
}

$start = validDateFilter($_GET['start'] ?? '');
$end = validDateFilter($_GET['end'] ?? '');
$category = trim((string) ($_GET['category'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$shortMonthNames = [
    1 => 'Jan',
    2 => 'Feb',
    3 => 'Mar',
    4 => 'Apr',
    5 => 'Mei',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Agu',
    9 => 'Sep',
    10 => 'Okt',
    11 => 'Nov',
    12 => 'Des',
];
$formatDateFilterLabel = function ($date) use ($shortMonthNames) {
    if ($date === '') {
        return '';
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$dateObject) {
        return '';
    }

    return $dateObject->format('j') . ' '
        . $shortMonthNames[(int) $dateObject->format('n')] . ' '
        . $dateObject->format('Y');
};
$dateRangeLabel = '';
if ($start !== '' && $end !== '') {
    $dateRangeLabel = $formatDateFilterLabel($start) . ' - ' . $formatDateFilterLabel($end);
} elseif ($start !== '') {
    $dateRangeLabel = $formatDateFilterLabel($start);
} elseif ($end !== '') {
    $dateRangeLabel = $formatDateFilterLabel($end);
}

$conditions = [];
$params = [];
$types = '';
if ($start !== '') {
    $conditions[] = 'k.tanggal_kegiatan >= ?';
    $params[] = $start;
    $types .= 's';
}
if ($end !== '') {
    $conditions[] = 'k.tanggal_kegiatan <= ?';
    $params[] = $end;
    $types .= 's';
}
if ($category !== '') {
    $conditions[] = 'FIND_IN_SET(?, REPLACE(k.kategori, ", ", ",")) > 0';
    $params[] = $category;
    $types .= 's';
}
if ($search !== '') {
    $conditions[] = '(k.judul_kegiatan LIKE ? OR k.deskripsi LIKE ? OR k.hasil_pembelajaran LIKE ? OR k.kategori LIKE ? OR EXISTS (SELECT 1 FROM peserta_kegiatan pks JOIN mahasiswa ms ON ms.id_mahasiswa = pks.id_mahasiswa WHERE pks.id_kegiatan = k.id_kegiatan AND ms.nama_mahasiswa LIKE ?))';
    $searchValue = '%' . $search . '%';
    for ($index = 0; $index < 5; $index++) {
        $params[] = $searchValue;
    }
    $types .= 'sssss';
}
if ($start !== '' && $end !== '' && $start > $end) {
    $conditions[] = '1 = 0';
}

$sql = 'SELECT k.id_kegiatan, k.tanggal_kegiatan, k.judul_kegiatan, k.kategori, k.waktu_kegiatan, k.deskripsi, k.hasil_pembelajaran, k.status_kegiatan, GROUP_CONCAT(DISTINCT m.nama_mahasiswa ORDER BY m.nama_mahasiswa SEPARATOR ", ") AS peserta FROM kegiatan k LEFT JOIN peserta_kegiatan pk ON pk.id_kegiatan = k.id_kegiatan LEFT JOIN mahasiswa m ON m.id_mahasiswa = pk.id_mahasiswa';
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$dateOrder = $start !== '' || $end !== '' ? 'ASC' : 'DESC';
$sql .= ' GROUP BY k.id_kegiatan ORDER BY k.tanggal_kegiatan ' . $dateOrder . ', k.id_kegiatan ' . $dateOrder;

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$documentsByActivity = [];
if ($activities) {
    $activityIds = array_map('intval', array_column($activities, 'id_kegiatan'));
    $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
    $documentSql = 'SELECT id_kegiatan, nama_file, jenis_foto FROM dokumentasi WHERE id_kegiatan IN (' . $placeholders . ') ORDER BY id_dokumentasi';
    $documentStmt = $conn->prepare($documentSql);
    $documentTypes = str_repeat('i', count($activityIds));
    $documentStmt->bind_param($documentTypes, ...$activityIds);
    $documentStmt->execute();
    $documentResult = $documentStmt->get_result();
    while ($document = $documentResult->fetch_assoc()) {
        $documentsByActivity[(int) $document['id_kegiatan']][$document['jenis_foto']][] = $document['nama_file'];
    }
    $documentStmt->close();
}

$categories = ['Observasi', 'Dokumentasi', 'Pengembangan', 'Pendalaman Materi', 'Diskusi'];
$stats = $conn->query('SELECT COUNT(*) AS total, COUNT(DISTINCT tanggal_kegiatan) AS days, MAX(tanggal_kegiatan) AS latest FROM kegiatan')->fetch_assoc();
$total = (int) ($stats['total'] ?? 0);
$days = (int) ($stats['days'] ?? 0);
$weekdayNames = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
];
$monthNames = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
];
$formatIndonesianDate = function ($date) use ($weekdayNames, $monthNames) {
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$dateObject) {
        return '';
    }

    return $weekdayNames[$dateObject->format('l')] . ', '
        . $dateObject->format('j') . ' '
        . $monthNames[(int) $dateObject->format('n')] . ' '
        . $dateObject->format('Y');
};
$formatTimeRange = function ($time) {
    return preg_replace('/\s*[-–—]\s*/u', '–', trim((string) $time));
};
$latest = $stats['latest'] ? $formatIndonesianDate($stats['latest']) : 'Belum ada data';
$shown = count($activities);
$icons = [
    'Dokumentasi' => 'bi-camera',
    'Observasi' => 'bi-binoculars',
    'Pengembangan' => 'bi-code-slash',
    'Pendalaman Materi' => 'bi-book-half',
    'Diskusi' => 'bi-people',
];
$groupedDays = [];
foreach ($activities as $activity) {
    $activityId = (int) $activity['id_kegiatan'];
    $dateKey = $activity['tanggal_kegiatan'];
    $activityDocuments = $documentsByActivity[$activityId] ?? [];
    $activityCategories = array_map('trim', explode(',', $activity['kategori']));
    if (!isset($groupedDays[$dateKey])) {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $dateKey);
        $groupedDays[$dateKey] = [
            'date' => $dateKey,
            'dateLabel' => $formatIndonesianDate($dateKey),
            'day' => $dateObject ? $dateObject->format('d') : '',
            'monthShort' => $dateObject ? strtoupper($dateObject->format('M')) : '',
            'year' => $dateObject ? $dateObject->format('Y') : '',
            'weekday' => $dateObject ? ($weekdayNames[$dateObject->format('l')] ?? '') : '',
            'activities' => [],
            'checkIn' => [],
            'checkOut' => [],
        ];
    }

    $groupedDays[$dateKey]['activities'][] = [
        'title' => $activity['judul_kegiatan'],
        'category' => $activity['kategori'],
        'time' => $formatTimeRange($activity['waktu_kegiatan']),
        'description' => $activity['deskripsi'],
        'learning' => $activity['hasil_pembelajaran'],
        'participants' => $activity['peserta'] ?: 'Belum tercatat',
        'status' => $activity['status_kegiatan'],
        'icon' => $icons[$activityCategories[0] ?? ''] ?? 'bi-card-checklist',
        'photos' => $activityDocuments['kegiatan'] ?? [],
    ];
}
if ($groupedDays) {
    foreach ($groupedDays as &$groupedDay) {
        $groupedDay['checkIn'] = [];
        $groupedDay['checkOut'] = [];
    }
    unset($groupedDay);

    $dayKeys = array_keys($groupedDays);
    $dayPlaceholders = implode(',', array_fill(0, count($dayKeys), '?'));
    $attendanceSql = 'SELECT k.tanggal_kegiatan, d.nama_file, d.jenis_foto FROM kegiatan k JOIN dokumentasi d ON d.id_kegiatan = k.id_kegiatan WHERE k.tanggal_kegiatan IN (' . $dayPlaceholders . ') AND d.jenis_foto IN (?, ?) ORDER BY d.id_dokumentasi';
    $attendanceStmt = $conn->prepare($attendanceSql);
    $attendanceTypes = str_repeat('s', count($dayKeys)) . 'ss';
    $checkInType = 'absen_masuk';
    $checkOutType = 'absen_pulang';
    $attendanceParams = array_merge($dayKeys, [$checkInType, $checkOutType]);
    $attendanceStmt->bind_param($attendanceTypes, ...$attendanceParams);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();
    while ($attendance = $attendanceResult->fetch_assoc()) {
        $groupKey = $attendance['tanggal_kegiatan'];
        $groupType = $attendance['jenis_foto'] === 'absen_masuk' ? 'checkIn' : 'checkOut';
        $groupedDays[$groupKey][$groupType][$attendance['nama_file']] = $attendance['nama_file'];
    }
    $attendanceStmt->close();
}
foreach ($groupedDays as &$groupedDay) {
    $groupedDay['checkIn'] = array_values($groupedDay['checkIn']);
    $groupedDay['checkOut'] = array_values($groupedDay['checkOut']);
}
unset($groupedDay);
$conn->close();
$current_page_name = 'Dashboard';
$topbar_label = 'Dokumentasi Kegiatan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Dokumentasi kegiatan dan pembelajaran magang Sartika dan Nadirah di PT Telkom Akses Malang.">
    <title>Logbook Magang | PT Telkom Akses Malang</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="assets/style.css?v=20261113-5" rel="stylesheet">
    <link href="assets/motion.css?v=20261113-2" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/include/sidebar.php'; ?>

    <main class="main-content">
        <?php require __DIR__ . '/include/header.php'; ?>

        <div class="content-wrap">
            <section class="welcome-row">
                <div>
                    <div class="eyebrow"><span></span>SISTEM INFORMASI MAGANG</div>
                    <h1>Perjalanan Magang Kami</h1>
                    <p class="welcome-desc">Catatan kegiatan, dokumentasi, dan pembelajaran Sartika &amp; Nadirah di PT Telkom Akses Malang.</p>
                </div>
                <form method="get" action="index.php" id="dateRangeForm" class="date-range-form">
                    <label class="date-filter-trigger" for="dateRangePicker">
                        <i class="bi bi-calendar3"></i>
                        <input type="text" id="dateRangePicker" value="<?= h($dateRangeLabel) ?>" placeholder="Pilih rentang tanggal" readonly>
                        <i class="bi bi-chevron-down"></i>
                    </label>
                    <input type="hidden" name="start" id="rangeStart" value="<?= h($start) ?>">
                    <input type="hidden" name="end" id="rangeEnd" value="<?= h($end) ?>">
                    <input type="hidden" name="q" value="<?= h($search) ?>">
                    <input type="hidden" name="category" value="<?= h($category) ?>">
                </form>
            </section>

            <section class="hero-banner">
                <div class="hero-copy">
                    <div class="hero-tag"><i class="bi bi-stars"></i>CATATAN KEGIATAN TERORGANISIR</div>
                    <h2>Semua aktivitas,<br><span>tercatat dengan rapi.</span></h2>
                    <p>Jelajahi pengalaman, dokumentasi lapangan, serta pembelajaran selama menjalani program magang.</p>
                    <a href="#daftar-kegiatan" class="btn btn-light hero-button">Lihat dokumentasi<i class="bi bi-arrow-down-right ms-2"></i></a>
                </div>
                <div class="hero-art" aria-hidden="true">
                    <div class="art-circle circle-one"></div><div class="art-circle circle-two"></div>
                    <div class="art-paper"><div class="paper-top"><span></span><span></span><span></span></div><div class="paper-check"><i class="bi bi-check2"></i></div><div class="paper-line long"></div><div class="paper-line"></div><div class="paper-line short"></div><div class="paper-footer"><i class="bi bi-calendar-check"></i> Logbook harian</div></div>
                    <div class="floating-badge"><i class="bi bi-check-circle-fill"></i>Dokumentasi tertata</div>
                </div>
            </section>

            <section class="stats-grid" aria-label="Ringkasan logbook">
                <article class="stat-card"><div class="stat-icon red"><i class="bi bi-journal-text"></i></div><div class="stat-label">Total kegiatan</div><div class="stat-value"><?= $total ?> <span>aktivitas</span></div><div class="stat-foot muted">Seluruh catatan logbook</div></article>
                <article class="stat-card"><div class="stat-icon peach"><i class="bi bi-calendar-check"></i></div><div class="stat-label">Hari terdokumentasi</div><div class="stat-value"><?= $days ?> <span>hari</span></div><div class="stat-foot muted">Hari dengan kegiatan tercatat</div></article>
                <article class="stat-card"><div class="stat-icon dark"><i class="bi bi-people"></i></div><div class="stat-label">Peserta magang</div><div class="stat-value">2 <span>mahasiswa</span></div><div class="stat-foot muted">Sartika &amp; Nadirah</div></article>
                <article class="stat-card"><div class="stat-icon pink"><i class="bi bi-clock-history"></i></div><div class="stat-label">Kegiatan terbaru</div><div class="stat-value small-value latest-stat-value"><?= h($latest) ?></div><div class="stat-foot muted">Tanggal paling baru dicatat</div></article>
            </section>

            <section class="filter-panel" id="filter-kegiatan">
                <div class="section-heading">
                    <div><span class="section-kicker">PENCARIAN DATA</span><h3>Temukan kegiatan</h3><p>Gunakan kata kunci, tanggal, atau kategori untuk menyaring catatan.</p></div>
                    <a class="reset-link" href="index.php"><i class="bi bi-arrow-counterclockwise"></i> Reset filter</a>
                </div>
                <form method="get" action="index.php" class="filter-form">
                    <div class="field-group search-field">
                        <label for="q">Kata kunci</label>
                        <div class="input-icon"><i class="bi bi-search"></i><input id="q" name="q" type="search" class="form-control" placeholder="Cari kegiatan, peserta, pembelajaran..." value="<?= h($search) ?>"></div>
                    </div>
                    <div class="field-group"><label for="start">Dari tanggal</label><input id="start" name="start" type="date" class="form-control" value="<?= h($start) ?>"></div>
                    <div class="field-group"><label for="end">Sampai tanggal</label><input id="end" name="end" type="date" class="form-control" value="<?= h($end) ?>"></div>
                    <div class="field-group">
                        <label for="category">Kategori</label>
                        <select id="category" name="category" class="form-select">
                            <option value="">Semua kategori</option>
                            <?php foreach ($categories as $item): ?>
                                <option value="<?= h($item) ?>" <?= $category === $item ? 'selected' : '' ?>><?= h($item) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-brand" type="submit"><i class="bi bi-funnel me-2"></i>Filter</button>
                </form>
            </section>

            <section class="activities-section" id="daftar-kegiatan">
                <div class="section-heading activities-heading">
                    <div><span class="section-kicker">REKAM JEJAK HARIAN</span><h3>Dokumentasi kegiatan</h3><p>Menampilkan <b><?= $shown ?></b> dari <?= $total ?> catatan aktivitas.</p></div>
                    <div class="view-toggle"><i class="bi bi-list-ul"></i><span>Daftar kegiatan</span></div>
                </div>
                <?php if ($activities): ?>
                    <div class="day-group-list">
                        <?php foreach ($groupedDays as $dateKey => $dayGroup): ?>
                            <?php
                            $detailJson = json_encode($dayGroup, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                            ?>
                            <article class="day-group-card" tabindex="0" aria-haspopup="dialog" aria-label="Lihat detail kegiatan <?= h($dayGroup['dateLabel']) ?>">
                                <div class="day-date-column">
                                    <div class="day-date-badge">
                                        <strong><?= h($dayGroup['day']) ?></strong>
                                        <span><?= h($dayGroup['monthShort']) ?></span>
                                        <small><?= h($dayGroup['year']) ?></small>
                                        <b><?= h($dayGroup['weekday']) ?></b>
                                    </div>
                                </div>
                                <div class="day-group-content">
                                    <header class="day-group-header">
                                        <div>
                                            <span class="section-kicker"><?= count($dayGroup['activities']) ?> KEGIATAN</span>
                                            <h4>Kegiatan pada hari ini</h4>
                                        </div>
                                        <button class="activity-more day-detail-trigger" type="button" aria-label="Lihat detail tanggal <?= h($dayGroup['dateLabel']) ?>" data-detail="<?= h($detailJson ?: '{}') ?>">
                                            <i class="bi bi-arrow-up-right"></i>
                                        </button>
                                    </header>
                                    <div class="day-activity-list">
                                        <?php foreach ($dayGroup['activities'] as $dayActivity): ?>
                                            <section class="day-activity-card">
                                                <div class="day-activity-topline">
                                                    <span class="category-pill"><i class="bi <?= h($dayActivity['icon']) ?> me-1"></i><?= h($dayActivity['category']) ?></span>
                                                    <span class="day-activity-time"><i class="bi bi-clock me-1"></i><?= h($dayActivity['time']) ?></span>
                                                </div>
                                                <h5><?= h($dayActivity['title']) ?></h5>
                                                <div class="participant-line"><i class="bi bi-people-fill"></i> Peserta: <strong><?= h($dayActivity['participants']) ?></strong></div>
                                                <p><?= h($dayActivity['description']) ?></p>
                                                <?php if ($dayActivity['photos']): ?>
                                                    <div class="activity-thumbnails day-photo-list" aria-label="Foto dokumentasi <?= h($dayActivity['title']) ?>">
                                                        <?php foreach ($dayActivity['photos'] as $photoIndex => $photo): ?>
                                                            <button class="photo-zoom" type="button" aria-label="Perbesar foto kegiatan <?= $photoIndex + 1 ?>">
                                                                <img src="uploads/<?= rawurlencode($photo) ?>" alt="Dokumentasi <?= h($dayActivity['title']) ?> <?= $photoIndex + 1 ?>" loading="lazy">
                                                            </button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="activity-status"><span class="status-dot"></span><?= h($dayActivity['status']) ?></div>
                                            </section>
                                        <?php endforeach; ?>
                                    </div>
                                    <section class="attendance-summary day-attendance">
                                        <span class="attendance-label"><i class="bi bi-shield-check"></i> Absensi Harian Telkom Akses</span>
                                        <div class="attendance-thumbnails">
                                            <?php foreach (['checkIn' => 'Absen masuk · pagi', 'checkOut' => 'Absen pulang · sore'] as $type => $label): ?>
                                                <?php if (!empty($dayGroup[$type])): ?>
                                                    <div class="attendance-photo-group">
                                                        <span class="attendance-photo-label"><?= h($label) ?> · <?= count($dayGroup[$type]) ?> foto</span>
                                                        <div class="attendance-photo-list">
                                                            <?php foreach ($dayGroup[$type] as $photoIndex => $photo): ?>
                                                                <button class="photo-zoom" type="button" aria-label="Perbesar <?= h($label) ?> <?= $photoIndex + 1 ?>">
                                                                    <img src="uploads/<?= rawurlencode($photo) ?>" alt="<?= h($label) ?> <?= $photoIndex + 1 ?>" loading="lazy">
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="attendance-missing"><i class="bi bi-image"></i><?= h($label) ?> belum diunggah</span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><div><i class="bi bi-search"></i></div><h4>Kegiatan tidak ditemukan</h4><p>Ubah kata kunci, kategori, atau rentang tanggal, lalu coba lagi.</p><a href="index.php" class="btn btn-outline-secondary">Tampilkan semua kegiatan</a></div>
                <?php endif; ?>
            </section>
            <footer class="footer"><span>© <?= date('Y') ?> Sistem Informasi Logbook Magang</span><span>PT Telkom Akses Malang <i class="bi bi-heart-fill"></i></span></footer>
        </div>
    </main>
</div>

<div class="toast-modal" id="detailToast" role="dialog" aria-modal="true" aria-labelledby="detailTitle" hidden>
    <div class="detail-box">
        <button class="detail-close" id="detailClose" type="button" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        <span class="section-kicker">RINGKASAN KEGIATAN HARIAN</span>
        <h3 id="detailTitle"></h3>
        <div class="detail-date" id="detailDate"></div>
        <div class="detail-meta" id="detailMeta"></div>
        <div class="day-detail-activities" id="detailActivities"></div>
        <section class="attendance-detail" id="attendanceDetailSection"><h4><i class="bi bi-shield-check me-2"></i>Absensi Harian Telkom Akses</h4><div class="attendance-detail-grid"><div class="attendance-detail-column" id="checkInColumn"><h5>Absen masuk · pagi</h5><div class="detail-gallery" id="detailCheckIn"></div></div><div class="attendance-detail-column" id="checkOutColumn"><h5>Absen pulang · sore</h5><div class="detail-gallery" id="detailCheckOut"></div></div></div></section>
    </div>
</div>

<div class="photo-lightbox" id="photoLightbox" role="dialog" aria-modal="true" aria-label="Perbesar foto" hidden>
    <button class="lightbox-close" id="lightboxClose" type="button" aria-label="Tutup foto"><i class="bi bi-x-lg"></i></button>
    <button class="lightbox-nav lightbox-prev" id="lightboxPrev" type="button" aria-label="Foto sebelumnya"><i class="bi bi-chevron-left"></i></button>
    <figure class="lightbox-figure">
        <img id="lightboxImage" src="" alt="">
        <figcaption class="lightbox-caption">
            <strong id="lightboxTitle"></strong>
            <p id="lightboxDescription"></p>
            <span id="lightboxCaption"></span>
        </figcaption>
    </figure>
    <button class="lightbox-nav lightbox-next" id="lightboxNext" type="button" aria-label="Foto berikutnya"><i class="bi bi-chevron-right"></i></button>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script src="assets/app.js?v=20261113-5"></script>
</body>
</html>
