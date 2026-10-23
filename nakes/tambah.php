<?php

require_once "../config/auth.php";
require_once "../config/database.php";

/* =========================
   AMBIL MASTER DATA
========================= */

$profesi = mysqli_query(
    $conn,
    "SELECT id_profesi, nama_profesi
     FROM profesi
     ORDER BY nama_profesi ASC"
);

if (!$profesi) {
    die("Gagal mengambil data profesi: " . mysqli_error($conn));
}


$unit_kerja = mysqli_query(
    $conn,
    "SELECT id_unit, nama_unit
     FROM unit_kerja
     ORDER BY nama_unit ASC"
);

if (!$unit_kerja) {
    die("Gagal mengambil data unit kerja: " . mysqli_error($conn));
}


$jabatan = mysqli_query(
    $conn,
    "SELECT id_jabatan, nama_jabatan
     FROM jabatan
     ORDER BY nama_jabatan ASC"
);

if (!$jabatan) {
    die("Gagal mengambil data jabatan: " . mysqli_error($conn));
}


$status_kepegawaian = mysqli_query(
    $conn,
    "SELECT id_status, nama_status
     FROM status_kepegawaian
     ORDER BY id_status ASC"
);

if (!$status_kepegawaian) {
    die(
        "Gagal mengambil data status kepegawaian: " .
        mysqli_error($conn)
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Tambah Tenaga Kesehatan</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
        }

        h2 {
            margin-top: 0;
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .full {
            grid-column: 1 / 3;
        }

        label {
            font-weight: bold;
            margin-bottom: 6px;
        }

        input,
        select,
        textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
        }

        .required {
            color: red;
        }

        .button-area {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }

        button,
        .btn-kembali {
            padding: 11px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        button {
            background: #198754;
            color: white;
        }

        .btn-kembali {
            background: #6c757d;
            color: white;
        }

        button:hover {
            background: #157347;
        }

        .btn-kembali:hover {
            background: #5c636a;
        }

        @media (max-width: 700px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: 1;
            }

            body {
                padding: 10px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <h2>Tambah Tenaga Kesehatan</h2>

    <form action="simpan.php"
          method="POST"
          enctype="multipart/form-data">

        <div class="form-grid">

            <!-- NIK -->

            <div class="form-group">

                <label>
                    NIK <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="nik"
                    maxlength="20"
                >

            </div>


            <!-- NIP -->

            <div class="form-group">

                <label>NIP</label>

                <input
                    type="text"
                    name="nip"
                    maxlength="30"
                >

            </div>


            <!-- Nama -->

            <div class="form-group full">

                <label>
                    Nama Lengkap <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="nama_lengkap"
                    maxlength="150"
                    required
                >

            </div>


            <!-- Gelar Depan -->

            <div class="form-group">

                <label>Gelar Depan</label>

                <input
                    type="text"
                    name="gelar_depan"
                    maxlength="50"
                >

            </div>


            <!-- Gelar Belakang -->

            <div class="form-group">

                <label>Gelar Belakang</label>

                <input
                    type="text"
                    name="gelar_belakang"
                    maxlength="100"
                >

            </div>


            <!-- Tempat Lahir -->

            <div class="form-group">

                <label>Tempat Lahir</label>

                <input
                    type="text"
                    name="tempat_lahir"
                    maxlength="100"
                >

            </div>


            <!-- Tanggal Lahir -->

            <div class="form-group">

                <label>Tanggal Lahir</label>

                <input
                    type="date"
                    name="tanggal_lahir"
                >

            </div>


            <!-- Jenis Kelamin -->

            <div class="form-group">

                <label>
                    Jenis Kelamin <span class="required">*</span>
                </label>

                <select name="jenis_kelamin" required>

                    <option value="">
                        -- Pilih Jenis Kelamin --
                    </option>

                    <option value="L">
                        Laki-laki
                    </option>

                    <option value="P">
                        Perempuan
                    </option>

                </select>

            </div>


            <!-- No HP -->

            <div class="form-group">

                <label>No. HP</label>

                <input
                    type="text"
                    name="no_hp"
                    maxlength="20"
                >

            </div>


            <!-- Email -->

            <div class="form-group">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    maxlength="150"
                >

            </div>


            <!-- Profesi -->

            <div class="form-group">

                <label>
                    Profesi <span class="required">*</span>
                </label>

                <select name="id_profesi" required>

                    <option value="">
                        -- Pilih Profesi --
                    </option>

                    <?php while ($row = mysqli_fetch_assoc($profesi)) { ?>

                        <option value="<?= $row['id_profesi']; ?>">
                            <?= htmlspecialchars($row['nama_profesi']); ?>
                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- Unit Kerja -->

            <div class="form-group">

                <label>
                    Unit Kerja <span class="required">*</span>
                </label>

                <select name="id_unit" required>

                    <option value="">
                        -- Pilih Unit Kerja --
                    </option>

                    <?php while ($row = mysqli_fetch_assoc($unit_kerja)) { ?>

                        <option value="<?= $row['id_unit']; ?>">
                            <?= htmlspecialchars($row['nama_unit']); ?>
                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- Jabatan -->

            <div class="form-group">

                <label>Jabatan</label>

                <select name="id_jabatan">

                    <option value="">
                        -- Pilih Jabatan --
                    </option>

                    <?php while ($row = mysqli_fetch_assoc($jabatan)) { ?>

                        <option value="<?= $row['id_jabatan']; ?>">
                            <?= htmlspecialchars($row['nama_jabatan']); ?>
                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- STATUS KEPEGAWAIAN -->

            <div class="form-group">

                <label>
                    Status Kepegawaian
                    <span class="required">*</span>
                </label>

                <select
                    name="id_status_kepegawaian"
                    required
                >

                    <option value="">
                        -- Pilih Status Kepegawaian --
                    </option>

                    <?php while ($row = mysqli_fetch_assoc($status_kepegawaian)) { ?>

                        <option value="<?= $row['id_status']; ?>">

                            <?= htmlspecialchars($row['nama_status']); ?>

                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- Tanggal Mulai Kerja -->

            <div class="form-group">

                <label>Tanggal Mulai Kerja</label>

                <input
                    type="date"
                    name="tanggal_mulai_kerja"
                >

            </div>


            <!-- Alamat -->

            <div class="form-group full">

                <label>Alamat</label>

                <textarea
                    name="alamat"
                    maxlength="500"
                ></textarea>

            </div>


            <!-- Foto -->

            <div class="form-group full">

                <label>Foto</label>

                <input
                    type="file"
                    name="foto"
                    accept=".jpg,.jpeg,.png"
                >

                <small>
                    Format JPG/JPEG/PNG, maksimal 2 MB.
                </small>

            </div>

        </div>


        <div class="button-area">

            <button type="submit">
                💾 Simpan
            </button>

            <a
                href="index.php"
                class="btn-kembali"
            >
                Kembali
            </a>

        </div>

    </form>

</div>

</body>

</html>