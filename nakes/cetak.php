<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/* =====================================================
   AMBIL FILTER
===================================================== */

$keyword = trim($_GET['keyword'] ?? '');

$id_profesi = (int) ($_GET['id_profesi'] ?? 0);

$id_unit = (int) ($_GET['id_unit'] ?? 0);

$id_status = (int) ($_GET['id_status'] ?? 0);


/* =====================================================
   FILTER QUERY
===================================================== */

$where = [];


if ($keyword !== '') {

    $keyword_safe = mysqli_real_escape_string(
        $conn,
        $keyword
    );

    $where[] = "(
        t.nama_lengkap LIKE '%$keyword_safe%'
        OR t.nip LIKE '%$keyword_safe%'
        OR t.nik LIKE '%$keyword_safe%'
    )";
}


if ($id_profesi > 0) {

    $where[] = "t.id_profesi = $id_profesi";

}


if ($id_unit > 0) {

    $where[] = "t.id_unit = $id_unit";

}


if ($id_status > 0) {

    $where[] = "t.id_status_kepegawaian = $id_status";

}


$where_sql = '';

if (!empty($where)) {

    $where_sql =
        "WHERE " .
        implode(" AND ", $where);

}


/* =====================================================
   AMBIL DATA
===================================================== */

$sql = "
SELECT
    t.id_nakes,
    t.nik,
    t.nip,
    t.nama_lengkap,
    t.gelar_depan,
    t.gelar_belakang,
    t.status_aktif,

    p.nama_profesi,
    u.nama_unit,
    j.nama_jabatan,
    s.nama_status

FROM tenaga_kesehatan t

LEFT JOIN profesi p
    ON t.id_profesi = p.id_profesi

LEFT JOIN unit_kerja u
    ON t.id_unit = u.id_unit

LEFT JOIN jabatan j
    ON t.id_jabatan = j.id_jabatan

LEFT JOIN status_kepegawaian s
    ON t.id_status_kepegawaian = s.id_status

$where_sql

ORDER BY t.nama_lengkap ASC
";


$result = mysqli_query($conn, $sql);


if (!$result) {

    die(
        "Gagal mengambil data: " .
        mysqli_error($conn)
    );

}


$total = mysqli_num_rows($result);


/* =====================================================
   JUDUL FILTER
===================================================== */

$judul_filter = "SELURUH TENAGA KESEHATAN";


if ($keyword !== '') {

    $judul_filter =
        "PENCARIAN: " .
        strtoupper($keyword);

}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<title>Cetak Data Tenaga Kesehatan</title>


<style>

@page {

    size: A4 landscape;

    margin: 15mm;

}


body {

    font-family: Arial, sans-serif;

    font-size: 11px;

    color: #000;

}


.header {

    text-align: center;

    margin-bottom: 15px;

}


.header h2 {

    margin: 0;

    font-size: 18px;

}


.header h3 {

    margin: 5px 0;

    font-size: 14px;

}


.header p {

    margin: 3px 0;

    font-size: 11px;

}


table {

    width: 100%;

    border-collapse: collapse;

    margin-top: 10px;

}


th {

    background: #eeeeee;

    font-weight: bold;

    text-align: center;

}


th,
td {

    border: 1px solid #000;

    padding: 5px;

}


td {

    vertical-align: top;

}


.center {

    text-align: center;

}


.footer {

    margin-top: 10px;

    font-size: 11px;

}


.no-print {

    margin-bottom: 15px;

}


.btn {

    padding: 8px 15px;

    border: none;

    border-radius: 4px;

    cursor: pointer;

    color: white;

}


.btn-print {

    background: #198754;

}


.btn-close {

    background: #6c757d;

}


@media print {

    .no-print {

        display: none;

    }

}

</style>

</head>


<body>


<div class="no-print">

    <button
        onclick="window.print()"
        class="btn btn-print"
    >
        🖨 Cetak
    </button>


    <button
        onclick="window.close()"
        class="btn btn-close"
    >
        ✖ Tutup
    </button>

</div>


<div class="header">

    <h2>
        RUMAH SAKIT UMUM DAERAH SUBANG
    </h2>

    <h3>
        DATA TENAGA KESEHATAN
    </h3>

    <p>
        <?= htmlspecialchars($judul_filter); ?>
    </p>

    <p>
        Jumlah Data:
        <strong><?= $total; ?></strong>
        orang
    </p>

</div>


<table>

<thead>

<tr>

    <th width="4%">No</th>

    <th width="20%">Nama Lengkap</th>

    <th width="15%">NIP</th>

    <th width="15%">Profesi</th>

    <th width="14%">Unit Kerja</th>

    <th width="12%">Jabatan</th>

    <th width="12%">Status Kepegawaian</th>

    <th width="8%">Status</th>

</tr>

</thead>


<tbody>

<?php

$no = 1;

while (
    $row = mysqli_fetch_assoc($result)
) {

    $nama = trim(
        ($row['gelar_depan'] ?? '') .
        ' ' .
        ($row['nama_lengkap'] ?? '') .
        ' ' .
        ($row['gelar_belakang'] ?? '')
    );

?>

<tr>

    <td class="center">
        <?= $no++; ?>
    </td>


    <td>
        <?= htmlspecialchars($nama); ?>
    </td>


    <td>
        <?= htmlspecialchars(
            $row['nip'] ?? '-'
        ); ?>
    </td>


    <td>
        <?= htmlspecialchars(
            $row['nama_profesi'] ?? '-'
        ); ?>
    </td>


    <td>
        <?= htmlspecialchars(
            $row['nama_unit'] ?? '-'
        ); ?>
    </td>


    <td>
        <?= htmlspecialchars(
            $row['nama_jabatan'] ?? '-'
        ); ?>
    </td>


    <td>
        <?= htmlspecialchars(
            $row['nama_status'] ?? '-'
        ); ?>
    </td>


    <td class="center">
        <?= htmlspecialchars(
            $row['status_aktif'] ?? '-'
        ); ?>
    </td>

</tr>

<?php } ?>


<?php if ($total == 0) { ?>

<tr>

    <td
        colspan="8"
        class="center"
    >
        Tidak ada data.
    </td>

</tr>

<?php } ?>

</tbody>

</table>


<div class="footer">

    Dicetak pada:
    <?= date('d-m-Y H:i:s'); ?>

</div>


</body>

</html>