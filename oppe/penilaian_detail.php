<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$id_penilaian = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_penilaian <= 0) {
    die("ID penilaian tidak valid.");
}

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function namaLengkap($data)
{
    $nama = '';

    if (!empty($data['gelar_depan'])) {
        $nama .= trim($data['gelar_depan']) . ' ';
    }

    $nama .= trim($data['nama_lengkap']);

    if (!empty($data['gelar_belakang'])) {
        $nama .= ', ' . trim($data['gelar_belakang']);
    }

    return $nama;
}

/*
|--------------------------------------------------------------------------
| DATA HEADER PENILAIAN
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        p.*,
        per.nama_periode,
        per.tanggal_mulai,
        per.tanggal_selesai,

        n.nomor_induk,
        n.nik,
        n.nip,
        n.nama_lengkap,
        n.gelar_depan,
        n.gelar_belakang,

        pr.nama_profesi,
        u.nama_unit,
        j.nama_jabatan,
        sk.nama_status

    FROM oppe_penilaian p

    INNER JOIN oppe_periode per
        ON p.id_periode = per.id_periode

    INNER JOIN tenaga_kesehatan n
        ON p.id_nakes = n.id_nakes

    LEFT JOIN profesi pr
        ON n.id_profesi = pr.id_profesi

    LEFT JOIN unit_kerja u
        ON n.id_unit = u.id_unit

    LEFT JOIN jabatan j
        ON n.id_jabatan = j.id_jabatan

    LEFT JOIN status_kepegawaian sk
        ON n.id_status_kepegawaian = sk.id_status

    WHERE p.id_penilaian = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_penilaian);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    die("Data penilaian tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| DATA DETAIL INDIKATOR
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        d.id_detail,
        d.id_indikator,
        d.nilai,
        d.catatan,

        i.komponen,
        i.nama_indikator,
        i.deskripsi,
        i.bobot

    FROM oppe_detail d

    INNER JOIN oppe_indikator i
        ON d.id_indikator = i.id_indikator

    WHERE d.id_penilaian = ?

    ORDER BY
        CASE i.komponen
            WHEN 'Kompetensi Profesional' THEN 1
            WHEN 'Keselamatan Pasien & Mutu Pelayanan' THEN 2
            WHEN 'Kepatuhan SOP & Regulasi' THEN 3
            WHEN 'Etika & Profesionalisme' THEN 4
            WHEN 'Komunikasi & Kolaborasi' THEN 5
            WHEN 'Kedisiplinan, K3 & Pengembangan Profesional' THEN 6
            ELSE 99
        END,
        i.id_indikator
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query detail gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_penilaian);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$detail = [];

while ($row = mysqli_fetch_assoc($result)) {
    $detail[] = $row;
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| BOBOT KOMPONEN
|--------------------------------------------------------------------------
*/
$bobot_komponen = [

    'Kompetensi Profesional' => 20,

    'Keselamatan Pasien & Mutu Pelayanan' => 20,

    'Kepatuhan SOP & Regulasi' => 15,

    'Etika & Profesionalisme' => 15,

    'Komunikasi & Kolaborasi' => 15,

    'Kedisiplinan, K3 & Pengembangan Profesional' => 15
];


/*
|--------------------------------------------------------------------------
| REKAP KOMPONEN
|--------------------------------------------------------------------------
*/
$rekap = [];

foreach ($bobot_komponen as $komponen => $bobot) {

    $rekap[$komponen] = [
        'bobot' => $bobot,
        'jumlah' => 0,
        'jumlah_indikator' => 0,
        'rata_rata' => 0,
        'terbobot' => 0
    ];
}


foreach ($detail as $row) {

    $komponen = $row['komponen'];

    if (!isset($rekap[$komponen])) {
        continue;
    }

    $rekap[$komponen]['jumlah'] += (float)$row['nilai'];

    $rekap[$komponen]['jumlah_indikator']++;
}


/*
|--------------------------------------------------------------------------
| HITUNG NILAI
|--------------------------------------------------------------------------
*/
$nilai_hitung = 0;

