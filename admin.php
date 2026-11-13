<?php
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/include/portfolio-storage.php';

if (empty($_SESSION['logbook_csrf'])) {
    $_SESSION['logbook_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['logbook_csrf'];
$error = '';
$notice = '';
$submittedValues = [];
$uploadedFiles = [];
$uploadDirectory = __DIR__ . '/uploads';
$portfolioUploadDirectory = __DIR__ . '/assets/img/portofolio';
$categoryOptions = ['Observasi', 'Dokumentasi', 'Pengembangan', 'Pendalaman Materi', 'Diskusi'];
$portfolioCategories = ['Looker Studio', 'Google Form', 'LARAISA / Audit'];

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function requireValidCsrf($token)
{
    if (!is_string($token) || !hash_equals($_SESSION['logbook_csrf'], $token)) {
        throw new RuntimeException('Sesi formulir tidak valid. Muat ulang halaman, lalu coba lagi.');
    }
}

function collectImageUploads($field, $kind, $directory, &$storedFiles)
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return [];
    }

    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Folder uploads tidak tersedia atau tidak dapat ditulis.');
    }

    $files = [];
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($_FILES[$field]['name'] as $index => $originalName) {
        $uploadError = $_FILES[$field]['error'][$index];
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Salah satu foto gagal diunggah. Periksa ukuran dan konfigurasi upload PHP.');
        }

        $temporaryPath = $_FILES[$field]['tmp_name'][$index];
        $size = (int) $_FILES[$field]['size'][$index];
        $mimeType = $fileInfo->file($temporaryPath);
        if ($size <= 0 || $size > 8 * 1024 * 1024 || !isset($allowedMimeTypes[$mimeType])) {
            throw new RuntimeException('Foto harus JPG, PNG, atau WEBP dan berukuran maksimal 8 MB per file.');
        }
        if (getimagesize($temporaryPath) === false) {
            throw new RuntimeException('File yang diunggah bukan gambar yang valid.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mimeType];
        $destination = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryPath, $destination)) {
            throw new RuntimeException('Foto tidak dapat disimpan ke folder uploads.');
        }

        $storedFiles[] = $destination;
        $files[] = ['name' => $filename, 'type' => $kind];
    }

    return $files;
}

function removeStoredFiles(array $files)
{
    foreach ($files as $file) {
        if (is_file($file) && !unlink($file)) {
            error_log('Unable to remove logbook upload: ' . $file);
        }
    }
}

function collectPortfolioImageUpload($field, $directory, &$storedFiles)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Screenshot gagal diunggah. Periksa ukuran dan konfigurasi upload PHP.');
    }

    $temporaryPath = $_FILES[$field]['tmp_name'];
    $size = (int) $_FILES[$field]['size'];
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($temporaryPath);
    $allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if ($size <= 0 || $size > 8 * 1024 * 1024 || !isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Screenshot harus JPG atau PNG dan berukuran maksimal 8 MB.');
    }
    if (getimagesize($temporaryPath) === false) {
        throw new RuntimeException('File yang diunggah bukan gambar yang valid.');
    }
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder screenshot portofolio tidak dapat dibuat.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('Folder screenshot portofolio tidak dapat ditulis.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mimeType];
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('Screenshot tidak dapat disimpan.');
    }

    $storedFiles[] = $destination;
    return $filename;
}

function removePortfolioImage($filename, $directory)
{
    $filename = basename((string) $filename);
    if ($filename !== '' && preg_match('/\A[a-f0-9]{32}\.(jpg|png)\z/', $filename)) {
        removeStoredFiles([$directory . DIRECTORY_SEPARATOR . $filename]);
    }
}

function removeUnreferencedPhotoFiles(mysqli $conn, array $filenames, $directory)
{
    $referenceCheck = $conn->prepare('SELECT COUNT(*) AS reference_count FROM dokumentasi WHERE nama_file = ?');
    foreach (array_unique($filenames) as $filename) {
        $filename = basename($filename);
        $referenceCheck->bind_param('s', $filename);
        $referenceCheck->execute();
        $referenceCount = (int) $referenceCheck->get_result()->fetch_assoc()['reference_count'];
        if ($referenceCount === 0) {
            removeStoredFiles([$directory . DIRECTORY_SEPARATOR . $filename]);
        }
    }
    $referenceCheck->close();
}

