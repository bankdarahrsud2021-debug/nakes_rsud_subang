<?php

require_once "../config/auth.php";
require_once "../config/database.php";

/* =========================================================
   ROLE
========================================================= */

$role = strtoupper(
    trim($_SESSION['role'] ?? 'ANGGOTA')
);

$boleh_hapus = in_array(
    $role,
    ['ADMIN', 'PENGURUS'],
    true
);


/* =========================================================
   FILTER
========================================================= */

$id_periode = isset($_GET['id_periode']) ? (int)$_GET['id_periode'] : 0;
$id_profesi = isset($_GET['id_profesi']) ? (int)$_GET['id_profesi'] : 0;
$id_unit    = isset($_GET['id_unit']) ? (int)$_GET['id_unit'] : 0;
$kategori   = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';


/* =========================================================
   DATA PERIODE
========================================================= */

$periode_list = [];

$sql = "
    SELECT
        id_periode,
        nama_periode,
        tanggal_mulai,
        tanggal_selesai,
        status
    FROM oppe_periode
    ORDER BY tanggal_mulai DESC, id_periode DESC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $periode_list[] = $row;
    }

}


/* =========================================================
   DATA PROFESI
========================================================= */

$profesi_list = [];

$sql = "
    SELECT
        id_profesi,
        kode_profesi,
        nama_profesi
    FROM profesi
    ORDER BY nama_profesi ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $profesi_list[] = $row;
    }

}


/* =========================================================
   DATA UNIT
========================================================= */

$unit_list = [];

$sql = "
    SELECT
        id_unit,
        nama_unit
    FROM unit_kerja
    ORDER BY nama_unit ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $unit_list[] = $row;
    }

}


/* =========================================================
   FILTER SQL
========================================================= */

$where = [];

if ($id_periode > 0) {

    $where[] = "p.id_periode = " . $id_periode;

}

if ($id_profesi > 0) {

    $where[] = "n.id_profesi = " . $id_profesi;

}

if ($id_unit > 0) {

    $where[] = "n.id_unit = " . $id_unit;

}

if ($kategori !== '') {

    $kategori_safe = mysqli_real_escape_string(
        $conn,
        $kategori
    );

    $where[] = "p.kategori = '" . $kategori_safe . "'";

}

$where_sql = '';

if (count($where) > 0) {

    $where_sql = "WHERE " . implode(" AND ", $where);

}


/* =========================================================
   DATA PENILAIAN
========================================================= */

$data_penilaian = [];

$sql = "
    SELECT
        p.id_penilaian,
        p.id_nakes,
        p.id_periode,
        p.tanggal_penilaian,
        p.penilai,
        p.total_nilai,
        p.nilai_akhir,
        p.kategori,
        p.status,

        n.nama_lengkap,
        n.nomor_induk,

        pr.nama_profesi,

        u.nama_unit,

        op.nama_periode

    FROM oppe_penilaian p

    INNER JOIN tenaga_kesehatan n
        ON p.id_nakes = n.id_nakes

    LEFT JOIN profesi pr
        ON n.id_profesi = pr.id_profesi

    LEFT JOIN unit_kerja u
        ON n.id_unit = u.id_unit

    LEFT JOIN oppe_periode op
        ON p.id_periode = op.id_periode

    $where_sql

    ORDER BY
        op.tanggal_mulai DESC,
        n.nama_lengkap ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {

    die(
        "Gagal mengambil data penilaian: " .
        mysqli_error($conn)
    );

}

while ($row = mysqli_fetch_assoc($result)) {

    $data_penilaian[] = $row;

}


/* =========================================================
   STATISTIK
========================================================= */

$total_penilaian = count($data_penilaian);

$sangat_baik = 0;
$baik = 0;
$cukup = 0;
$perlu_perbaikan = 0;

$total_nilai = 0;
$jumlah_nilai = 0;

