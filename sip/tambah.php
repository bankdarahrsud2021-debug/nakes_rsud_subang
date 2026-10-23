<?php
include "../config/auth.php";
include "../config/database.php";

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

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah SIP</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .container {
            background: white;
            max-width: 700px;
            margin: auto;
            padding: 25px;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 9px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        button {
            margin-top: 20px;
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            background: #198754;
            color: white;
            cursor: pointer;
        }

        .back {
            display: inline-block;
            margin-bottom: 15px;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="container">

    <a href="index.php" class="back">← Kembali</a>

    <h2>Tambah Data SIP</h2>

    <form
        action="simpan.php"
        method="POST"
        enctype="multipart/form-data">

        <label>Tenaga Kesehatan</label>

        <select name="id_nakes" required>

            <option value="">-- Pilih Tenaga Kesehatan --</option>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                <option value="<?= $row['id_nakes']; ?>">

                    <?= htmlspecialchars(
                        trim(
                            ($row['gelar_depan'] ?? '') . ' ' .
                            $row['nama_lengkap'] . ' ' .
                            ($row['gelar_belakang'] ?? '')
                        )
                    ); ?>

                    -
                    <?= htmlspecialchars($row['nama_profesi'] ?? ''); ?>

                </option>

            <?php } ?>

        </select>

        <label>Nomor SIP</label>

        <input
            type="text"
            name="nomor_sip"
            required>

        <label>Tempat Praktik</label>

        <input
            type="text"
            name="tempat_praktik"
            required>

        <label>Tanggal Terbit</label>

        <input
            type="date"
            name="tanggal_terbit"
            required>

        <label>Tanggal Berakhir</label>

        <input
            type="date"
            name="tanggal_berakhir"
            required>

        <label>Status Masa Berlaku</label>

        <select name="status" required>

            <option value="">-- Pilih Status --</option>
            <option value="AKTIF">AKTIF</option>
            <option value="AKAN BERAKHIR">AKAN BERAKHIR</option>
            <option value="KADALUARSA">KADALUARSA</option>

        </select>

        <label>File SIP</label>

        <input
            type="file"
            name="file_sip"
            accept=".pdf,.jpg,.jpeg,.png"
            required>

        <small>
            Format PDF/JPG/JPEG/PNG, maksimal 5 MB.
        </small>

        <br>

        <button type="submit">
            💾 Simpan
        </button>

    </form>

</div>

</body>
</html>