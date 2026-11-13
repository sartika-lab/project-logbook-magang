<?php
$conn->query(
    "CREATE TABLE IF NOT EXISTS portofolio (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        kategori VARCHAR(80) NOT NULL,
        judul VARCHAR(200) NOT NULL,
        deskripsi TEXT NOT NULL,
        gambar VARCHAR(255) NOT NULL DEFAULT '',
        url_external VARCHAR(2048) NOT NULL DEFAULT '',
        urutan INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_portofolio_urutan (urutan, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
