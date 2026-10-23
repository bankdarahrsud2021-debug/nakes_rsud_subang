<?php
include "../config/auth.php";
include "../config/database.php";

$id_sip = (int)($_GET['id'] ?? 0);

if ($id_sip <= 0) {
    die("ID SIP tidak valid.");
}

$query = "
    SELECT
        s.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang
    FROM sip_nakes s
    INNER JOIN tenaga_kesehatan t
        ON s.id_nakes = t.id_nakes
    WHERE s.id_sip = ?
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_sip
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data SIP tidak ditemukan.");
}

$query_nakes = "
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

$result_nakes = mysqli_query($conn, $query_nakes);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit SIP</title>

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
            background: #0d6efd;
            color: white;
        }

        .back {
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="container">

    <a href="index.php" class="back">← Kembali</a>

    <h2>Edit Data SIP</h2>

    <form
        action="update.php"
        method="POST"
        enctype="multipart/form-data">

        <input
            type="hidden"
            name="id_sip"
            value="<?= $data['id_sip']; ?>">

        <label>Tenaga Kesehatan</label>

        <select name="id_nakes" required>

            <?php while ($row = mysqli_fetch_assoc($result_nakes)) { ?>

                <option
                    value="<?= $row['id_nakes']; ?>"
                    <?= $row['id_nakes'] == $data['id_nakes'] ? 'selected' : ''; ?>>

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
            value="<?= htmlspecialchars($data['nomor_sip']); ?>"
            required>

        <label>Tempat Praktik</label>

        <input
            type="text"
            name="tempat_praktik"
            value="<?= htmlspecialchars($data['tempat_praktik']); ?>"
            required>

        <label>Tanggal Terbit</label>

        <input
            type="date"
            name="tanggal_terbit"
            value="<?= htmlspecialchars($data['tanggal_terbit']); ?>"
            required>

        <label>Tanggal Berakhir</label>

        <input
            type="date"
            name="tanggal_berakhir"
            value="<?= htmlspecialchars($data['tanggal_berakhir']); ?>"
            required>

        <label>Status Masa Berlaku</label>

        <select name="status" required>

            <option value="AKTIF"
                <?= $data['status'] == 'AKTIF' ? 'selected' : ''; ?>>
                AKTIF
            </option>

            <option value="AKAN BERAKHIR"
                <?= $data['status'] == 'AKAN BERAKHIR' ? 'selected' : ''; ?>>
                AKAN BERAKHIR
            </option>

            <option value="KADALUARSA"
                <?= $data['status'] == 'KADALUARSA' ? 'selected' : ''; ?>>
                KADALUARSA
            </option>

        </select>

        <label>File SIP Baru</label>

        <input
            type="file"
            name="file_sip"
            accept=".pdf,.jpg,.jpeg,.png">

        <small>
            Kosongkan jika tidak ingin mengganti file.
        </small>

        <br>

        <?php if (!empty($data['file_sip'])) { ?>

            <p>
                File saat ini:
                <a
                    href="../uploads/sip_nakes/<?= htmlspecialchars($data['file_sip']); ?>"
                    target="_blank">
                    Lihat File SIP
                </a>
            </p>

        <?php } ?>

        <button type="submit">
            💾 Update
        </button>

    </form>

</div>

</body>
</html>