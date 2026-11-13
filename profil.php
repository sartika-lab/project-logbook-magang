<?php
$current_page_name = 'Profil Tim';
$topbar_label = 'Mahasiswa & Tugas Akhir';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Profil mahasiswa magang dan tim tugas akhir D3 Teknik Telekomunikasi Politeknik Negeri Malang.">
    <title>Profil Mahasiswa | Logbook Magang Telkom Akses</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/style.css?v=20261113-3" rel="stylesheet">
    <link href="assets/profil.css?v=20261113-7" rel="stylesheet">
    <link href="assets/motion.css?v=20261113-3" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/include/sidebar.php'; ?>

    <main class="main-content">
        <?php require __DIR__ . '/include/header.php'; ?>

        <div class="content-wrap profile-content">
            <section class="profile-hero">
                <div class="profile-hero-copy">
                    <div class="eyebrow"><span></span>PROFIL TIM MAGANG</div>
                    <h1>Profil Mahasiswa Magang </h1>
                    <p>D3 Teknik Telekomunikasi - Jurusan Teknik Elektro, Politeknik Negeri Malang</p>
                </div>
                <div class="profile-hero-mark" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
            </section>

            <section class="profile-section" aria-labelledby="studentProfilesTitle">
                <div class="profile-section-heading">
                    <div><span class="section-kicker">IDENTITAS MAHASISWA</span><h2 id="studentProfilesTitle">Profil Anggota Tim</h2></div>
                    <a class="profile-section-note" href="https://www.polinema.ac.id/" target="_blank" rel="noopener noreferrer"><i class="bi bi-mortarboard"></i> Politeknik Negeri Malang</a>
                </div>
                <div class="student-grid">
                    <article class="student-card" tabindex="0" role="button" aria-haspopup="dialog" aria-label="Lihat biodata Sartika Bisti Pali'">
                        <div class="student-card-banner leader-banner"></div>
                        <div class="student-card-content">
                            <div class="student-card-intro">
                                <img src="assets/img/sartika.jpg" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md profile-photo profile-photo-sartika" alt="Sartika">
                                <div class="student-heading">
                                    <span class="role-badge role-leader"><i class="bi bi-star-fill"></i>Ketua Kelompok / Team Leader</span>
                                    <h3>Sartika Bisti Pali'</h3>
                                </div>
                            </div>
                            <dl class="student-details">
                                <div><dt>NIM</dt><dd>243101030063</dd></div>
                                <div><dt>Tempat, Tanggal Lahir</dt><dd>Toraja, 28 April 2006</dd></div>
                                <div><dt>Jenis Kelamin</dt><dd>Perempuan</dd></div>
                                <div><dt>Agama</dt><dd>Kristen Protestan</dd></div>
                                <div><dt>Program Studi</dt><dd>D3 Teknik Telekomunikasi</dd></div>
                                <div><dt>Jurusan</dt><dd>Teknik Elektro - Politeknik Negeri Malang</dd></div>
                                <div><dt>Alamat</dt><dd>Jl. Semanggi Barat No. 23C, Kota Malang</dd></div>
                                <div><dt>No. HP</dt><dd>085341664554</dd></div>
                            </dl>
                            <blockquote class="student-motto">
                                <i class="bi bi-quote" aria-hidden="true"></i>
                                <div><span>Motto Hidup</span><p>Lakukan segala sesuatu dalam kasih dan hiduplah selaras dengan ciptaan Tuhan.</p></div>
                            </blockquote>
                        </div>
                    </article>

                    <article class="student-card" tabindex="0" role="button" aria-haspopup="dialog" aria-label="Lihat biodata Nadirah Firyal Az Zahrah">
                        <div class="student-card-banner member-banner"></div>
                        <div class="student-card-content">
                            <div class="student-card-intro">
                                <img src="assets/img/nadirah.jpg" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md profile-photo profile-photo-nadirah" alt="Nadirah">
                                <div class="student-heading">
                                    <span class="role-badge role-member"><i class="bi bi-people-fill"></i>Anggota Kelompok / Team Member</span>
                                    <h3>Nadirah Firyal Az Zahrah</h3>
                                </div>
                            </div>
                            <dl class="student-details">
                                <div><dt>NIM</dt><dd>243101030016</dd></div>
                                <div><dt>Tempat, Tanggal Lahir</dt><dd>Situbondo, 3 Mei 2006</dd></div>
                                <div><dt>Jenis Kelamin</dt><dd>Perempuan</dd></div>
                                <div><dt>Agama</dt><dd>Islam</dd></div>
                                <div><dt>Program Studi</dt><dd>D3 Teknik Telekomunikasi</dd></div>
                                <div><dt>Jurusan</dt><dd>Teknik Elektro - Politeknik Negeri Malang</dd></div>
                                <div><dt>Alamat</dt><dd>Jl. Semanggi Barat No. 29A, Kota Malang</dd></div>
                                <div><dt>No. HP</dt><dd>085280868123</dd></div>
                            </dl>
                            <blockquote class="student-motto">
                                <i class="bi bi-quote" aria-hidden="true"></i>
                                <div><span>Motto Hidup</span><p>Terus bertumbuh, tetap membumi. Mengejar pencapaian tinggi tanpa melupakan nilai-nilai kebaikan.</p></div>
                            </blockquote>
                        </div>
                    </article>
                </div>
            </section>

            <section class="profile-section" aria-labelledby="internshipTitle">
                <div class="profile-section-heading">
                    <div><span class="section-kicker">DUKUNGAN &amp; KOLABORASI</span><h2 id="internshipTitle">Pembimbing &amp; lokasi magang</h2></div>
                </div>
                <div class="support-grid">
                    <article class="support-card advisor-card">
                        <div class="support-icon"><i class="bi bi-mortarboard-fill"></i></div>
                        <div><span class="support-label">Dosen Pembimbing POLINEMA</span><h3>Dr. Farida Arinie Soelistianto, S.T., M.T.</h3></div>
                    </article>
                    <article class="support-card location-card">
                        <div class="support-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div><span class="support-label">Lokasi Industri</span><h3>PT Telkom Akses Malang</h3><p>Jl. Danau Sentani Raya No. 2, Madyopuro, Kec. Kedungkandang, Kota Malang</p></div>
                    </article>
                    <article class="support-card supervisor-person-card">
                        <div class="support-icon"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <span class="support-label">Pembimbing Mitra / Supervisor Lapangan</span>
                            <h3>Dhien Aulia Nabila</h3>
                            <p>Korlap B2C Malang 2</p>
                        </div>
                    </article>
                    <article class="support-card supervisor-person-card">
                        <div class="support-icon"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <span class="support-label">Pembimbing Mitra / Supervisor Lapangan</span>
                            <h3>Daniel Salmon Handoyo</h3>
                            <p>Officer 3 Maintenance</p>
                        </div>
                    </article>
                </div>
            </section>

            <footer class="profile-footer">Profil tim magang · D3 Teknik Telekomunikasi · Politeknik Negeri Malang</footer>
        </div>
    </main>