$authenticated = !empty($_SESSION['logbook_admin_authenticated']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireValidCsrf($_POST['csrf_token'] ?? null);

        if (($_POST['action'] ?? '') === 'login') {
            if ($adminPassword === '') {
                throw new RuntimeException('Admin belum dikonfigurasi. Atur LOGBOOK_ADMIN_PASSWORD di environment server.');
            }
            if (!hash_equals($adminPassword, (string) ($_POST['password'] ?? ''))) {
                throw new RuntimeException('Kata sandi admin tidak sesuai.');
            }

            session_regenerate_id(true);
            $_SESSION['logbook_admin_authenticated'] = true;
            header('Location: admin.php');
            exit;
        }

        if (!$authenticated) {
            throw new RuntimeException('Silakan login terlebih dahulu.');
        }

        $action = isset($_POST['delete_all_photos'])
            ? 'delete_all_photos'
            : (isset($_POST['delete_document_id']) ? 'delete_photo' : ($_POST['action'] ?? ''));
        if ($action === 'logout') {
            $_SESSION = [];
            session_destroy();
            header('Location: admin.php');
            exit;
        }

        if ($action === 'delete') {
            $activityId = filter_var($_POST['id_kegiatan'] ?? null, FILTER_VALIDATE_INT);
            if (!$activityId || $activityId < 1) {
                throw new RuntimeException('Kegiatan yang dipilih tidak valid.');
            }

            $photoType = 'kegiatan';
            $stmt = $conn->prepare('SELECT nama_file FROM dokumentasi WHERE id_kegiatan = ? AND jenis_foto = ?');
            $stmt->bind_param('is', $activityId, $photoType);
            $stmt->execute();
            $result = $stmt->get_result();
            $filenamesToCheck = [];
            while ($document = $result->fetch_assoc()) {
                $filenamesToCheck[] = basename($document['nama_file']);
            }
            $stmt->close();

            $conn->begin_transaction();
            $stmt = $conn->prepare('DELETE FROM kegiatan WHERE id_kegiatan = ?');
            $stmt->bind_param('i', $activityId);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();
            $conn->commit();

            if ($deleted === 0) {
                throw new RuntimeException('Kegiatan tidak ditemukan atau sudah dihapus.');
            }
            removeUnreferencedPhotoFiles($conn, $filenamesToCheck, $uploadDirectory);
            header('Location: admin.php?status=deleted');
            exit;
        }

        if ($action === 'delete_photo') {
            $activityId = filter_var($_POST['id_kegiatan'] ?? null, FILTER_VALIDATE_INT);
            $documentId = filter_var($_POST['delete_document_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$activityId || $activityId < 1 || !$documentId || $documentId < 1) {
                throw new RuntimeException('Foto yang dipilih tidak valid.');
            }

            $stmt = $conn->prepare('SELECT nama_file FROM dokumentasi WHERE id_dokumentasi = ? AND id_kegiatan = ?');
            $stmt->bind_param('ii', $documentId, $activityId);
            $stmt->execute();
            $document = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$document) {
                throw new RuntimeException('Foto tidak ditemukan pada kegiatan ini.');
            }

            $conn->begin_transaction();
            $stmt = $conn->prepare('DELETE FROM dokumentasi WHERE id_dokumentasi = ? AND id_kegiatan = ?');
            $stmt->bind_param('ii', $documentId, $activityId);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();
            $conn->commit();
            if ($deleted !== 1) {
                throw new RuntimeException('Foto tidak berhasil dihapus dari dokumentasi.');
            }

            removeUnreferencedPhotoFiles($conn, [$document['nama_file']], $uploadDirectory);
            header('Location: admin.php?edit=' . $activityId . '&status=photo_deleted');
            exit;
        }

        if ($action === 'delete_all_photos') {
            $activityId = filter_var($_POST['id_kegiatan'] ?? null, FILTER_VALIDATE_INT);
            if (!$activityId || $activityId < 1) {
                throw new RuntimeException('Kegiatan yang dipilih tidak valid.');
            }

            $photoType = 'kegiatan';
            $stmt = $conn->prepare('SELECT nama_file FROM dokumentasi WHERE id_kegiatan = ? AND jenis_foto = ?');
            $stmt->bind_param('is', $activityId, $photoType);
            $stmt->execute();
            $result = $stmt->get_result();
            $filenamesToCheck = [];
            while ($document = $result->fetch_assoc()) {
                $filenamesToCheck[] = basename($document['nama_file']);
            }
            $stmt->close();

            $conn->begin_transaction();
            $stmt = $conn->prepare('DELETE FROM dokumentasi WHERE id_kegiatan = ? AND jenis_foto = ?');
            $stmt->bind_param('is', $activityId, $photoType);
            $stmt->execute();
            $deletedCount = $stmt->affected_rows;
            $stmt->close();
            $conn->commit();

            removeUnreferencedPhotoFiles($conn, $filenamesToCheck, $uploadDirectory);
            header(
                'Location: admin.php?edit=' . $activityId
                . '&status=activity_photos_deleted&count=' . $deletedCount
            );
            exit;
        }

        if ($action === 'delete_portfolio') {
            $portfolioId = filter_var($_POST['portfolio_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$portfolioId || $portfolioId < 1) {
                throw new RuntimeException('Portofolio yang dipilih tidak valid.');
            }

            $stmt = $conn->prepare('SELECT gambar FROM portofolio WHERE id = ?');
            $stmt->bind_param('i', $portfolioId);
            $stmt->execute();
            $portfolio = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$portfolio) {
                throw new RuntimeException('Data portofolio tidak ditemukan atau sudah dihapus.');
            }

            $conn->begin_transaction();
            $stmt = $conn->prepare('DELETE FROM portofolio WHERE id = ?');
            $stmt->bind_param('i', $portfolioId);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();
            $conn->commit();
            if ($deleted !== 1) {
                throw new RuntimeException('Data portofolio tidak berhasil dihapus.');
            }

            removePortfolioImage($portfolio['gambar'], $portfolioUploadDirectory);
            header('Location: admin.php?status=portfolio_deleted');
            exit;
        }

        if ($action === 'save_portfolio') {
            $portfolioId = filter_var($_POST['portfolio_id'] ?? '', FILTER_VALIDATE_INT);
            $portfolioId = $portfolioId ? (int) $portfolioId : 0;
            $portfolioCategoryInput = $_POST['portfolio_kategori'] ?? '';
            $portfolioTitleInput = $_POST['portfolio_judul'] ?? '';
            $portfolioDescriptionInput = $_POST['portfolio_deskripsi'] ?? '';
            if (!is_string($portfolioCategoryInput) || !is_string($portfolioTitleInput) || !is_string($portfolioDescriptionInput)) {
                throw new RuntimeException('Data portofolio tidak valid.');
            }
            $portfolioCategory = trim($portfolioCategoryInput);
            $portfolioTitle = trim($portfolioTitleInput);
            $portfolioDescription = trim($portfolioDescriptionInput);
            $portfolioUrlInput = $_POST['portfolio_url_external'] ?? '';
            if (!is_string($portfolioUrlInput)) {
                throw new RuntimeException('URL eksternal tidak valid.');
            }
            $portfolioUrl = trim($portfolioUrlInput);
            $portfolioOrder = filter_var($_POST['portfolio_urutan'] ?? '0', FILTER_VALIDATE_INT);
            if ($portfolioOrder === false || $portfolioOrder < 0) {
                throw new RuntimeException('Urutan harus berupa angka nol atau lebih.');
            }
            if (!in_array($portfolioCategory, $portfolioCategories, true)) {
                throw new RuntimeException('Pilih kategori portofolio yang valid.');
            }
            if ($portfolioTitle === '' || $portfolioDescription === '') {
                throw new RuntimeException('Judul dan deskripsi portofolio wajib diisi.');
            }
            if ($portfolioUrl !== '') {
                $urlParts = parse_url($portfolioUrl);
                if (
                    !filter_var($portfolioUrl, FILTER_VALIDATE_URL)
                    || !is_array($urlParts)
                    || !in_array(strtolower($urlParts['scheme'] ?? ''), ['http', 'https'], true)
                    || empty($urlParts['host'])
                ) {
                    throw new RuntimeException('URL eksternal harus berupa alamat HTTP atau HTTPS yang valid.');
                }
            }

            $existingPortfolio = null;
            if ($portfolioId > 0) {
                $stmt = $conn->prepare('SELECT gambar FROM portofolio WHERE id = ?');
                $stmt->bind_param('i', $portfolioId);
                $stmt->execute();
                $existingPortfolio = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$existingPortfolio) {
                    throw new RuntimeException('Portofolio yang akan diedit tidak ditemukan.');
                }
            }

            $newImage = collectPortfolioImageUpload('portfolio_gambar', $portfolioUploadDirectory, $uploadedFiles);
            if ($portfolioId > 0 && $newImage === '' && empty($existingPortfolio['gambar'])) {
                throw new RuntimeException('Unggah screenshot untuk portofolio ini.');
            }
            if ($portfolioId === 0 && $newImage === '') {
                throw new RuntimeException('Unggah screenshot untuk portofolio baru.');
            }
            $portfolioImage = $newImage !== '' ? $newImage : $existingPortfolio['gambar'];

            $conn->begin_transaction();
            if ($portfolioId > 0) {
                $stmt = $conn->prepare(
                    'UPDATE portofolio SET kategori = ?, judul = ?, deskripsi = ?, gambar = ?, url_external = ?, urutan = ? WHERE id = ?'
                );
                $stmt->bind_param('sssssii', $portfolioCategory, $portfolioTitle, $portfolioDescription, $portfolioImage, $portfolioUrl, $portfolioOrder, $portfolioId);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO portofolio (kategori, judul, deskripsi, gambar, url_external, urutan) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('sssssi', $portfolioCategory, $portfolioTitle, $portfolioDescription, $portfolioImage, $portfolioUrl, $portfolioOrder);
                $stmt->execute();
                $portfolioId = (int) $conn->insert_id;
                $stmt->close();
            }
            $conn->commit();

            if ($newImage !== '' && !empty($existingPortfolio['gambar'])) {
                removePortfolioImage($existingPortfolio['gambar'], $portfolioUploadDirectory);
            }
            header('Location: admin.php?status=portfolio_saved');
            exit;
        }

        if ($action !== 'save') {
            throw new RuntimeException('Aksi tidak dikenali.');
        }

        $activityId = filter_var($_POST['id_kegiatan'] ?? '', FILTER_VALIDATE_INT);
        $activityId = $activityId ? (int) $activityId : 0;
        $date = trim((string) ($_POST['tanggal_kegiatan'] ?? ''));
        $title = trim((string) ($_POST['judul_kegiatan'] ?? ''));
        $postedCategories = $_POST['kategori'] ?? [];
        if (!is_array($postedCategories)) {
            throw new RuntimeException('Pilihan kategori tidak valid.');
        }
        $categories = [];
        foreach ($postedCategories as $postedCategory) {
            if (!is_string($postedCategory)) {
                throw new RuntimeException('Pilihan kategori tidak valid.');
            }
            $postedCategory = trim($postedCategory);
            if (!in_array($postedCategory, $categoryOptions, true)) {
                throw new RuntimeException('Kategori kegiatan tidak valid.');
            }
            if (!in_array($postedCategory, $categories, true)) {
                $categories[] = $postedCategory;
            }
        }
        $category = implode(', ', $categories);
        $time = trim((string) ($_POST['waktu_kegiatan'] ?? ''));
        $description = trim((string) ($_POST['deskripsi'] ?? ''));
        $learning = trim((string) ($_POST['hasil_pembelajaran'] ?? ''));
        $status = trim((string) ($_POST['status_kegiatan'] ?? 'Selesai'));
        $participants = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['peserta'] ?? [])),
            function ($id) {
                return $id > 0;
            }
        )));

        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
            throw new RuntimeException('Tanggal kegiatan harus diisi dengan format yang valid.');
        }
        if ($title === '' || !$categories || $time === '' || $description === '') {
            throw new RuntimeException('Lengkapi judul, kategori, waktu, dan deskripsi kegiatan.');
        }
        if ($status !== 'Izin Sakit' && $learning === '') {
            throw new RuntimeException('Hasil pembelajaran wajib diisi kecuali status kegiatan Izin Sakit.');
        }
        if ($status === '') {
            $status = 'Selesai';
        }
        if (!$participants) {
            throw new RuntimeException('Pilih minimal satu peserta kegiatan.');
        }

        $validParticipants = [];
        $participantResult = $conn->query('SELECT id_mahasiswa FROM mahasiswa');
        while ($participant = $participantResult->fetch_assoc()) {
            $validParticipants[] = (int) $participant['id_mahasiswa'];
        }
        if (array_diff($participants, $validParticipants)) {
            throw new RuntimeException('Pilihan peserta tidak valid.');
        }

        $newDocuments = array_merge(
            collectImageUploads('foto_kegiatan', 'kegiatan', $uploadDirectory, $uploadedFiles),
            collectImageUploads('foto_absen_masuk', 'absen_masuk', $uploadDirectory, $uploadedFiles),
            collectImageUploads('foto_absen_pulang', 'absen_pulang', $uploadDirectory, $uploadedFiles)
        );
        $conn->begin_transaction();
        if ($activityId > 0) {
            $stmt = $conn->prepare(
                'UPDATE kegiatan SET tanggal_kegiatan = ?, judul_kegiatan = ?, kategori = ?, waktu_kegiatan = ?, deskripsi = ?, hasil_pembelajaran = ?, status_kegiatan = ? WHERE id_kegiatan = ?'
            );
            $stmt->bind_param('sssssssi', $date, $title, $category, $time, $description, $learning, $status, $activityId);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                $check = $conn->prepare('SELECT id_kegiatan FROM kegiatan WHERE id_kegiatan = ?');
                $check->bind_param('i', $activityId);
                $check->execute();
                $exists = $check->get_result()->num_rows > 0;
                $check->close();
                if (!$exists) {
                    throw new RuntimeException('Kegiatan yang akan diedit tidak ditemukan.');
                }
            }
            $stmt->close();
            $stmt = $conn->prepare('DELETE FROM peserta_kegiatan WHERE id_kegiatan = ?');
            $stmt->bind_param('i', $activityId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO kegiatan (tanggal_kegiatan, judul_kegiatan, kategori, waktu_kegiatan, deskripsi, hasil_pembelajaran, status_kegiatan) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssssss', $date, $title, $category, $time, $description, $learning, $status);
            $stmt->execute();
            $activityId = (int) $conn->insert_id;
            $stmt->close();
        }

        $participantInsert = $conn->prepare('INSERT INTO peserta_kegiatan (id_kegiatan, id_mahasiswa) VALUES (?, ?)');
        foreach ($participants as $participantId) {
            $participantInsert->bind_param('ii', $activityId, $participantId);
            $participantInsert->execute();
        }
        $participantInsert->close();

        $documentInsert = $conn->prepare('INSERT INTO dokumentasi (id_kegiatan, nama_file, jenis_foto) VALUES (?, ?, ?)');
        foreach ($newDocuments as $document) {
            $documentInsert->bind_param('iss', $activityId, $document['name'], $document['type']);
            $documentInsert->execute();
        }
        $documentInsert->close();
        $conn->commit();

        header('Location: admin.php?status=saved');
        exit;
    } catch (RuntimeException $exception) {
        if ($conn->errno || $conn->thread_id) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackException) {
                error_log($rollbackException->getMessage());
            }
        }
        removeStoredFiles($uploadedFiles);
        $error = $exception->getMessage();
        if (in_array($_POST['action'] ?? '', ['save', 'save_portfolio'], true) || isset($_POST['delete_document_id']) || isset($_POST['delete_all_photos'])) {
            $submittedValues = $_POST;
        }
    } catch (Throwable $exception) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackException) {
            error_log($rollbackException->getMessage());
        }
        removeStoredFiles($uploadedFiles);
        error_log('Logbook admin error: ' . $exception->getMessage());
        $error = 'Data belum dapat disimpan. Periksa konfigurasi database dan folder uploads, lalu coba lagi.';
        if (in_array($_POST['action'] ?? '', ['save', 'save_portfolio'], true) || isset($_POST['delete_document_id']) || isset($_POST['delete_all_photos'])) {
            $submittedValues = $_POST;
        }
    }

    $authenticated = !empty($_SESSION['logbook_admin_authenticated']);
}