foreach ($data_penilaian as $row) {

    $kat = strtoupper(
        trim($row['kategori'] ?? '')
    );

    switch ($kat) {

        case 'SANGAT BAIK':
            $sangat_baik++;
            break;

        case 'BAIK':
            $baik++;
            break;

        case 'CUKUP':
            $cukup++;
            break;

        case 'PERLU PERBAIKAN':
            $perlu_perbaikan++;
            break;

    }

    if (
        isset($row['nilai_akhir']) &&
        $row['nilai_akhir'] !== '' &&
        $row['nilai_akhir'] !== null
    ) {

        $total_nilai += (float)$row['nilai_akhir'];
        $jumlah_nilai++;

    }

}

$rata_rata = 0;

if ($jumlah_nilai > 0) {

    $rata_rata = $total_nilai / $jumlah_nilai;

}


/* =========================================================
   TOTAL NAKES SESUAI FILTER
========================================================= */

$where_nakes = [];

if ($id_profesi > 0) {

    $where_nakes[] =
        "id_profesi = " . $id_profesi;

}

if ($id_unit > 0) {

    $where_nakes[] =
        "id_unit = " . $id_unit;

}

$where_nakes_sql = '';

if (count($where_nakes) > 0) {

    $where_nakes_sql =
        "WHERE " . implode(" AND ", $where_nakes);

}

$sql = "
    SELECT COUNT(*) AS jumlah
    FROM tenaga_kesehatan
    $where_nakes_sql
";

$result = mysqli_query($conn, $sql);

$total_nakes = 0;

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_nakes = (int)$row['jumlah'];

}

$belum_dinilai =
    $total_nakes - $total_penilaian;

if ($belum_dinilai < 0) {

    $belum_dinilai = 0;

}


/* =========================================================
   PERSENTASE PENYELESAIAN
========================================================= */

$persentase_selesai = 0;

if ($total_nakes > 0) {

    $persentase_selesai =
        ($total_penilaian / $total_nakes) * 100;

}


/* =========================================================
   REKAP 6 KOMPONEN
========================================================= */

$rekap_komponen = [];

$sql = "
    SELECT
        c.id_komponen,
        c.nama_komponen,
        c.bobot,
        c.urutan,

        COUNT(DISTINCT p.id_penilaian) AS jumlah_penilaian,

        COALESCE(
            SUM(d.nilai),
            0
        ) AS total_nilai,

        COUNT(d.id_detail) AS jumlah_detail

    FROM oppe_komponen c

    LEFT JOIN oppe_indikator i
        ON i.id_komponen = c.id_komponen
        AND i.status_aktif = 1

    LEFT JOIN oppe_detail d
        ON d.id_indikator = i.id_indikator

    LEFT JOIN oppe_penilaian p
        ON p.id_penilaian = d.id_penilaian

    LEFT JOIN tenaga_kesehatan n
        ON p.id_nakes = n.id_nakes

    WHERE c.status_aktif = 1
";

if ($id_periode > 0) {

    $sql .=
        " AND (p.id_periode = $id_periode OR p.id_periode IS NULL)";

}

if ($id_profesi > 0) {

    $sql .=
        " AND (n.id_profesi = $id_profesi OR n.id_profesi IS NULL)";

}

if ($id_unit > 0) {

    $sql .=
        " AND (n.id_unit = $id_unit OR n.id_unit IS NULL)";

}

$sql .= "
    GROUP BY
        c.id_komponen,
        c.nama_komponen,
        c.bobot,
        c.urutan

    ORDER BY c.urutan ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $total_detail =
            (int)$row['jumlah_detail'];

        $total_nilai_komponen =
            (float)$row['total_nilai'];

        $rata_komponen = 0;

        if ($total_detail > 0) {

            $rata_komponen =
                $total_nilai_komponen /
                $total_detail;

        }

        $kontribusi =
            (
                $rata_komponen *
                (float)$row['bobot']
            ) / 100;

        $row['rata_rata'] =
            $rata_komponen;

        $row['kontribusi'] =
            $kontribusi;

        $rekap_komponen[] =
            $row;

    }

}


