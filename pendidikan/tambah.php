<?php
require_once "../config/auth.php";
require_once "../config/database.php";

$query = "
    SELECT
        t.id_nakes,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi
    FROM tenaga_kesehatan t
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    WHERE t.status_aktif = 'AKTIF'
    ORDER BY t.nama_lengkap ASC
";

$result_nakes = mysqli_query($conn, $query);

if (!$result_nakes) {
    die("Query gagal: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Pendidikan</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        h2 {
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            margin-top: 20px;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>➕ Tambah Data Pendidikan</h2>

    <form action="simpan.php" method="POST" enctype="multipart/form-data">

        <label>Tenaga Kesehatan *</label>

        <select name="id_nakes" required>

            <option value="">-- Pilih Tenaga Kesehatan --</option>

            <?php while ($nakes = mysqli_fetch_assoc($result_nakes)): ?>

                <option value="<?= (int)$nakes['id_nakes']; ?>">

                    <?= htmlspecialchars(
                        trim(
                            ($nakes['gelar_depan'] ? $nakes['gelar_depan'] . ' ' : '') .
                            $nakes['nama_lengkap'] .
                            ($nakes['gelar_belakang'] ? ', ' . $nakes['gelar_belakang'] : '')
                        )
                    ); ?>

                    <?php if (!empty($nakes['nama_profesi'])): ?>
                        - <?= htmlspecialchars($nakes['nama_profesi']); ?>
                    <?php endif; ?>

                </option>

            <?php endwhile; ?>

        </select>


        <label>Jenjang Pendidikan *</label>

        <select name="jenjang" required>

            <option value="">-- Pilih Jenjang --</option>
            <option value="SMA/SMK">SMA/SMK</option>
            <option value="D1">D1</option>
            <option value="D2">D2</option>
            <option value="D3">D3</option>
            <option value="D4">D4</option>
            <option value="S1">S1</option>
            <option value="Profesi">Profesi</option>
            <option value="S2">S2</option>
            <option value="S3">S3</option>

        </select>


        <label>Institusi Pendidikan *</label>

        <input
            type="text"
            name="institusi"
            maxlength="200"
            required>


        <label>Program Studi *</label>

        <input
            type="text"
            name="program_studi"
            maxlength="150"
            required>


        <label>Nomor Ijazah</label>

        <input
            type="text"
            name="nomor_ijazah"
            maxlength="100">


        <label>Tanggal Ijazah</label>

        <input
            type="date"
            name="tanggal_ijazah">


        <label>Tahun Lulus *</label>

        <input
            type="number"
            name="tahun_lulus"
            min="1950"
            max="<?= date('Y'); ?>"
            required>


        <label>File Ijazah</label>

        <input
            type="file"
            name="file_ijazah"
            accept=".pdf,.jpg,.jpeg,.png">

        <div class="info">
            Format: PDF, JPG, JPEG, PNG. Maksimal 5 MB.
        </div>


        <label>File Transkrip</label>

        <input
            type="file"
            name="file_transkrip"
            accept=".pdf,.jpg,.jpeg,.png">

        <div class="info">
            Format: PDF, JPG, JPEG, PNG. Maksimal 5 MB.
        </div>


        <button
            type="submit"
            class="btn btn-success">
            💾 Simpan
        </button>

        <a
            href="index.php"
            class="btn btn-secondary">
            ↩ Kembali
        </a>

    </form>

</div>

</body>
</html>