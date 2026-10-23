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
| DATA PENILAIAN
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
    die("Data OPPE tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| DETAIL INDIKATOR
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

$nilai_akhir = (float)$data['nilai_akhir'];
$kategori = $data['kategori'];

$nama_nakes = namaLengkap($data);


/*
|--------------------------------------------------------------------------
| TANGGAL
|--------------------------------------------------------------------------
*/
$tanggal_penilaian = '';

if (!empty($data['tanggal_penilaian'])) {

    $tanggal_penilaian = date(
        'd-m-Y',
        strtotime($data['tanggal_penilaian'])
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>
Formulir OPPE - <?= e($nama_nakes); ?>
</title>

<style>

@page {
    size: A4 portrait;
    margin: 12mm 12mm 15mm 12mm;
}

* {
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #000;
    margin: 0;
    padding: 0;
    background: #fff;
}

.page {
    width: 100%;
    margin: auto;
}

.header {
    text-align: center;
    margin-bottom: 12px;
}

.header h1 {
    margin: 0;
    font-size: 18px;
    font-weight: bold;
}

.header h2 {
    margin: 3px 0;
    font-size: 13px;
}

.header p {
    margin: 2px 0;
    font-size: 11px;
}

.garis {
    border-bottom: 2px solid #000;
    margin-top: 8px;
}

.subjudul {
    font-weight: bold;
    font-size: 12px;
    margin-top: 12px;
    margin-bottom: 5px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    border: 1px solid #000;
    padding: 5px;
    vertical-align: top;
}

th {
    text-align: center;
    font-weight: bold;
}

.identity td {
    padding: 4px 6px;
}

.label {
    width: 18%;
    font-weight: bold;
}

.value {
    width: 32%;
}

.petunjuk {
    border: 1px solid #000;
    padding: 7px;
    margin-bottom: 8px;
}

.skala-table {
    margin-top: 5px;
}

.skala-table td {
    text-align: center;
    padding: 5px;
}

.komponen-row td {
    font-weight: bold;
    background: #eee;
}

.center {
    text-align: center;
}

.right {
    text-align: right;
}

.indikator {
    width: 38%;
}

.catatan {
    width: 27%;
}

.nilai {
    width: 8%;
    text-align: center;
    font-weight: bold;
}

.bobot {
    width: 9%;
    text-align: center;
}

.rekap td {
    vertical-align: middle;
}

.hasil-box {
    margin-top: 10px;
}

.nilai-akhir {
    font-size: 20px;
    font-weight: bold;
    text-align: center;
}

.kategori {
    font-size: 16px;
    font-weight: bold;
    text-align: center;
}

.catatan-box {
    min-height: 55px;
    white-space: pre-wrap;
}

.signature {
    margin-top: 30px;
}

.signature td {
    height: 105px;
    text-align: center;
    vertical-align: top;
}

.footer {
    margin-top: 15px;
    font-size: 9px;
    text-align: right;
}

.no-print {
    margin-bottom: 15px;
    text-align: right;
}

.btn {
    display: inline-block;
    padding: 8px 14px;
    margin-left: 5px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    font-size: 12px;
}

.btn-print {
    background: #2563eb;
    color: white;
}

.btn-back {
    background: #64748b;
    color: white;
}

.page-break {
    page-break-before: always;
}

@media print {

    .no-print {
        display: none;
    }

    body {
        font-size: 10px;
    }

    th,
    td {
        padding: 4px;
    }

}

</style>

</head>

<body>

<div class="page">


<!-- TOMBOL -->

<div class="no-print">

    <a href="penilaian_detail.php?id=<?= $id_penilaian; ?>"
       class="btn btn-back">

        ← Kembali

    </a>

    <button onclick="window.print()"
            class="btn btn-print">

        🖨️ Cetak / Simpan PDF

    </button>

</div>


<!-- HEADER -->

<div class="header">

    <h1>
        FORMULIR OBJECTIVE PROFESSIONAL PRACTICE EVALUATION
    </h1>

    <h2>
        (OPPE)
    </h2>

    <p>
        TENAGA KESEHATAN LAINNYA
    </p>

    <div class="garis"></div>

</div>


<!-- IDENTITAS -->

<div class="subjudul">
    A. IDENTITAS TENAGA KESEHATAN
</div>

<table class="identity">

<tr>

    <td class="label">Nama</td>

    <td class="value">
        <?= e($nama_nakes); ?>
    </td>

    <td class="label">Nomor Induk</td>

    <td class="value">
        <?= e($data['nomor_induk']); ?>
    </td>

</tr>

<tr>

    <td class="label">NIK</td>

    <td>
        <?= e($data['nik']); ?>
    </td>

    <td class="label">NIP</td>

    <td>
        <?= e($data['nip']); ?>
    </td>

</tr>

<tr>

    <td class="label">Profesi</td>

    <td>
        <?= e($data['nama_profesi']); ?>
    </td>

    <td class="label">Jabatan</td>

    <td>
        <?= e($data['nama_jabatan']); ?>
    </td>

</tr>

<tr>

    <td class="label">Unit Kerja</td>

    <td>
        <?= e($data['nama_unit']); ?>
    </td>

    <td class="label">Status Kepegawaian</td>

    <td>
        <?= e($data['nama_status']); ?>
    </td>

</tr>

<tr>

    <td class="label">Periode OPPE</td>

    <td>
        <?= e($data['nama_periode']); ?>
    </td>

    <td class="label">Tanggal Penilaian</td>

    <td>
        <?= e($tanggal_penilaian); ?>
    </td>

</tr>

</table>


<!-- PETUNJUK -->

<div class="subjudul">
    B. PETUNJUK PENILAIAN
</div>

<div class="petunjuk">

    Berikan nilai berdasarkan bukti kinerja, observasi,
    dokumentasi pelayanan, kepatuhan terhadap SOP,
    standar profesi, keselamatan pasien, serta perilaku
    profesional selama periode penilaian.

</div>

<table class="skala-table">

<tr>

    <th>Nilai</th>
    <th>Kategori</th>
    <th>Interpretasi</th>

</tr>

<tr>

    <td>4</td>
    <td>Sangat Baik</td>
    <td>Melampaui standar</td>

</tr>

<tr>

    <td>3</td>
    <td>Baik</td>
    <td>Memenuhi standar</td>

</tr>

<tr>

    <td>2</td>
    <td>Cukup</td>
    <td>Memenuhi sebagian standar</td>

</tr>

<tr>

    <td>1</td>
    <td>Perlu Perbaikan</td>
    <td>Tidak memenuhi standar</td>

</tr>

</table>


<!-- PENILAIAN -->

<div class="subjudul">
    C. HASIL PENILAIAN
</div>

<table>

<thead>

<tr>

    <th width="5%">No</th>

    <th class="indikator">
        Indikator Penilaian
    </th>

    <th class="bobot">
        Bobot
    </th>

    <th class="nilai">
        Nilai
    </th>

    <th class="catatan">
        Catatan / Bukti
    </th>

</tr>

</thead>

<tbody>

<?php

$nomor = 1;
$komponen_sebelumnya = '';

foreach ($detail as $row):

    $komponen = $row['komponen'];

    if ($komponen !== $komponen_sebelumnya):

        $komponen_sebelumnya = $komponen;

?>

<tr class="komponen-row">

    <td colspan="2">

        <?= e($komponen); ?>

    </td>

    <td colspan="3">

        Bobot:
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

            <br>

            <small>
                <?= e($row['deskripsi']); ?>
            </small>

        <?php endif; ?>

    </td>

    <td class="center">

        <?= e($row['bobot']); ?>%

    </td>

    <td class="nilai">

        <?= number_format(
            (float)$row['nilai'],
            0,
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

</tbody>

</table>


<!-- REKAP -->

<div class="subjudul">
    D. REKAPITULASI NILAI OPPE
</div>

<table class="rekap">

<thead>

<tr>

    <th width="5%">No</th>

    <th>Komponen Penilaian</th>

    <th width="12%">Bobot</th>

    <th width="15%">Rata-rata</th>

    <th width="17%">Nilai Terbobot</th>

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
        <?= e($komponen); ?>
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

    <td class="center">

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
        class="right">

        <strong>
            NILAI AKHIR
        </strong>

    </td>

    <td class="center">
        <strong>100%</strong>
    </td>

    <td></td>

    <td class="nilai-akhir">

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


<!-- KESIMPULAN -->

<div class="subjudul">
    E. KESIMPULAN HASIL OPPE
</div>

<table class="hasil-box">

<tr>

    <td width="50%"
        class="nilai-akhir">

        NILAI AKHIR

        <br><br>

        <?= number_format(
            $nilai_akhir,
            2,
            ',',
            '.'
        ); ?>

    </td>


    <td width="50%"
        class="kategori">

        KATEGORI

        <br><br>

        <?= e($kategori); ?>

    </td>

</tr>

</table>


<!-- CATATAN -->

<div class="subjudul">
    F. CATATAN DAN TINDAK LANJUT
</div>

<table>

<tr>

    <td width="50%">

        <strong>
            Catatan Umum
        </strong>

        <div class="catatan-box"
             style="margin-top:8px;">

            <?= nl2br(
                e($data['catatan'])
            ); ?>

        </div>

    </td>

    <td width="50%">

        <strong>
            Tindak Lanjut
        </strong>

        <div class="catatan-box"
             style="margin-top:8px;">

            <?= nl2br(
                e($data['tindak_lanjut'])
            ); ?>

        </div>

    </td>

</tr>

</table>


<!-- TANDA TANGAN -->

<div class="signature">

<table>

<tr>

    <td width="50%">

        Mengetahui,<br>
        Kepala Unit/Instalasi

        <br><br><br><br><br>

        <strong>
            (........................................)
        </strong>

        <br>

        NIP. ........................................

    </td>


    <td width="50%">

        Penilai,<br>
        Atasan Langsung

        <br><br><br><br><br>

        <strong>
            <?= e($data['penilai']); ?>
        </strong>

        <br>

        NIP. ........................................

    </td>

</tr>

</table>

</div>


<!-- FOOTER -->

<div class="footer">

    Dokumen OPPE Tenaga Kesehatan Lainnya —
    RSUD Subang

</div>

</div>

</body>

</html>