$editActivity = null;
$editParticipants = [];
$editDocuments = [];
$duplicateSourceForForm = $_GET['duplicate'] ?? ($submittedValues['duplicate_source_id'] ?? null);
$duplicateMode = $authenticated && $duplicateSourceForForm !== null && !isset($_GET['edit']);
$duplicateValues = [];
$requestedEditId = $_GET['edit'] ?? ($duplicateMode ? $duplicateSourceForForm : ($submittedValues['id_kegiatan'] ?? null));
if ($authenticated && $requestedEditId !== null) {
    $editId = filter_var($requestedEditId, FILTER_VALIDATE_INT);
    if ($editId && $editId > 0) {
        $stmt = $conn->prepare('SELECT * FROM kegiatan WHERE id_kegiatan = ?');
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $loadedActivity = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($loadedActivity && $duplicateMode) {
            $duplicateValues = [
                'judul_kegiatan' => $loadedActivity['judul_kegiatan'],
                'kategori' => $loadedActivity['kategori'],
                'waktu_kegiatan' => $loadedActivity['waktu_kegiatan'],
                'deskripsi' => $loadedActivity['deskripsi'],
                'hasil_pembelajaran' => $loadedActivity['hasil_pembelajaran'],
                'status_kegiatan' => $loadedActivity['status_kegiatan'],
            ];
        } elseif ($loadedActivity) {
            $editActivity = $loadedActivity;
        }
        if ($loadedActivity) {
            $stmt = $conn->prepare('SELECT id_mahasiswa FROM peserta_kegiatan WHERE id_kegiatan = ?');
            $stmt->bind_param('i', $editId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $editParticipants[] = (int) $row['id_mahasiswa'];
            }
            $stmt->close();
            if (!$duplicateMode) {
                $stmt = $conn->prepare('SELECT id_dokumentasi, nama_file, jenis_foto FROM dokumentasi WHERE id_kegiatan = ? ORDER BY id_dokumentasi');
                $stmt->bind_param('i', $editId);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $editDocuments[] = $row;
                }
                $stmt->close();
            }
        }
    }
}