</div>
<div class="profile-lightbox" id="profileLightbox" role="dialog" aria-modal="true" aria-labelledby="profileLightboxTitle" hidden>
    <article class="profile-lightbox-dialog">
        <button class="profile-lightbox-close" id="profileLightboxClose" type="button" aria-label="Tutup foto"><i class="bi bi-x-lg"></i></button>
        <div class="profile-lightbox-image-wrap"><img id="profileLightboxImage" src="" alt=""></div>
        <div class="profile-lightbox-copy">
            <span class="section-kicker">PROFIL MAHASISWA</span>
            <h2 id="profileLightboxTitle"></h2>
            <span class="profile-lightbox-role" id="profileLightboxRole"></span>
            <dl class="profile-lightbox-details" id="profileLightboxDetails"></dl>
            <blockquote class="profile-lightbox-motto">
                <span class="profile-lightbox-motto-label"><i class="bi bi-quote" aria-hidden="true"></i> MOTTO HIDUP</span>
                <p id="profileLightboxMotto"></p>
            </blockquote>
        </div>
    </article>
</div>
<script src="assets/app.js?v=20261113-5"></script>
<script>
document.querySelectorAll('.profile-photo').forEach(function (photo) {
    function showFallback() {
        if (photo.dataset.fallbackApplied) return;
        photo.dataset.fallbackApplied = 'true';
        const sartika = photo.classList.contains('profile-photo-sartika');
        const background = sartika ? '#fff1f1' : '#eef3f8';
        const jacket = sartika ? '#454b58' : '#496482';
        const hairOrHijab = sartika
            ? '<path d="M35 44c0-23 10-35 25-35s25 12 25 35c-8-4-15-12-20-22-7 11-17 19-30 22" fill="#292a31"/>'
            : '<path d="M34 47c0-27 11-39 26-39s26 12 26 39v17c0 22-11 34-26 34S34 86 34 64z" fill="#fff"/>';
        const fallback = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120"><rect width="120" height="120" fill="' + background + '"/><path d="M10 120c4-30 23-45 50-45s46 15 50 45" fill="' + jacket + '"/><circle cx="60" cy="47" r="25" fill="#d6a17f"/>' + hairOrHijab + '</svg>';
        photo.src = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(fallback);
    }

    photo.addEventListener('error', showFallback);
    if (photo.complete && photo.naturalWidth === 0) showFallback();
});