/* =========================================================
   PERIODE TERPILIH
========================================================= */

$nama_periode_terpilih =
    'Semua Periode';

if ($id_periode > 0) {

    foreach ($periode_list as $p) {

        if (
            (int)$p['id_periode'] ===
            $id_periode
        ) {

            $nama_periode_terpilih =
                $p['nama_periode'];

            break;

        }

    }

}


/* =========================================================
   URL TAMBAH PENILAIAN
========================================================= */

$url_penilaian = 'penilaian_tambah.php';

if ($id_periode > 0) {

    $url_penilaian =
        'penilaian_tambah.php?id_periode=' .
        $id_periode;

}


/* =========================================================
   URL HAPUS PERIODE
========================================================= */

$url_hapus_periode = '';

if ($id_periode > 0) {

    $url_hapus_periode =
        'periode_hapus.php?id_periode=' .
        $id_periode;

}


/* =========================================================
   PESAN
========================================================= */

$pesan_sukses = '';

if (
    isset($_GET['hapus_periode']) &&
    $_GET['hapus_periode'] === 'success'
) {

    $pesan_sukses =
        'Periode OPPE beserta seluruh penilaiannya berhasil dihapus.';

}

if (
    isset($_GET['hapus_penilaian']) &&
    $_GET['hapus_penilaian'] === 'success'
) {

    $pesan_sukses =
        'Penilaian OPPE berhasil dihapus.';

}

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Dashboard OPPE - RSUD Subang
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
    color: #333;
}

.container {
    width: 96%;
    max-width: 1550px;
    margin: 25px auto;
}

