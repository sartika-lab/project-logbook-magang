CREATE DATABASE IF NOT EXISTS db_logbook_magang
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE db_logbook_magang;

CREATE TABLE IF NOT EXISTS mahasiswa (
    id_mahasiswa INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_mahasiswa VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_mahasiswa),
    UNIQUE KEY uq_mahasiswa_nama (nama_mahasiswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kegiatan (
    id_kegiatan INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tanggal_kegiatan DATE NOT NULL,
    judul_kegiatan VARCHAR(200) NOT NULL,
    kategori VARCHAR(80) NOT NULL,
    waktu_kegiatan VARCHAR(50) NOT NULL DEFAULT '',
    deskripsi TEXT NOT NULL,
    hasil_pembelajaran TEXT NOT NULL,
    status_kegiatan VARCHAR(50) NOT NULL DEFAULT 'Selesai',
    PRIMARY KEY (id_kegiatan),
    KEY idx_kegiatan_tanggal (tanggal_kegiatan),
    KEY idx_kegiatan_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS peserta_kegiatan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_kegiatan INT UNSIGNED NOT NULL,
    id_mahasiswa INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_peserta_kegiatan (id_kegiatan, id_mahasiswa),
    CONSTRAINT fk_peserta_kegiatan
        FOREIGN KEY (id_kegiatan) REFERENCES kegiatan (id_kegiatan) ON DELETE CASCADE,
    CONSTRAINT fk_peserta_mahasiswa
        FOREIGN KEY (id_mahasiswa) REFERENCES mahasiswa (id_mahasiswa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dokumentasi (
    id_dokumentasi INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_kegiatan INT UNSIGNED NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    jenis_foto ENUM('kegiatan', 'absen_masuk', 'absen_pulang') NOT NULL DEFAULT 'kegiatan',
    PRIMARY KEY (id_dokumentasi),
    KEY idx_dokumentasi_kegiatan (id_kegiatan),
    CONSTRAINT fk_dokumentasi_kegiatan
        FOREIGN KEY (id_kegiatan) REFERENCES kegiatan (id_kegiatan) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portofolio (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kategori VARCHAR(80) NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT NOT NULL,
    gambar VARCHAR(255) NOT NULL DEFAULT '',
    url_external VARCHAR(2048) NOT NULL DEFAULT '',
    urutan INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_portofolio_urutan (urutan, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER //
CREATE PROCEDURE ensure_logbook_column(
    IN table_name_value VARCHAR(64),
    IN column_name_value VARCHAR(64),
    IN column_definition_value VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = table_name_value
          AND COLUMN_NAME = column_name_value
    ) THEN
        SET @logbook_ddl = CONCAT(
            'ALTER TABLE `', REPLACE(table_name_value, '`', '``'),
            '` ADD COLUMN `', REPLACE(column_name_value, '`', '``'),
            '` ', column_definition_value
        );
        PREPARE logbook_statement FROM @logbook_ddl;
        EXECUTE logbook_statement;
        DEALLOCATE PREPARE logbook_statement;
    END IF;
END//

CALL ensure_logbook_column('kegiatan', 'waktu_kegiatan', "VARCHAR(50) NOT NULL DEFAULT ''")//
CALL ensure_logbook_column('kegiatan', 'hasil_pembelajaran', 'TEXT NOT NULL')//
CALL ensure_logbook_column('kegiatan', 'status_kegiatan', "VARCHAR(50) NOT NULL DEFAULT 'Selesai'")//
CALL ensure_logbook_column('dokumentasi', 'jenis_foto', "ENUM('kegiatan','absen_masuk','absen_pulang') NOT NULL DEFAULT 'kegiatan'")//
DROP PROCEDURE ensure_logbook_column//
DELIMITER ;

INSERT IGNORE INTO mahasiswa (id_mahasiswa, nama_mahasiswa)
VALUES (1, 'Sartika'), (2, 'Nadirah');