foreach ($rekap as $komponen => &$item) {

    if ($item['jumlah_indikator'] > 0) {

        $item['rata_rata'] =
            $item['jumlah']
            /
            $item['jumlah_indikator'];

        $item['terbobot'] =
            $item['rata_rata']
            *
            ($item['bobot'] / 100);

        $nilai_hitung += $item['terbobot'];
    }
}

unset($item);

$nilai_hitung = round($nilai_hitung, 2);


/*
|--------------------------------------------------------------------------
| NILAI AKHIR DARI DATABASE
|--------------------------------------------------------------------------
*/
$nilai_akhir = (float)$data['nilai_akhir'];

$kategori = $data['kategori'];


/*
|--------------------------------------------------------------------------
| WARNA KATEGORI
|--------------------------------------------------------------------------
*/
if ($kategori === 'Sangat Baik') {

    $kelas_kategori = 'sangat-baik';

} elseif ($kategori === 'Baik') {

    $kelas_kategori = 'baik';

} elseif ($kategori === 'Cukup') {

    $kelas_kategori = 'cukup';

} else {

    $kelas_kategori = 'perbaikan';
}


/*
|--------------------------------------------------------------------------
| NAMA NAKES
|--------------------------------------------------------------------------
*/
$nama_nakes = namaLengkap($data);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Hasil OPPE - <?= e($nama_nakes); ?>
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 20px;
            color: #1e293b;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0 0 5px;
        }

        .header p {
            margin: 5px 0;
            color: #64748b;
        }

        .card {
            background: white;
            border-radius: 10px;
            border: 1px solid #dbe2ea;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: #f8fafc;
            border-bottom: 1px solid #dbe2ea;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .card-body {
            padding: 20px;
        }

        .identity {
            display: grid;
            grid-template-columns: 180px 1fr 180px 1fr;
            gap: 12px 15px;
        }

        .identity .label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .komponen {
            background: #e2e8f0;
            font-weight: bold;
        }

        .bobot {
            text-align: center;
            white-space: nowrap;
        }

        .nilai {
            text-align: center;
            font-weight: bold;
        }

        .terbobot {
            text-align: center;
            font-weight: bold;
        }

        .deskripsi {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        .rekap-table td {
            vertical-align: middle;
        }

        .hasil {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .nilai-box {
            text-align: center;
            padding: 30px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
        }

        .nilai-box .judul {
            font-size: 16px;
            color: #64748b;
        }

        .nilai-box .angka {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }

        .kategori {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 18px;
        }

        .sangat-baik {
            background: #dcfce7;
            color: #166534;
        }

        .baik {
            background: #dbeafe;
            color: #1e40af;
        }

        .cukup {
            background: #fef3c7;
            color: #92400e;
        }

        .perbaikan {
            background: #fee2e2;
            color: #991b1b;
        }

        .catatan-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            min-height: 80px;
            white-space: pre-wrap;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 20px;
        }

        .actions-right {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 17px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-success {
            background: #16a34a;
            color: white;
        }

        .status {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 15px;
            background: #dbeafe;
            color: #1e40af;
            font-weight: bold;
        }

        @media (max-width: 800px) {

            body {
                padding: 10px;
            }

            .identity {
                grid-template-columns: 1fr;
            }

            .hasil {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            .actions-right {
                flex-direction: column;
            }

        }

        @media print {

            body {
                background: white;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .container {
                max-width: none;
            }

            .card {
                border: 1px solid #999;
                break-inside: avoid;
            }

        }

    </style>

</head>

<body>

<div class="container">


    <!-- HEADER -->

    <div class="header">

        <h1>HASIL OPPE</h1>

        <p>
            Objective Professional Practice Evaluation
        </p>

        <p>
            Evaluasi Kinerja Profesional Tenaga Kesehatan Lainnya
        </p>

        <?php if ($data['status'] === 'DINILAI'): ?>

            <p>
                <span class="status">
                    STATUS: DINILAI
                </span>
            </p>

        <?php endif; ?>

    </div>


    <!-- TOMBOL -->

    <div class="actions">

        <a href="penilaian.php"
           class="btn btn-secondary">
            ← Kembali
        </a>

        <div class="actions-right">

            <a href="penilaian_form.php?id=<?= $id_penilaian; ?>"
               class="btn btn-primary">
                ✏️ Edit Penilaian
            </a>

            <a href="cetak.php?id=<?= $id_penilaian; ?>"
               target="_blank"
               class="btn btn-success">
                🖨️ Cetak OPPE
            </a>

        </div>

    </div>


    <!-- IDENTITAS -->

    <div class="card">

        <div class="card-header">
            A. IDENTITAS TENAGA KESEHATAN
        </div>

        <div class="card-body">

            <div class="identity">

                <div class="label">
                    Nama
                </div>

                <div>
                    <?= e($nama_nakes); ?>
                </div>


                <div class="label">
                    Nomor Induk
                </div>

                <div>
                    <?= e($data['nomor_induk']); ?>
                </div>


                <div class="label">
                    NIK
                </div>

                <div>
                    <?= e($data['nik']); ?>
                </div>


                <div class="label">
                    NIP
                </div>

                <div>
                    <?= e($data['nip']); ?>
                </div>


                <div class="label">
                    Profesi
                </div>

                <div>
                    <?= e($data['nama_profesi']); ?>
                </div>


                <div class="label">
                    Unit Kerja
                </div>

                <div>
                    <?= e($data['nama_unit']); ?>
                </div>


                <div class="label">
                    Jabatan
                </div>

                <div>
                    <?= e($data['nama_jabatan']); ?>
                </div>


                <div class="label">
                    Status Kepegawaian
                </div>

                <div>
                    <?= e($data['nama_status']); ?>
                </div>


                <div class="label">
                    Periode OPPE
                </div>

                <div>
                    <?= e($data['nama_periode']); ?>
                </div>


                <div class="label">
                    Tanggal Penilaian
                </div>

                <div>
                    <?= date(
                        'd-m-Y',
                        strtotime($data['tanggal_penilaian'])
                    ); ?>
                </div>

            </div>

        </div>

    </div>


    <!-- DETAIL INDIKATOR -->

    <div class="card">

        <div class="card-header">
            B. HASIL PENILAIAN INDIKATOR
        </div>

        <div class="card-body">

            <?php

            $nomor = 1;
            $komponen_sebelumnya = '';

            ?>

            <table>

                <thead>

                    <tr>

                        <th width="50">
                            No
                        </th>

                        <th>
                            Indikator
                        </th>

                        <th width="100">
                            Bobot
                        </th>

                        <th width="100">
                            Nilai
                        </th>

                        <th>
                            Catatan
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($detail as $row): ?>

                    <?php

                    $komponen = $row['komponen'];

                    if ($komponen !== $komponen_sebelumnya):

                        $komponen_sebelumnya = $komponen;

                    ?>

                        <tr>

                            <td colspan="5"
                                class="komponen">

                                <?= e($komponen); ?>

                                —
                                Bobot
                                <?= e($bobot_komponen[$komponen] ?? 0); ?>%

                            </td>

                        </tr>

                    <?php endif; ?>


                    <tr>

                        <td class="center">
                            <?= $nomor++; ?>
                        </td>

                        <td>

                            <strong>
                                <?= e($row['nama_indikator']); ?>
                            </strong>

                            <?php if (!empty($row['deskripsi'])): ?>

                                <div class="deskripsi">
                                    <?= e($row['deskripsi']); ?>
                                </div>

                            <?php endif; ?>

                        </td>

                        <td class="bobot">
                            <?= e($row['bobot']); ?>%
                        </td>

                        <td class="nilai">
                            <?= number_format(
                                (float)$row['nilai'],
                                2,
                                ',',
                                '.'
                            ); ?>
                        </td>

                        <td>
                            <?= nl2br(
                                e($row['catatan'])
                            ); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($detail)): ?>

                    <tr>

                        <td colspan="5"
                            class="center">

                            Belum ada detail penilaian.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- REKAP KOMPONEN -->

    <div class="card">

        <div class="card-header">
            C. REKAPITULASI NILAI
        </div>

        <div class="card-body">

            <table class="rekap-table">

                <thead>

                    <tr>

                        <th>
                            No
                        </th>

                        <th>
                            Komponen Penilaian
                        </th>

                        <th>
                            Bobot
                        </th>

                        <th>
                            Rata-rata
                        </th>

                        <th>
                            Nilai Terbobot
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php

                $no = 1;

                foreach ($rekap as $komponen => $item):

                ?>

                    <tr>

                        <td class="center">
                            <?= $no++; ?>
                        </td>

                        <td>
                            <strong>
                                <?= e($komponen); ?>
                            </strong>
                        </td>

                        <td class="center">
                            <?= $item['bobot']; ?>%
                        </td>

                        <td class="center">

                            <?php if ($item['jumlah_indikator'] > 0): ?>

                                <?= number_format(
                                    $item['rata_rata'],
                                    2,
                                    ',',
                                    '.'
                                ); ?>

                            <?php else: ?>

                                -

                            <?php endif; ?>

                        </td>

                        <td class="terbobot">

                            <?= number_format(
                                $item['terbobot'],
                                2,
                                ',',
                                '.'
                            ); ?>

                        </td>

                    </tr>

                <?php endforeach; ?>


                <tr>

                    <td colspan="2"
                        style="text-align:right;font-weight:bold;">

                        NILAI AKHIR

                    </td>

                    <td class="center">
                        <strong>100%</strong>
                    </td>

                    <td></td>

                    <td class="terbobot"
                        style="font-size:20px;">

                        <?= number_format(
                            $nilai_akhir,
                            2,
                            ',',
                            '.'
                        ); ?>

                    </td>

                </tr>

                </tbody>

            </table>

        </div>

    </div>


    <!-- HASIL AKHIR -->

    <div class="card">

        <div class="card-header">
            D. KESIMPULAN HASIL OPPE
        </div>

        <div class="card-body">

            <div class="hasil">

                <div class="nilai-box">

                    <div class="judul">
                        NILAI AKHIR
                    </div>

                    <div class="angka">

                        <?= number_format(
                            $nilai_akhir,
                            2,
                            ',',
                            '.'
                        ); ?>

                    </div>

                    <div>
                        Skala 1,00 – 4,00
                    </div>

                </div>


                <div class="nilai-box">

                    <div class="judul">
                        KATEGORI
                    </div>

                    <div style="margin-top:20px;">

                        <span class="kategori <?= $kelas_kategori; ?>">

                            <?= e($kategori); ?>

                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- CATATAN -->

    <div class="card">

        <div class="card-header">
            E. CATATAN DAN TINDAK LANJUT
        </div>

        <div class="card-body">

            <p>
                <strong>Catatan Umum</strong>
            </p>

            <div class="catatan-box">

                <?= nl2br(
                    e($data['catatan'])
                ); ?>

            </div>


            <p style="margin-top:20px;">

                <strong>
                    Tindak Lanjut
                </strong>

            </p>

            <div class="catatan-box">

                <?= nl2br(
                    e($data['tindak_lanjut'])
                ); ?>

            </div>

        </div>

    </div>


    <!-- INFORMASI PENILAI -->

    <div class="card">

        <div class="card-header">
            F. PENGESAHAN PENILAIAN
        </div>

        <div class="card-body">

            <table>

                <tr>

                    <td width="50%">
                        <strong>Penilai</strong>
                    </td>

                    <td>
                        <?= e($data['penilai']); ?>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Tanggal Penilaian
                        </strong>
                    </td>

                    <td>
                        <?= date(
                            'd-m-Y',
                            strtotime($data['tanggal_penilaian'])
                        ); ?>
                    </td>

                </tr>

                <tr>

                    <td>
                        <strong>
                            Status
                        </strong>
                    </td>

                    <td>
                        <?= e($data['status']); ?>
                    </td>

                </tr>

            </table>

        </div>

    </div>


    <!-- TOMBOL BAWAH -->

    <div class="actions">

        <a href="penilaian.php"
           class="btn btn-secondary">

            ← Kembali ke Daftar OPPE

        </a>

        <div class="actions-right">

            <a href="penilaian_form.php?id=<?= $id_penilaian; ?>"
               class="btn btn-primary">

                ✏️ Edit

            </a>

            <a href="cetak.php?id=<?= $id_penilaian; ?>"
               target="_blank"
               class="btn btn-success">

                🖨️ Cetak

            </a>

        </div>

    </div>

</div>

</body>

</html>