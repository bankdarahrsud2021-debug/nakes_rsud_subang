<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID kredensial tidak valid.");
}

$id_kredensial = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| Ambil data kredensial dan identitas nakes
|--------------------------------------------------------------------------
*/
$query = "
    SELECT
        k.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        t.nik,
        t.nip,
        p.nama_profesi,
        u.nama_unit
    FROM kredensial k
    INNER JOIN tenaga_kesehatan t
        ON k.id_nakes = t.id_nakes
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
    WHERE k.id_kredensial = ?
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_kredensial);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data kredensial tidak ditemukan.");
}

/*
|--------------------------------------------------------------------------
| Daftar dokumen wajib
|--------------------------------------------------------------------------
*/
$daftar_dokumen = [
    "KTP",
    "IJAZAH",
    "STR",
    "SIP",
    "SERTIFIKAT KOMPETENSI",
    "SERTIFIKAT PELATIHAN",
    "SK PENGANGKATAN/KEPEGAWAIAN",
    "DOKUMEN PENDUKUNG LAINNYA"
];

/*
|--------------------------------------------------------------------------
| Pastikan checklist dokumen tersedia
|--------------------------------------------------------------------------
*/
foreach ($daftar_dokumen as $jenis) {

    $cek = mysqli_prepare(
        $conn,
        "SELECT id_dokumen
         FROM proses_kredensial
         WHERE id_kredensial = ?
         AND jenis_dokumen = ?"
    );

    mysqli_stmt_bind_param(
        $cek,
        "is",
        $id_kredensial,
        $jenis
    );

    mysqli_stmt_execute($cek);

    $hasil = mysqli_stmt_get_result($cek);

    if (mysqli_num_rows($hasil) == 0) {

        $insert = mysqli_prepare(
            $conn,
            "INSERT INTO proses_kredensial
            (
                id_kredensial,
                jenis_dokumen,
                status_dokumen
            )
            VALUES (?, ?, 'TIDAK ADA')"
        );

        mysqli_stmt_bind_param(
            $insert,
            "is",
            $id_kredensial,
            $jenis
        );

        mysqli_stmt_execute($insert);
    }
}

/*
|--------------------------------------------------------------------------
| Ambil seluruh checklist dokumen
|--------------------------------------------------------------------------
*/
$query_dokumen = "
    SELECT *
    FROM proses_kredensial
    WHERE id_kredensial = ?
    ORDER BY id_dokumen ASC
";

$stmt_dokumen = mysqli_prepare($conn, $query_dokumen);

mysqli_stmt_bind_param(
    $stmt_dokumen,
    "i",
    $id_kredensial
);

mysqli_stmt_execute($stmt_dokumen);

$result_dokumen = mysqli_stmt_get_result($stmt_dokumen);

/*
|--------------------------------------------------------------------------
| Hitung kelengkapan
|--------------------------------------------------------------------------
*/
$total_dokumen = 0;
$total_lengkap = 0;

$dokumen_data = [];

while ($dok = mysqli_fetch_assoc($result_dokumen)) {

    $dokumen_data[] = $dok;

    $total_dokumen++;

    /*
    | Dokumen dianggap lengkap jika:
    | 1. Status ADA
    | 2. Ada file
    */
    if (
        strtoupper($dok['status_dokumen']) === 'ADA'
        && !empty($dok['nama_file'])
    ) {
        $total_lengkap++;
    }
}