$hasActivityPhotos = count(array_filter($editDocuments, function ($document) {
    return $document['jenis_foto'] === 'kegiatan';
})) > 0;

$formValue = function ($key, $default = '') use ($submittedValues, $editActivity, $duplicateValues, $duplicateMode) {
    if (array_key_exists($key, $submittedValues)) {
        return is_scalar($submittedValues[$key]) ? (string) $submittedValues[$key] : $default;
    }
    if ($duplicateMode && array_key_exists($key, $duplicateValues)) {
        return $duplicateValues[$key];
    }
    return $editActivity[$key] ?? $default;
};
$selectedCategories = array_key_exists('kategori', $submittedValues)
    ? (is_array($submittedValues['kategori']) ? $submittedValues['kategori'] : [])
    : array_values(array_filter(array_map('trim', explode(',', (string) $formValue('kategori')))));
$selectedParticipants = $submittedValues
    ? array_map('intval', (array) ($submittedValues['peserta'] ?? []))
    : $editParticipants;
$students = [];
$editPortfolio = null;
$requestedPortfolioId = $_GET['edit_portfolio'] ?? ($submittedValues['portfolio_id'] ?? null);
$portfolios = [];
if ($authenticated) {
    $studentResult = $conn->query('SELECT id_mahasiswa, nama_mahasiswa FROM mahasiswa ORDER BY nama_mahasiswa');
    while ($student = $studentResult->fetch_assoc()) {
        $students[] = $student;
    }
    $activitiesResult = $conn->query(
        'SELECT k.id_kegiatan, k.tanggal_kegiatan, k.judul_kegiatan, k.kategori, k.status_kegiatan, GROUP_CONCAT(DISTINCT m.nama_mahasiswa ORDER BY m.nama_mahasiswa SEPARATOR ", ") AS peserta FROM kegiatan k LEFT JOIN peserta_kegiatan pk ON pk.id_kegiatan = k.id_kegiatan LEFT JOIN mahasiswa m ON m.id_mahasiswa = pk.id_mahasiswa GROUP BY k.id_kegiatan ORDER BY k.tanggal_kegiatan DESC, k.id_kegiatan DESC'
    );
    $activities = $activitiesResult->fetch_all(MYSQLI_ASSOC);

    if ($requestedPortfolioId !== null) {
        $portfolioId = filter_var($requestedPortfolioId, FILTER_VALIDATE_INT);
        if ($portfolioId && $portfolioId > 0) {
            $stmt = $conn->prepare('SELECT * FROM portofolio WHERE id = ?');
            $stmt->bind_param('i', $portfolioId);
            $stmt->execute();
            $editPortfolio = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    $portfolioResult = $conn->query('SELECT * FROM portofolio ORDER BY urutan ASC, id ASC');
    $portfolios = $portfolioResult->fetch_all(MYSQLI_ASSOC);
}

$portfolioFormValue = function ($key, $default = '') use ($submittedValues, $editPortfolio) {
    $submittedKey = [
        'id' => 'portfolio_id',
        'kategori' => 'portfolio_kategori',
        'judul' => 'portfolio_judul',
        'deskripsi' => 'portfolio_deskripsi',
        'gambar' => 'portfolio_gambar',
        'url_external' => 'portfolio_url_external',
        'urutan' => 'portfolio_urutan',
    ][$key] ?? $key;
    if (array_key_exists($submittedKey, $submittedValues)) {
        return is_scalar($submittedValues[$submittedKey]) ? (string) $submittedValues[$submittedKey] : $default;
    }
    return $editPortfolio[$key] ?? $default;
};

if (($_GET['status'] ?? '') === 'saved') {
    $notice = 'Data kegiatan berhasil disimpan.';
} elseif (($_GET['status'] ?? '') === 'deleted') {
    $notice = 'Kegiatan dan dokumentasinya berhasil dihapus.';
} elseif (($_GET['status'] ?? '') === 'photo_deleted') {
    $notice = 'Foto berhasil dihapus.';
} elseif (($_GET['status'] ?? '') === 'activity_photos_deleted') {
    $deletedCount = filter_var($_GET['count'] ?? 0, FILTER_VALIDATE_INT);
    $notice = $deletedCount > 0
        ? $deletedCount . ' foto dokumentasi kegiatan berhasil dihapus. Foto absensi tidak diubah.'
        : 'Tidak ada foto dokumentasi kegiatan untuk dihapus. Foto absensi tidak diubah.';
} elseif (($_GET['status'] ?? '') === 'portfolio_saved') {
    $notice = 'Data portofolio berhasil disimpan.';
} elseif (($_GET['status'] ?? '') === 'portfolio_deleted') {
    $notice = 'Data portofolio dan screenshot berhasil dihapus.';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Logbook | PT Telkom Akses Malang</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/style.css?v=20261112-1" rel="stylesheet">
    <link href="assets/motion.css?v=20261113-2" rel="stylesheet">
</head>
<body class="admin-page">
<main class="admin-wrap">
    <header class="admin-header">
        <a class="brand" href="index.php">
            <span class="brand-mark"><i class="bi bi-journal-bookmark-fill"></i></span>
            <span><b>LOGBOOK</b><small>ADMIN MAGANG</small></span>
        </a>
        <a class="btn btn-outline-secondary" href="index.php"><i class="bi bi-box-arrow-up-right me-1"></i> Lihat halaman publik</a>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($notice !== ''): ?>
        <div class="alert alert-success" role="status"><?= h($notice) ?></div>
    <?php endif; ?>
    <?php if ($duplicateMode): ?>
        <div class="alert alert-info" role="status">
            Data kegiatan sudah disalin ke formulir tambah. Pilih tanggal baru sebelum menyimpan. Foto dari kegiatan asal tidak ikut disalin; unggah foto baru jika diperlukan.
        </div>
    <?php endif; ?>

    <?php if (!$authenticated): ?>
        <section class="admin-login filter-panel">
            <span class="section-kicker">AREA PRIVAT</span>
            <h1>Masuk ke pengelola</h1>
            <p class="text-secondary">Halaman ini hanya untuk pengelolaan logbook.</p>
            <form method="post" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="login">
                <div class="field-group">
                    <label for="password">Kata sandi admin</label>
                    <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-brand mt-3" type="submit"><i class="bi bi-lock me-2"></i>Masuk</button>
            </form>
        </section>
    <?php else: ?>
        <div class="admin-title-row">
            <div>
                <span class="section-kicker">PENGELOLAAN LOGBOOK</span>
                <h1>Data kegiatan magang</h1>
                <p class="text-secondary mb-0">Kelola aktivitas, peserta, dokumentasi, dan absensi harian.</p>
            </div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="logout">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Keluar</button>
            </form>
        </div>

        <section class="filter-panel admin-form-panel">
            <div class="section-heading">
                <div>
                    <span class="section-kicker"><?= $editActivity ? 'UBAH CATATAN' : 'CATATAN BARU' ?></span>
                    <h3><?= $editActivity ? 'Edit kegiatan' : 'Input kegiatan harian' ?></h3>
                </div>
                <?php if ($editActivity): ?>
                    <a class="reset-link" href="admin.php"><i class="bi bi-x-circle me-1"></i>Batal mengedit</a>
                <?php endif; ?>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id_kegiatan" value="<?= h($editActivity['id_kegiatan'] ?? '') ?>">
                <?php if ($duplicateMode): ?>
                    <input type="hidden" name="duplicate_source_id" value="<?= h($duplicateSourceForForm) ?>">
                <?php endif; ?>
                <div class="admin-form-grid">
                    <div class="field-group">
                        <label for="tanggal_kegiatan">Tanggal kegiatan *</label>
                        <input class="form-control" id="tanggal_kegiatan" name="tanggal_kegiatan" type="date" value="<?= h($formValue('tanggal_kegiatan', $duplicateMode ? '' : date('Y-m-d'))) ?>" <?= $duplicateMode ? 'autofocus' : '' ?> required>
                    </div>
                    <div class="field-group">
                        <label for="waktu_kegiatan">Waktu kegiatan *</label>
                        <input class="form-control" id="waktu_kegiatan" name="waktu_kegiatan" maxlength="50" placeholder="Contoh: 08:00 - 16:00" value="<?= h($formValue('waktu_kegiatan')) ?>" required>
                    </div>
                    <fieldset class="field-group admin-field-wide">
                        <legend class="form-label">Kategori * <small class="text-secondary">(pilih satu atau lebih)</small></legend>
                        <div class="participant-options">
                            <?php foreach ($categoryOptions as $option): ?>
                                <label class="participant-option">
                                    <input type="checkbox" name="kategori[]" value="<?= h($option) ?>" <?= in_array($option, $selectedCategories, true) ? 'checked' : '' ?>>
                                    <span><?= h($option) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <div class="field-group">
                        <label for="status_kegiatan">Status</label>
                        <select class="form-select" id="status_kegiatan" name="status_kegiatan">
                            <?php
                            $currentStatus = $formValue('status_kegiatan', 'Selesai');
                            $statusOptions = ['Selesai', 'Izin Sakit'];
                            if (!in_array($currentStatus, $statusOptions, true)) {
                                $statusOptions[] = $currentStatus;
                            }
                            ?>
                            <?php foreach ($statusOptions as $option): ?>
                                <option value="<?= h($option) ?>" <?= $currentStatus === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group admin-field-wide">
                        <label for="judul_kegiatan">Judul kegiatan *</label>
                        <input class="form-control" id="judul_kegiatan" name="judul_kegiatan" maxlength="200" value="<?= h($formValue('judul_kegiatan')) ?>" required>
                    </div>
                    <fieldset class="field-group admin-field-wide">
                        <legend class="form-label">Peserta kegiatan *</legend>
                        <div class="participant-options">
                            <?php foreach ($students as $student): ?>
                                <label class="participant-option">
                                    <input type="checkbox" name="peserta[]" value="<?= (int) $student['id_mahasiswa'] ?>" <?= in_array((int) $student['id_mahasiswa'], $selectedParticipants, true) || (!$editActivity && !$submittedValues && !$editParticipants) ? 'checked' : '' ?>>
                                    <span><?= h($student['nama_mahasiswa']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <div class="field-group">
                        <label for="deskripsi">Deskripsi pekerjaan *</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?= h($formValue('deskripsi')) ?></textarea>
                    </div>
                    <div class="field-group">
                        <label for="hasil_pembelajaran">Hasil pembelajaran <span id="learningRequiredMark">*</span></label>
                        <textarea class="form-control" id="hasil_pembelajaran" name="hasil_pembelajaran" rows="5"><?= h($formValue('hasil_pembelajaran')) ?></textarea>
                        <small class="form-hint" id="sickStatusHint" hidden>Hasil pembelajaran dan foto bersifat opsional untuk status Izin Sakit.</small>
                    </div>
                    <div class="field-group">
                        <label for="foto_kegiatan">Foto dokumentasi lapangan/kerja</label>
                        <input class="form-control" id="foto_kegiatan" name="foto_kegiatan[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        <small class="form-hint">JPG, PNG, WEBP; maksimal 8 MB per foto. Foto kegiatan asal tidak ikut saat duplikasi; pilih foto baru bila diperlukan.</small>
                    </div>
                    <div class="field-group">
                        <label for="foto_absen_masuk">Foto absen masuk (pagi)</label>
                        <input class="form-control" id="foto_absen_masuk" name="foto_absen_masuk[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        <small class="form-hint">Pilih satu atau beberapa foto kamera belakang; unggahan baru ditambahkan ke data yang ada.</small>
                    </div>
                    <div class="field-group">
                        <label for="foto_absen_pulang">Foto absen pulang (sore)</label>
                        <input class="form-control" id="foto_absen_pulang" name="foto_absen_pulang[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                        <small class="form-hint">Pilih satu atau beberapa foto selfie sore; tambahkan catatan ringkas pada deskripsi/hasil pembelajaran.</small>
                    </div>
                    <?php if ($editDocuments): ?>
                        <div class="field-group admin-field-wide">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <span class="form-label mb-0">Foto tersimpan <small class="text-secondary">(foto baru akan ditambahkan; hapus foto lama satu per satu jika diperlukan)</small></span>
                                <?php if ($hasActivityPhotos): ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" name="delete_all_photos" value="1" formnovalidate onclick="return confirm('Hapus semua foto dokumentasi kegiatan ini? Foto absen masuk dan pulang tidak akan dihapus.')">
                                        <i class="bi bi-trash me-1"></i>Hapus semua foto dokumentasi
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="admin-photo-list">
                                <?php foreach ($editDocuments as $document): ?>
                                    <div class="admin-photo-item">
                                        <a href="uploads/<?= rawurlencode($document['nama_file']) ?>" target="_blank" rel="noopener">
                                            <img src="uploads/<?= rawurlencode($document['nama_file']) ?>" alt="<?= h($document['jenis_foto']) ?>">
                                        </a>
                                        <span><?= h(str_replace('_', ' ', $document['jenis_foto'])) ?></span>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" name="delete_document_id" value="<?= (int) $document['id_dokumentasi'] ?>" formnovalidate onclick="return confirm('Hapus foto ini?')">
                                            <i class="bi bi-trash me-1"></i>Hapus foto
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="admin-form-actions">
                    <button class="btn btn-brand" type="submit"><i class="bi bi-save me-2"></i><?= $editActivity ? 'Update Kegiatan' : 'Simpan kegiatan' ?></button>
                </div>
            </form>
        </section>

        <section class="filter-panel">
            <div class="section-heading">
                <div><span class="section-kicker">REKAP</span><h3>Kegiatan yang sudah dicatat</h3></div>
                <span class="record-count"><?= count($activities) ?> kegiatan</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Peserta</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php if (!$activities): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada data kegiatan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?= h(date('d M Y', strtotime($activity['tanggal_kegiatan']))) ?></td>
                                <td><strong><?= h($activity['judul_kegiatan']) ?></strong><small class="d-block text-secondary"><?= h($activity['kategori']) ?></small></td>
                                <td><?= h($activity['peserta'] ?: 'Belum dipilih') ?></td>
                                <td><span class="category-pill"><?= h($activity['status_kegiatan']) ?></span></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="admin.php?edit=<?= (int) $activity['id_kegiatan'] ?>" aria-label="Edit kegiatan"><i class="bi bi-pencil"></i></a>
                                    <a class="btn btn-sm btn-outline-primary" href="admin.php?duplicate=<?= (int) $activity['id_kegiatan'] ?>" aria-label="Duplikat kegiatan" title="Duplikat">
                                        <i class="bi bi-copy"></i><span class="ms-1">Duplikat</span>
                                    </a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus kegiatan beserta seluruh foto dokumentasinya?')">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_kegiatan" value="<?= (int) $activity['id_kegiatan'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Hapus kegiatan"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php $portfolioHasImage = !empty($editPortfolio['gambar']); ?>
        <section class="filter-panel admin-form-panel" id="portofolio">
            <div class="section-heading">
                <div>
                    <span class="section-kicker"><?= $editPortfolio ? 'UBAH PORTOFOLIO' : 'PORTOFOLIO BARU' ?></span>
                    <h3><?= $editPortfolio ? 'Edit hasil kerja' : 'Tambah hasil kerja / portofolio' ?></h3>
                    <p>Kelola kartu portofolio publik, screenshot, dan tautan terkait.</p>
                </div>
                <?php if ($editPortfolio): ?>
                    <a class="reset-link" href="admin.php#portofolio"><i class="bi bi-x-circle me-1"></i>Batal mengedit</a>
                <?php endif; ?>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="action" value="save_portfolio">
                <input type="hidden" name="portfolio_id" value="<?= h($portfolioFormValue('id', '')) ?>">
                <div class="admin-form-grid">
                    <div class="field-group">
                        <label for="portfolio_kategori">Kategori *</label>
                        <select class="form-select" id="portfolio_kategori" name="portfolio_kategori" required>
                            <option value="">Pilih kategori</option>
                            <?php foreach ($portfolioCategories as $option): ?>
                                <option value="<?= h($option) ?>" <?= $portfolioFormValue('kategori') === $option ? 'selected' : '' ?>><?= h($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="portfolio_urutan">Urutan tampil *</label>
                        <input class="form-control" id="portfolio_urutan" name="portfolio_urutan" type="number" min="0" step="1" value="<?= h($portfolioFormValue('urutan', '0')) ?>" required>
                    </div>
                    <div class="field-group admin-field-wide">
                        <label for="portfolio_judul">Judul *</label>
                        <input class="form-control" id="portfolio_judul" name="portfolio_judul" maxlength="200" value="<?= h($portfolioFormValue('judul')) ?>" required>
                    </div>
                    <div class="field-group admin-field-wide">
                        <label for="portfolio_deskripsi">Deskripsi *</label>
                        <textarea class="form-control" id="portfolio_deskripsi" name="portfolio_deskripsi" rows="4" required><?= h($portfolioFormValue('deskripsi')) ?></textarea>
                    </div>
                    <div class="field-group admin-field-wide">
                        <label for="portfolio_gambar">Screenshot (JPG/PNG) <?= $portfolioHasImage ? '' : '*' ?></label>
                        <input class="form-control" id="portfolio_gambar" name="portfolio_gambar" type="file" accept="image/jpeg,image/png" <?= $portfolioHasImage ? '' : 'required' ?>>
                        <small class="form-hint">Maksimal 8 MB. Saat mengedit, kosongkan jika ingin tetap menggunakan gambar yang tersimpan.</small>
                        <?php if ($portfolioHasImage): ?>
                            <div class="portfolio-current-image">
                                <span class="form-hint">Screenshot saat ini:</span>
                                <a href="assets/img/portofolio/<?= rawurlencode(basename($editPortfolio['gambar'])) ?>" target="_blank" rel="noopener">
                                    <img src="assets/img/portofolio/<?= rawurlencode(basename($editPortfolio['gambar'])) ?>" alt="Preview <?= h($editPortfolio['judul']) ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="field-group admin-field-wide">
                        <label for="portfolio_url_external">URL eksternal</label>
                        <input class="form-control" id="portfolio_url_external" name="portfolio_url_external" type="url" maxlength="2048" placeholder="https://..." value="<?= h($portfolioFormValue('url_external')) ?>">
                        <small class="form-hint">Tautan publik Google Form atau Looker Studio. Kosongkan jika belum tersedia.</small>
                    </div>
                </div>
                <div class="admin-form-actions">
                    <button class="btn btn-brand" type="submit"><i class="bi bi-save me-2"></i><?= $editPortfolio ? 'Update portofolio' : 'Simpan portofolio' ?></button>
                </div>
            </form>
        </section>

        <section class="filter-panel">
            <div class="section-heading">
                <div><span class="section-kicker">REKAP</span><h3>Data portofolio</h3></div>
                <span class="record-count"><?= count($portfolios) ?> item</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle portfolio-admin-table">
                    <thead><tr><th>Urutan</th><th>Preview</th><th>Kategori &amp; Judul</th><th>URL</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php if (!$portfolios): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada data portofolio.</td></tr>
                    <?php else: ?>
                        <?php foreach ($portfolios as $portfolio): ?>
                            <?php
                            $externalUrl = filter_var($portfolio['url_external'], FILTER_VALIDATE_URL);
                            $urlScheme = $externalUrl ? strtolower((string) parse_url($portfolio['url_external'], PHP_URL_SCHEME)) : '';
                            $externalUrl = $externalUrl && in_array($urlScheme, ['http', 'https'], true) ? $portfolio['url_external'] : '';
                            $portfolioImageName = basename($portfolio['gambar']);
                            ?>
                            <tr>
                                <td><?= (int) $portfolio['urutan'] ?></td>
                                <td>
                                    <?php if ($portfolioImageName !== ''): ?>
                                        <a href="assets/img/portofolio/<?= rawurlencode($portfolioImageName) ?>" target="_blank" rel="noopener" aria-label="Preview screenshot <?= h($portfolio['judul']) ?>">
                                            <img class="portfolio-admin-thumbnail" src="assets/img/portofolio/<?= rawurlencode($portfolioImageName) ?>" alt="Screenshot <?= h($portfolio['judul']) ?>" loading="lazy">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-secondary">Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="category-pill"><?= h($portfolio['kategori']) ?></span><strong class="d-block mt-1"><?= h($portfolio['judul']) ?></strong></td>
                                <td>
                                    <?php if ($externalUrl !== ''): ?>
                                        <a href="<?= h($externalUrl) ?>" target="_blank" rel="noopener noreferrer">Buka link<i class="bi bi-box-arrow-up-right ms-1"></i></a>
                                    <?php else: ?>
                                        <span class="text-secondary">Belum tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="admin.php?edit_portfolio=<?= (int) $portfolio['id'] ?>#portofolio" aria-label="Edit portofolio"><i class="bi bi-pencil"></i></a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus portofolio beserta screenshot-nya?')">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete_portfolio">
                                        <input type="hidden" name="portfolio_id" value="<?= (int) $portfolio['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Hapus portofolio"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const status = document.getElementById('status_kegiatan');
    const learning = document.getElementById('hasil_pembelajaran');
    const requiredMark = document.getElementById('learningRequiredMark');
    const hint = document.getElementById('sickStatusHint');
    const categoryOptions = document.querySelectorAll('input[name="kategori[]"]');

    function updateSickRequirements() {
        if (!status || !learning) return;
        const sick = status.value === 'Izin Sakit';
        learning.required = !sick;
        requiredMark.hidden = sick;
        hint.hidden = !sick;
    }

    if (status && learning) {
        status.addEventListener('change', updateSickRequirements);
        updateSickRequirements();
    }

    if (categoryOptions.length > 0) {
        const categoryForm = categoryOptions[0].form;
        categoryForm.addEventListener('submit', function (event) {
            if (!Array.from(categoryOptions).some(function (option) { return option.checked; })) {
                event.preventDefault();
                categoryOptions[0].setCustomValidity('Pilih minimal satu kategori.');
                categoryOptions[0].reportValidity();
            }
        });
        categoryOptions.forEach(function (option) {
            option.addEventListener('change', function () {
                categoryOptions[0].setCustomValidity('');
            });
        });
    }
});
</script>
</body>
</html>