var profileModal = document.getElementById('profileLightbox');
var profileModalImage = document.getElementById('profileLightboxImage');
var profileModalTitle = document.getElementById('profileLightboxTitle');
var profileModalRole = document.getElementById('profileLightboxRole');
var profileModalDetails = document.getElementById('profileLightboxDetails');
var profileModalMotto = document.getElementById('profileLightboxMotto');
var profileModalClose = document.getElementById('profileLightboxClose');
var profilePreviousFocus = null;
var profilePreviousOverflow = '';

function openProfileModal(card) {
    var photo = card.querySelector('.profile-photo');
    var name = card.querySelector('.student-heading h3').textContent.trim();
    var role = card.querySelector('.role-badge').textContent.trim();
    var details = Array.from(card.querySelectorAll('.student-details > div')).map(function (row) {
        return {
            label: row.querySelector('dt').textContent.trim(),
            value: row.querySelector('dd').textContent.trim()
        };
    });
    var motto = card.querySelector('.student-motto p');

    profilePreviousFocus = card;
    profileModalTitle.textContent = name;
    profileModalRole.textContent = role;
    profileModalDetails.replaceChildren();
    details.forEach(function (item) {
        var entry = document.createElement('div');
        var label = document.createElement('dt');
        var value = document.createElement('dd');
        label.textContent = item.label;
        value.textContent = item.value;
        entry.append(label, value);
        profileModalDetails.appendChild(entry);
    });
    profileModalMotto.textContent = motto ? '“' + motto.textContent.trim() + '”' : 'Belum ada motto.';
    profileModalImage.src = photo.src;
    profileModalImage.alt = photo.alt;
    profilePreviousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    profileModal.hidden = false;
    profileModalClose.focus();
}

function closeProfileLightbox() {
    if (profileModal.hidden) return;
    profileModal.hidden = true;
    profileModalImage.removeAttribute('src');
    document.body.style.overflow = profilePreviousOverflow;
    if (profilePreviousFocus) profilePreviousFocus.focus();
}

document.querySelectorAll('.student-card').forEach(function (card) {
    card.addEventListener('click', function (event) {
        if (event.target.closest('a, button')) return;
        openProfileModal(card);
    });
    card.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openProfileModal(card);
        }
    });
});

profileModalClose.addEventListener('click', closeProfileLightbox);
profileModal.addEventListener('click', function (event) {
    if (event.target === profileModal) closeProfileLightbox();
});
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !profileModal.hidden) closeProfileLightbox();
});
</script>
</body>
</html>