.header {
    background: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.header h1 {
    margin: 0 0 7px 0;
    font-size: 26px;
}

.header p {
    margin: 0;
    color: #666;
}

.toolbar {
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 9px 14px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-size: 13px;
}

.btn-primary {
    background: #0d6efd;
    color: white;
}

.btn-success {
    background: #198754;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-info {
    background: #0dcaf0;
    color: #000;
}

.btn-warning {
    background: #ffc107;
    color: #000;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #bb2d3b;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
}

.stat-grid {
    display: grid;
    grid-template-columns:
        repeat(5, 1fr);

    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.stat-title {
    font-size: 12px;
    color: #777;
    font-weight: bold;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 30px;
    font-weight: bold;
}

.stat-desc {
    margin-top: 6px;
    font-size: 12px;
    color: #777;
}

.progress {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.progress-bar {
    height: 100%;
    background: #0d6efd;
}

.panel {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.panel-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 15px;
}

.filter-grid {
    display: grid;
    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;
}

.form-group label {
    display: block;
    font-weight: bold;
    font-size: 12px;
    margin-bottom: 6px;
}

.form-group select {
    width: 100%;
    padding: 9px;
    border: 1px solid #ccc;
    border-radius: 6px;
    background: white;
}

.filter-actions {
    margin-top: 15px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.two-column {
    display: grid;
    grid-template-columns:
        1fr 1fr;

    gap: 20px;
}

.category-row {
    margin-bottom: 14px;
}

.category-header {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 5px;
}

.category-bar {
    width: 100%;
    height: 10px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.category-fill {
    height: 100%;
}

.fill-sangat {
    background: #198754;
}

.fill-baik {
    background: #0dcaf0;
}

.fill-cukup {
    background: #ffc107;
}

.fill-perlu {
    background: #dc3545;
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

th {
    background: #f1f3f5;
    border: 1px solid #ddd;
    padding: 10px;
    font-size: 12px;
}

td {
    border: 1px solid #ddd;
    padding: 9px;
    font-size: 12px;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: bold;
}

.badge-sangat {
    background: #d1e7dd;
    color: #0f5132;
}

.badge-baik {
    background: #cff4fc;
    color: #055160;
}

.badge-cukup {
    background: #fff3cd;
    color: #664d03;
}

.badge-perlu {
    background: #f8d7da;
    color: #842029;
}

.badge-draft {
    background: #e2e3e5;
    color: #41464b;
}

.component-name {
    font-weight: bold;
}

.component-progress {
    margin-top: 5px;
    height: 7px;
}

.component-bar {
    height: 100%;
    background: #0d6efd;
}

.empty {
    padding: 35px;
    text-align: center;
    color: #777;
}

@media(max-width:1100px) {

    .stat-grid {
        grid-template-columns:
            repeat(3, 1fr);
    }

    .filter-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .two-column {
        grid-template-columns: 1fr;
    }

}

@media(max-width:700px) {

    .stat-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

}

@media(max-width:450px) {

    .stat-grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<div class="container">

<div class="header">

    <h1>
        Dashboard OPPE
    </h1>

    <p>
        Objective Professional Practice Evaluation
        Tenaga Kesehatan Lainnya RSUD Subang
    </p>

    <div class="toolbar">

        <div>

            <a href="../dashboard/index.php"
               class="btn btn-secondary">
                ← Dashboard Utama
            </a>

        </div>

        <div>

            <a href="periode_tambah.php"
               class="btn btn-primary">
                + Periode
            </a>

            <a href="indikator.php"
               class="btn btn-info">
                ⚙ Indikator
            </a>

            <a href="<?= htmlspecialchars($url_penilaian); ?>"
               class="btn btn-primary"
               <?php if ($id_periode <= 0): ?>
                   onclick="return pilihPeriode();"
               <?php endif; ?>
            >
                + Penilaian
            </a>

        </div>

    </div>

</div>


<?php if ($pesan_sukses !== ''): ?>

    <div class="alert-success">

        ✅
        <?= htmlspecialchars($pesan_sukses); ?>

    </div>

<?php endif; ?>


<div class="stat-grid">

    <div class="stat-card">

        <div class="stat-title">
            TOTAL NAKES
        </div>

        <div class="stat-number">
            <?= number_format($total_nakes); ?>
        </div>

        <div class="stat-desc">
            Tenaga kesehatan sesuai filter
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-title">
            SUDAH DINILAI
        </div>

        <div class="stat-number">
            <?= number_format($total_penilaian); ?>
        </div>

        <div class="stat-desc">
            Penilaian OPPE
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-title">
            BELUM DINILAI
        </div>

        <div class="stat-number">
            <?= number_format($belum_dinilai); ?>
        </div>

        <div class="stat-desc">
            Memerlukan penilaian
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-title">
            RATA-RATA NILAI
        </div>

        <div class="stat-number">
            <?= number_format($rata_rata, 2); ?>
        </div>

        <div class="stat-desc">
            Skala 1,00 – 4,00
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-title">
            PENYELESAIAN
        </div>

        <div class="stat-number">
            <?= number_format(
                $persentase_selesai,
                1
            ); ?>%
        </div>

        <div class="stat-desc">
            Persentase OPPE selesai
        </div>

        <div class="progress">

            <div
                class="progress-bar"
                style="width: <?= min(
                    100,
                    $persentase_selesai
                ); ?>%">
            </div>

        </div>

    </div>

</div>


<div class="panel">

    <div class="panel-title">
        🔎 Filter Dashboard OPPE
    </div>

    <form method="GET">

        <div class="filter-grid">

            <div class="form-group">

                <label>
                    Periode
                </label>

                <select name="id_periode">

                    <option value="0">
                        -- Semua Periode --
                    </option>

                    <?php foreach ($periode_list as $p): ?>

                        <option
                            value="<?= (int)$p['id_periode']; ?>"
                            <?= $id_periode ==
                                $p['id_periode']
                                ? 'selected'
                                : ''; ?>
                        >

                            <?= htmlspecialchars(
                                $p['nama_periode']
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Profesi
                </label>

                <select name="id_profesi">

                    <option value="0">
                        -- Semua Profesi --
                    </option>

                    <?php foreach ($profesi_list as $p): ?>

                        <option
                            value="<?= (int)$p['id_profesi']; ?>"
                            <?= $id_profesi ==
                                $p['id_profesi']
                                ? 'selected'
                                : ''; ?>
                        >

                            <?= htmlspecialchars(
                                $p['nama_profesi']
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Unit Kerja
                </label>

                <select name="id_unit">

                    <option value="0">
                        -- Semua Unit --
                    </option>

                    <?php foreach ($unit_list as $u): ?>

                        <option
                            value="<?= (int)$u['id_unit']; ?>"
                            <?= $id_unit ==
                                $u['id_unit']
                                ? 'selected'
                                : ''; ?>
                        >

                            <?= htmlspecialchars(
                                $u['nama_unit']
                            ); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Kategori
                </label>

                <select name="kategori">

                    <option value="">
                        -- Semua Kategori --
                    </option>

                    <option
                        value="SANGAT BAIK"
                        <?= $kategori ===
                            'SANGAT BAIK'
                            ? 'selected'
                            : ''; ?>
                    >
                        Sangat Baik
                    </option>

                    <option
                        value="BAIK"
                        <?= $kategori === 'BAIK'
                            ? 'selected'
                            : ''; ?>
                    >
                        Baik
                    </option>

                    <option
                        value="CUKUP"
                        <?= $kategori === 'CUKUP'
                            ? 'selected'
                            : ''; ?>
                    >
                        Cukup
                    </option>

                    <option
                        value="PERLU PERBAIKAN"
                        <?= $kategori ===
                            'PERLU PERBAIKAN'
                            ? 'selected'
                            : ''; ?>
                    >
                        Perlu Perbaikan
                    </option>

                </select>

            </div>

        </div>


        <div class="filter-actions">

            <button
                type="submit"
                class="btn btn-primary">
                🔎 Tampilkan
            </button>

            <a
                href="index.php"
                class="btn btn-secondary">
                Reset
            </a>


            <?php if (
                $boleh_hapus &&
                $id_periode > 0
            ): ?>

                <a
                    href="<?= htmlspecialchars(
                        $url_hapus_periode
                    ); ?>"
                    class="btn btn-danger"
                    onclick="
                        return confirm(
                            'PERINGATAN!\n\n' +
                            'Periode OPPE yang dipilih beserta SELURUH PENILAIAN dan DETAIL NILAINYA akan dihapus.\n\n' +
                            'Data yang sudah dihapus tidak dapat dikembalikan.\n\n' +
                            'Yakin ingin melanjutkan?'
                        );
                    "
                >
                    🗑️ Hapus Periode
                </a>

            <?php endif; ?>

        </div>

    </form>

</div>


<div class="two-column">


<div class="panel">

    <div class="panel-title">
        Distribusi Hasil OPPE
    </div>

    <?php

    $kategori_total =
        $sangat_baik +
        $baik +
        $cukup +
        $perlu_perbaikan;

    $persen_sangat = 0;
    $persen_baik = 0;
    $persen_cukup = 0;
    $persen_perlu = 0;

    if ($kategori_total > 0) {

        $persen_sangat =
            ($sangat_baik /
            $kategori_total) * 100;

        $persen_baik =
            ($baik /
            $kategori_total) * 100;

        $persen_cukup =
            ($cukup /
            $kategori_total) * 100;

        $persen_perlu =
            ($perlu_perbaikan /
            $kategori_total) * 100;

    }

    ?>


    <div class="category-row">

        <div class="category-header">

            <span>
                Sangat Baik
            </span>

            <strong>
                <?= $sangat_baik; ?>
                (<?= number_format(
                    $persen_sangat,
                    1
                ); ?>%)
            </strong>

        </div>

        <div class="category-bar">

            <div
                class="category-fill fill-sangat"
                style="width: <?= $persen_sangat; ?>%">
            </div>

        </div>

    </div>


    <div class="category-row">

        <div class="category-header">

            <span>
                Baik
            </span>

            <strong>
                <?= $baik; ?>
                (<?= number_format(
                    $persen_baik,
                    1
                ); ?>%)
            </strong>

        </div>

        <div class="category-bar">

            <div
                class="category-fill fill-baik"
                style="width: <?= $persen_baik; ?>%">
            </div>

        </div>

    </div>


    <div class="category-row">

        <div class="category-header">

            <span>
                Cukup
            </span>

            <strong>
                <?= $cukup; ?>
                (<?= number_format(
                    $persen_cukup,
                    1
                ); ?>%)
            </strong>

        </div>

        <div class="category-bar">

            <div
                class="category-fill fill-cukup"
                style="width: <?= $persen_cukup; ?>%">
            </div>

        </div>

    </div>


    <div class="category-row">

        <div class="category-header">

            <span>
                Perlu Perbaikan
            </span>

            <strong>
                <?= $perlu_perbaikan; ?>
                (<?= number_format(
                    $persen_perlu,
                    1
                ); ?>%)
            </strong>

        </div>

        <div class="category-bar">

            <div
                class="category-fill fill-perlu"
                style="width: <?= $persen_perlu; ?>%">
            </div>

        </div>

    </div>

</div>


<div class="panel">

    <div class="panel-title">
        Rekap 6 Komponen OPPE
    </div>

    <?php if (count($rekap_komponen) > 0): ?>

        <?php foreach ($rekap_komponen as $kom): ?>

            <div style="margin-bottom:15px;">

                <div
                    style="
                    display:flex;
                    justify-content:space-between;
                    font-size:13px;
                    "
                >

                    <span class="component-name">

                        <?= htmlspecialchars(
                            $kom['nama_komponen']
                        ); ?>

                    </span>

                    <strong>

                        <?= number_format(
                            $kom['rata_rata'],
                            2
                        ); ?>

                    </strong>

                </div>

                <div
                    style="
                    display:flex;
                    justify-content:space-between;
                    font-size:11px;
                    color:#777;
                    margin-top:3px;
                    "
                >

                    <span>

                        Bobot
                        <?= number_format(
                            $kom['bobot'],
                            0
                        ); ?>%

                    </span>

                    <span>

                        Kontribusi
                        <?= number_format(
                            $kom['kontribusi'],
                            2
                        ); ?>

                    </span>

                </div>

                <div
                    class="progress component-progress"
                >

                    <div
                        class="component-bar"
                        style="width: <?= min(
                            100,
                            ($kom['rata_rata'] / 4) * 100
                        ); ?>%">
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            Belum ada data komponen.
        </div>

    <?php endif; ?>

</div>

</div>


<div class="panel">

    <div class="panel-title">

        Rekap Penilaian OPPE

        <span
            style="
            font-size:12px;
            color:#777;
            font-weight:normal;
            "
        >
            —
            <?= htmlspecialchars(
                $nama_periode_terpilih
            ); ?>
        </span>

    </div>


    <?php if (count($data_penilaian) > 0): ?>

    <div class="table-responsive">

        <table>

            <thead>

                <tr>

                    <th width="40">
                        No
                    </th>

                    <th>
                        Nama
                    </th>

                    <th>
                        Nomor Induk
                    </th>

                    <th>
                        Profesi
                    </th>

                    <th>
                        Unit
                    </th>

                    <th>
                        Periode
                    </th>

                    <th>
                        Tanggal
                    </th>

                    <th>
                        Nilai
                    </th>

                    <th>
                        Kategori
                    </th>

                    <th>
                        Penilai
                    </th>

                    <th width="250">
                        Aksi
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php

            $no = 1;

            foreach ($data_penilaian as $row):

                $kat = strtoupper(
                    trim(
                        $row['kategori'] ?? ''
                    )
                );

                $badge = 'badge-draft';

                if ($kat === 'SANGAT BAIK') {

                    $badge = 'badge-sangat';

                } elseif ($kat === 'BAIK') {

                    $badge = 'badge-baik';

                } elseif ($kat === 'CUKUP') {

                    $badge = 'badge-cukup';

                } elseif ($kat === 'PERLU PERBAIKAN') {

                    $badge = 'badge-perlu';

                }

            ?>

                <tr>

                    <td class="text-center">
                        <?= $no++; ?>
                    </td>

                    <td>

                        <strong>

                            <?= htmlspecialchars(
                                $row['nama_lengkap']
                            ); ?>

                        </strong>

                    </td>

                    <td>

                        <?= htmlspecialchars(
                            $row['nomor_induk'] ?? '-'
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
                            $row['nama_periode'] ?? '-'
                        ); ?>

                    </td>

                    <td class="text-center">

                        <?php

                        if (
                            !empty(
                                $row['tanggal_penilaian']
                            )
                        ) {

                            echo date(
                                'd-m-Y',
                                strtotime(
                                    $row['tanggal_penilaian']
                                )
                            );

                        } else {

                            echo '-';

                        }

                        ?>

                    </td>

                    <td
                        class="text-center"
                        style="font-size:15px;"
                    >

                        <strong>

                            <?= number_format(
                                (float)$row['nilai_akhir'],
                                2
                            ); ?>

                        </strong>

                    </td>

                    <td class="text-center">

                        <span
                            class="badge <?= $badge; ?>"
                        >

                            <?= htmlspecialchars(
                                $row['kategori']
                                ?? 'BELUM DINILAI'
                            ); ?>

                        </span>

                    </td>

                    <td>

                        <?= htmlspecialchars(
                            $row['penilai'] ?? '-'
                        ); ?>

                    </td>

                    <td class="text-center">

                        <a
                            href="penilaian_detail.php?id=<?= (int)$row['id_penilaian']; ?>"
                            class="btn btn-info"
                        >
                            Detail
                        </a>

                        <a
                            href="penilaian_form.php?id=<?= (int)$row['id_penilaian']; ?>"
                            class="btn btn-warning"
                        >
                            Edit
                        </a>

                        <a
                            href="cetak.php?id=<?= (int)$row['id_penilaian']; ?>"
                            target="_blank"
                            class="btn btn-secondary"
                        >
                            Cetak
                        </a>

                        <?php if ($boleh_hapus): ?>

                            <a
                                href="penilaian_hapus.php?id=<?= (int)$row['id_penilaian']; ?>"
                                class="btn btn-danger"
                                onclick="
                                    return confirm(
                                        'Yakin ingin menghapus penilaian OPPE untuk <?= htmlspecialchars(
                                            addslashes($row['nama_lengkap']),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>?\n\n' +
                                        'Seluruh detail nilai indikator juga akan dihapus.'
                                    );
                                "
                            >
                                🗑️ Hapus
                            </a>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php else: ?>

        <div class="empty">

            <h3>
                Belum ada penilaian OPPE
            </h3>

            <p>
                Belum terdapat data sesuai filter yang dipilih.
            </p>

            <a
                href="<?= htmlspecialchars($url_penilaian); ?>"
                class="btn btn-success"
                <?php if ($id_periode <= 0): ?>
                    onclick="return pilihPeriode();"
                <?php endif; ?>
            >
                + Buat Penilaian
            </a>

        </div>

    <?php endif; ?>

</div>


<div
    style="
    text-align:center;
    color:#888;
    font-size:11px;
    padding:15px;
    "
>

    Sistem Informasi Tenaga Kesehatan Lainnya
    RSUD Subang

</div>

</div>


<script>

function pilihPeriode() {

    alert(
        "Silakan pilih Periode OPPE terlebih dahulu pada bagian Filter Dashboard OPPE."
    );

    return false;

}

</script>

</body>

</html>