<?php
require_once "../config/auth.php";
require_once "../config/database.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID pendidikan tidak valid.");
}

$sql = "
    SELECT
        p.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        pr.nama_profesi
    FROM pendidikan_nakes p
    INNER JOIN tenaga_kesehatan t
        ON p.id_nakes = t.id_nakes
    LEFT JOIN profesi pr
        ON t.id_profesi = pr.id_profesi
    WHERE p.id_pendidikan = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    die("Data pendidikan tidak ditemukan.");
}


/* Daftar nakes */
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
       OR t.id_nakes = ?
    ORDER BY t.nama_lengkap ASC
";

$stmt = mysqli_prepare($conn, $query_nakes);
mysqli_stmt_bind_param(
    $stmt,
    "i",
    $data['id_nakes']
);
mysqli_stmt_execute($stmt);

$result_nakes = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pendidikan</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            margin-top: 20px;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            color: white;
        }

        .btn-primary {
            background: #007bff;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .file-info {
            margin-top: 5px;
            font-size: 13px;
        }

        .file-info a {
            color: #007bff;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>✏️ Edit Data Pendidikan</h2>

    <form action="update.php" method="POST" enctype="multipart/form-data">

        <input
            type="hidden"
            name="id_pendidikan"
            value="<?= (int)$data['id_pendidikan']; ?>">


        <label>Tenaga Kesehatan *</label>

        <select name="id_nakes" required>

            <?php while ($nakes = mysqli_fetch_assoc($result_nakes)): ?>

                <option
                    value="<?= (int)$nakes['id_nakes']; ?>"
                    <?= ($nakes['id_nakes'] == $data['id_nakes']) ? 'selected' : ''; ?>>

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


        <label>Jenjang *</label>

        <select name="jenjang" required>

            <?php
            $jenjang_list = [
                'SMA/SMK',
                'D1',
                'D2',
                'D3',
                'D4',
                'S1',
                'Profesi',
                'S2',
                'S3'
            ];

            foreach ($jenjang_list as $jenjang):
            ?>

                <option
                    value="<?= htmlspecialchars($jenjang); ?>"
                    <?= ($data['jenjang'] == $jenjang) ? 'selected' : ''; ?>>

                    <?= htmlspecialchars($jenjang); ?>

                </option>

            <?php endforeach; ?>

        </select>


        <label>Institusi Pendidikan *</label>

        <input
            type="text"
            name="institusi"
            maxlength="200"
            value="<?= htmlspecialchars($data['institusi']); ?>"
            required>


        <label>Program Studi *</label>

        <input
            type="text"
            name="program_studi"
            maxlength="150"
            value="<?= htmlspecialchars($data['program_studi']); ?>"
            required>


        <label>Nomor Ijazah</label>

        <input
            type="text"
            name="nomor_ijazah"
            maxlength="100"
            value="<?= htmlspecialchars($data['nomor_ijazah'] ?? ''); ?>">


        <label>Tanggal Ijazah</label>

        <input
            type="date"
            name="tanggal_ijazah"
            value="<?= htmlspecialchars($data['tanggal_ijazah'] ?? ''); ?>">


        <label>Tahun Lulus *</label>

        <input
            type="number"
            name="tahun_lulus"
            min="1950"
            max="<?= date('Y'); ?>"
            value="<?= htmlspecialchars($data['tahun_lulus']); ?>"
            required>


        <label>File Ijazah</label>

        <input
            type="file"
            name="file_ijazah"
            accept=".pdf,.jpg,.jpeg,.png">

        <?php if (!empty($data['file_ijazah'])): ?>

            <div class="file-info">
                File saat ini:
                <a
                    href="../uploads/ijazah_nakes/<?= rawurlencode($data['file_ijazah']); ?>"
                    target="_blank">
                    📄 Lihat Ijazah
                </a>
            </div>

        <?php endif; ?>


        <label>File Transkrip</label>

        <input
            type="file"
            name="file_transkrip"
            accept=".pdf,.jpg,.jpeg,.png">

        <?php if (!empty($data['file_transkrip'])): ?>

            <div class="file-info">
                File saat ini:
                <a
                    href="../uploads/ijazah_nakes/<?= rawurlencode($data['file_transkrip']); ?>"
                    target="_blank">
                    📄 Lihat Transkrip
                </a>
            </div>

        <?php endif; ?>


        <button
            type="submit"
            class="btn btn-primary">
            💾 Simpan Perubahan
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