if ($total_dokumen > 0 && $total_lengkap == $total_dokumen) {
    $status_kelangkapan = "LENGKAP";
} else {
    $status_kelangkapan = "BELUM LENGKAP";
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>
        Checklist Dokumen Kredensial
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .toolbar {
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-dashboard {
            background: #6c757d;
            color: white;
        }

        .btn-save {
            background: #007bff;
            color: white;
        }

        .btn-view {
            background: #198754;
            color: white;
        }

        .btn-print {
            background: #343a40;
            color: white;
        }

        .identitas {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .identitas table {
            width: 100%;
            border-collapse: collapse;
        }

        .identitas td {
            padding: 6px;
        }

        .status-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .status-lengkap {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .status-belum {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .progress-info {
            margin-top: 5px;
            font-size: 14px;
            font-weight: normal;
        }

        .dokumen-table {
            width: 100%;
            border-collapse: collapse;
        }

        .dokumen-table th,
        .dokumen-table td {
            border: 1px solid #ddd;
            padding: 10px;
            vertical-align: middle;
        }

        .dokumen-table th {
            background: #343a40;
            color: white;
            text-align: center;
        }

        .dokumen-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .status-ada {
            color: #198754;
            font-weight: bold;
        }

        .status-tidak {
            color: #dc3545;
            font-weight: bold;
        }

        input[type="file"] {
            width: 100%;
            max-width: 260px;
        }

        textarea {
            width: 100%;
            padding: 7px;
            resize: vertical;
        }

        .file-info {
            font-size: 12px;
            margin-top: 5px;
            color: #666;
        }

        .bottom-area {
            margin-top: 20px;
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .alert-danger {
            background: #f8d7da;
            color: #842029;
        }

        @media (max-width: 900px) {

            .container {
                padding: 15px;
                overflow-x: auto;
            }

            .dokumen-table {
                min-width: 1000px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <!-- =========================================================
         TOOLBAR
    ========================================================== -->

    <div class="toolbar">

        <a href="index.php" class="btn btn-dashboard">
            ← Kembali ke Kredensial
        </a>

        <a href="../dashboard/index.php" class="btn btn-dashboard">
            ← Dashboard
        </a>

        <?php if ($status_kelangkapan === "LENGKAP"): ?>

            <a
                href="cetak.php?id=<?= $id_kredensial; ?>"
                target="_blank"
                class="btn btn-print">
                🖨 Cetak Lembar Kredensial
            </a>

        <?php endif; ?>

    </div>


    <h2>
        Checklist Kelengkapan Dokumen Kredensial
    </h2>


    <!-- =========================================================
         PESAN
    ========================================================== -->

    <?php if (isset($_GET['success'])): ?>

        <div class="alert alert-success">
            Checklist dan dokumen berhasil disimpan.
        </div>

    <?php endif; ?>


    <?php if (isset($_GET['error'])): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($_GET['error']); ?>
        </div>

    <?php endif; ?>


    <!-- =========================================================
         IDENTITAS
    ========================================================== -->

    <div class="identitas">

        <table>

            <tr>
                <td width="200">
                    <strong>Nama Lengkap</strong>
                </td>

                <td>
                    :
                    <?= htmlspecialchars(
                        trim(
                            ($data['gelar_depan'] ?? '') . ' ' .
                            ($data['nama_lengkap'] ?? '') . ' ' .
                            ($data['gelar_belakang'] ?? '')
                        )
                    ); ?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>NIK</strong>
                </td>

                <td>
                    :
                    <?= htmlspecialchars($data['nik'] ?? '-'); ?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>NIP</strong>
                </td>

                <td>
                    :
                    <?= htmlspecialchars($data['nip'] ?? '-'); ?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Profesi</strong>
                </td>

                <td>
                    :
                    <?= htmlspecialchars($data['nama_profesi'] ?? '-'); ?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Unit Kerja</strong>
                </td>

                <td>
                    :
                    <?= htmlspecialchars($data['nama_unit'] ?? '-'); ?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Jenis Kredensial</strong>
                </td>

                <td>
                    :
                    <?= htmlspecialchars($data['jenis_kredensial'] ?? '-'); ?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Tanggal Pengajuan</strong>
                </td>

                <td>
                    :
                    <?= !empty($data['tanggal_pengajuan'])
                        ? date('d-m-Y', strtotime($data['tanggal_pengajuan']))
                        : '-';
                    ?>
                </td>
            </tr>

            <tr>
                <td>
                    <strong>Status Kredensial</strong>
                </td>

                <td>
                    :
                    <strong>
                        <?= htmlspecialchars($data['status'] ?? '-'); ?>
                    </strong>
                </td>
            </tr>

        </table>

    </div>


    <!-- =========================================================
         STATUS KELENGKAPAN
    ========================================================== -->

    <?php if ($status_kelangkapan === "LENGKAP"): ?>

        <div class="status-box status-lengkap">

            🟢 DOKUMEN LENGKAP

            <div class="progress-info">
                <?= $total_lengkap; ?>
                dari
                <?= $total_dokumen; ?>
                dokumen tersedia dan memiliki file.
            </div>

        </div>

    <?php else: ?>

        <div class="status-box status-belum">

            🔴 DOKUMEN BELUM LENGKAP

            <div class="progress-info">
                <?= $total_lengkap; ?>
                dari
                <?= $total_dokumen; ?>
                dokumen lengkap.
            </div>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         FORM CHECKLIST
    ========================================================== -->

    <form
        action="simpan_dokumen.php"
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="id_kredensial"
            value="<?= $id_kredensial; ?>"
        >


        <table class="dokumen-table">

            <thead>

                <tr>

                    <th width="50">
                        No
                    </th>

                    <th>
                        Dokumen
                    </th>

                    <th width="150">
                        Status
                    </th>

                    <th width="280">
                        Upload Dokumen
                    </th>

                    <th width="150">
                        File
                    </th>

                    <th width="220">
                        Keterangan
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php

            $no = 1;

            foreach ($dokumen_data as $dok):

            ?>

                <tr>

                    <!-- NOMOR -->

                    <td align="center">

                        <?= $no++; ?>

                    </td>


                    <!-- NAMA DOKUMEN -->

                    <td>

                        <strong>
                            <?= htmlspecialchars(
                                $dok['jenis_dokumen']
                            ); ?>
                        </strong>

                    </td>


                    <!-- STATUS -->

                    <td align="center">

                        <label>

                            <input
                                type="radio"
                                name="status[<?= $dok['id_dokumen']; ?>]"
                                value="ADA"

                                <?= strtoupper(
                                    $dok['status_dokumen']
                                ) === 'ADA'
                                    ? 'checked'
                                    : ''; ?>
                            >

                            <span class="status-ada">
                                Ada
                            </span>

                        </label>

                        <br>

                        <label>

                            <input
                                type="radio"
                                name="status[<?= $dok['id_dokumen']; ?>]"
                                value="TIDAK ADA"

                                <?= strtoupper(
                                    $dok['status_dokumen']
                                ) === 'TIDAK ADA'
                                    ? 'checked'
                                    : ''; ?>
                            >

                            <span class="status-tidak">
                                Tidak Ada
                            </span>

                        </label>

                    </td>


                    <!-- UPLOAD -->

                    <td>

                        <input
                            type="file"
                            name="file[<?= $dok['id_dokumen']; ?>]"
                            accept=".pdf,.jpg,.jpeg,.png"
                        >

                        <div class="file-info">
                            PDF/JPG/JPEG/PNG<br>
                            Maksimal 5 MB
                        </div>

                    </td>


                    <!-- FILE -->

                    <td align="center">

                        <?php if (!empty($dok['nama_file'])): ?>

                            <a
                                href="../uploads/dokumen_kredensial/<?= urlencode($dok['nama_file']); ?>"
                                target="_blank"
                                class="btn btn-view"
                            >
                                📄 Lihat File
                            </a>

                        <?php else: ?>

                            <span style="color:#999;">
                                Belum ada file
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- KETERANGAN -->

                    <td>

                        <textarea
                            name="keterangan[<?= $dok['id_dokumen']; ?>]"
                            rows="2"
                            placeholder="Keterangan..."
                        ><?= htmlspecialchars(
                            $dok['keterangan'] ?? ''
                        ); ?></textarea>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>


        <div class="bottom-area">

            <button
                type="submit"
                class="btn btn-save"
            >
                💾 Simpan Checklist & Dokumen
            </button>

        </div>

    </form>

</div>

</body>

</html>