<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$role = $_SESSION['role'] ?? 'ANGGOTA';


/* =====================================================
   FILTER
===================================================== */

$keyword = trim($_GET['keyword'] ?? '');

$id_profesi = (int) ($_GET['id_profesi'] ?? 0);

$id_unit = (int) ($_GET['id_unit'] ?? 0);

$id_status = (int) ($_GET['id_status'] ?? 0);


/* =====================================================
   MASTER PROFESI
===================================================== */

$profesi_result = mysqli_query(
    $conn,
    "SELECT
        id_profesi,
        nama_profesi
     FROM profesi
     WHERE status = 'AKTIF'
     ORDER BY nama_profesi ASC"
);


/* =====================================================
   MASTER UNIT KERJA
===================================================== */

$unit_result = mysqli_query(
    $conn,
    "SELECT
        id_unit,
        nama_unit
     FROM unit_kerja
     WHERE status = 'AKTIF'
     ORDER BY nama_unit ASC"
);


/* =====================================================
   MASTER STATUS KEPEGAWAIAN
===================================================== */

$status_result = mysqli_query(
    $conn,
    "SELECT
        id_status,
        nama_status
     FROM status_kepegawaian
     ORDER BY nama_status ASC"
);


/* =====================================================
   MEMBUAT FILTER QUERY
===================================================== */

$where = [];


/* PENCARIAN */

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


/* FILTER PROFESI */

if ($id_profesi > 0) {

    $where[] =
        "t.id_profesi = $id_profesi";

}


/* FILTER UNIT */

if ($id_unit > 0) {

    $where[] =
        "t.id_unit = $id_unit";

}


/* FILTER STATUS KEPEGAWAIAN */

if ($id_status > 0) {

    $where[] =
        "t.id_status_kepegawaian = $id_status";

}


/* =====================================================
   WHERE SQL
===================================================== */

$where_sql = '';

if (!empty($where)) {

    $where_sql =
        "WHERE " .
        implode(" AND ", $where);

}


/* =====================================================
   QUERY DATA TENAGA KESEHATAN
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


ORDER BY
    t.nama_lengkap ASC
";


$result = mysqli_query(
    $conn,
    $sql
);


if (!$result) {

    die(
        "Gagal mengambil data: " .
        mysqli_error($conn)
    );

}


$total_data = mysqli_num_rows($result);

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Data Tenaga Kesehatan</title>


<style>

/* =====================================================
   BODY
===================================================== */

body {

    font-family: Arial, sans-serif;

    background: #f4f6f9;

    margin: 0;

    padding: 25px;

}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    max-width: 1400px;

    margin: auto;

    background: white;

    padding: 25px;

    border-radius: 10px;

    box-shadow:
        0 2px 10px rgba(0,0,0,.08);

}


/* =====================================================
   HEADER
===================================================== */

.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;

    gap: 10px;

    flex-wrap: wrap;

}


h2 {

    margin: 0;

    color: #333;

}


.role-info {

    font-size: 13px;

    color: #666;

    margin-top: 5px;

}


/* =====================================================
   NAV BUTTON
===================================================== */

.nav-buttons {

    display: flex;

    gap: 8px;

    flex-wrap: wrap;

}


.btn {

    display: inline-block;

    padding: 9px 13px;

    border-radius: 6px;

    text-decoration: none;

    color: white;

    font-size: 13px;

    border: none;

    cursor: pointer;

}


/* DASHBOARD */

.btn-dashboard {

    background: #0d6efd;

}

.btn-dashboard:hover {

    background: #0b5ed7;

}


/* TAMBAH */

.btn-tambah {

    background: #198754;

}

.btn-tambah:hover {

    background: #157347;

}


/* CETAK */

.btn-cetak {

    background: #6f42c1;

}

.btn-cetak:hover {

    background: #59359a;

}


/* CARI */

.btn-cari {

    background: #0d6efd;

}

.btn-cari:hover {

    background: #0b5ed7;

}


/* RESET */

.btn-reset {

    background: #6c757d;

}

.btn-reset:hover {

    background: #5c636a;

}


/* DETAIL */

.btn-detail {

    background: #0d6efd;

}


/* EDIT */

.btn-edit {

    background: #ffc107;

    color: #212529;

}


/* =====================================================
   FILTER
===================================================== */

.filter-box {

    background: #f8f9fa;

    border: 1px solid #ddd;

    border-radius: 8px;

    padding: 15px;

    margin-bottom: 20px;

}


.filter-title {

    font-weight: bold;

    margin-bottom: 12px;

    color: #333;

}


.filter-form {

    display: grid;

    grid-template-columns:
        2fr 1fr 1fr 1fr auto auto;

    gap: 10px;

    align-items: end;

}


.form-group {

    display: flex;

    flex-direction: column;

}


.form-group label {

    font-size: 12px;

    margin-bottom: 5px;

    color: #555;

}


.form-group input,
.form-group select {

    padding: 9px;

    border: 1px solid #ccc;

    border-radius: 6px;

    font-size: 13px;

    background: white;

    box-sizing: border-box;

}


/* =====================================================
   INFO DATA
===================================================== */

.data-info {

    margin-bottom: 10px;

    font-size: 14px;

    color: #555;

}


.data-info strong {

    color: #0d6efd;

}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

    margin-top: 10px;

}


th,
td {

    border: 1px solid #ddd;

    padding: 9px;

    text-align: left;

    font-size: 13px;

}


th {

    background: #f1f3f5;

    white-space: nowrap;

}


tr:hover {

    background: #f8f9fa;

}


.aksi {

    white-space: nowrap;

}


/* =====================================================
   STATUS AKTIF
===================================================== */

.status-aktif {

    display: inline-block;

    padding: 4px 8px;

    border-radius: 12px;

    font-size: 11px;

    font-weight: bold;

    background: #d1e7dd;

    color: #0f5132;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 40px;

    color: #777;

    border: 1px solid #ddd;

    border-radius: 8px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

    .filter-form {

        grid-template-columns:
            1fr 1fr 1fr;

    }

}


@media (max-width: 700px) {

    body {

        padding: 10px;

    }


    .container {

        padding: 15px;

    }


    .filter-form {

        grid-template-columns:
            1fr;

    }


    .header {

        align-items: flex-start;

    }

}

</style>

</head>


<body>


<div class="container">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="header">


    <div>

        <h2>
            Data Tenaga Kesehatan
        </h2>


        <div class="role-info">

            Login sebagai:

            <strong>
                <?= htmlspecialchars($role); ?>
            </strong>

        </div>

    </div>


    <div class="nav-buttons">


        <!-- DASHBOARD -->

        <a
            href="../dashboard/index.php"
            class="btn btn-dashboard"
        >
            🏠 Dashboard
        </a>


        <!-- CETAK DATA -->

        <a
            href="cetak.php?keyword=<?= urlencode($keyword); ?>&id_profesi=<?= $id_profesi; ?>&id_unit=<?= $id_unit; ?>&id_status=<?= $id_status; ?>"
            target="_blank"
            class="btn btn-cetak"
        >
            🖨 Cetak Data
        </a>


        <!-- TAMBAH DATA -->

        <?php if (
            $role === 'ADMIN' ||
            $role === 'PENGURUS'
        ) { ?>

            <a
                href="tambah.php"
                class="btn btn-tambah"
            >
                + Tambah Tenaga Kesehatan
            </a>

        <?php } ?>


    </div>

</div>


<!-- =====================================================
     FILTER
===================================================== -->

<div class="filter-box">


    <div class="filter-title">

        🔎 Pencarian & Filter

    </div>


    <form
        method="GET"
        class="filter-form"
    >


        <!-- PENCARIAN -->

        <div class="form-group">

            <label>
                Nama / NIP / NIK
            </label>


            <input
                type="text"
                name="keyword"
                value="<?= htmlspecialchars($keyword); ?>"
                placeholder="Ketik nama, NIP atau NIK..."
            >

        </div>


        <!-- PROFESI -->

        <div class="form-group">

            <label>
                Profesi
            </label>


            <select name="id_profesi">


                <option value="0">

                    -- Semua Profesi --

                </option>


                <?php while (
                    $p = mysqli_fetch_assoc(
                        $profesi_result
                    )
                ) { ?>


                    <option
                        value="<?= $p['id_profesi']; ?>"

                        <?= (
                            $id_profesi ==
                            $p['id_profesi']
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >

                        <?= htmlspecialchars(
                            $p['nama_profesi']
                        ); ?>

                    </option>


                <?php } ?>


            </select>

        </div>


        <!-- UNIT -->

        <div class="form-group">

            <label>
                Unit Kerja
            </label>


            <select name="id_unit">


                <option value="0">

                    -- Semua Unit --

                </option>


                <?php while (
                    $u = mysqli_fetch_assoc(
                        $unit_result
                    )
                ) { ?>


                    <option
                        value="<?= $u['id_unit']; ?>"

                        <?= (
                            $id_unit ==
                            $u['id_unit']
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >

                        <?= htmlspecialchars(
                            $u['nama_unit']
                        ); ?>

                    </option>


                <?php } ?>


            </select>

        </div>


        <!-- STATUS KEPEGAWAIAN -->

        <div class="form-group">

            <label>
                Status Kepegawaian
            </label>


            <select name="id_status">


                <option value="0">

                    -- Semua Status --

                </option>


                <?php while (
                    $s = mysqli_fetch_assoc(
                        $status_result
                    )
                ) { ?>


                    <option
                        value="<?= $s['id_status']; ?>"

                        <?= (
                            $id_status ==
                            $s['id_status']
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >

                        <?= htmlspecialchars(
                            $s['nama_status']
                        ); ?>

                    </option>


                <?php } ?>


            </select>

        </div>


        <!-- CARI -->

        <button
            type="submit"
            class="btn btn-cari"
        >

            🔎 Cari

        </button>


        <!-- RESET -->

        <a
            href="index.php"
            class="btn btn-reset"
        >

            ↻ Reset

        </a>


    </form>

</div>


<!-- =====================================================
     INFO DATA
===================================================== -->

<div class="data-info">

    Menampilkan:

    <strong>
        <?= $total_data; ?>
    </strong>

    data tenaga kesehatan.

</div>


<!-- =====================================================
     TABEL
===================================================== -->

<?php if (
    $total_data > 0
) { ?>


<div class="table-wrapper">


<table>


<thead>

<tr>

    <th>No</th>

    <th>Nama</th>

    <th>Profesi</th>

    <th>Unit Kerja</th>

    <th>Jabatan</th>

    <th>Status Kepegawaian</th>

    <th>Status Aktif</th>


    <?php if (
        $role === 'ADMIN' ||
        $role === 'PENGURUS'
    ) { ?>

        <th>Aksi</th>

    <?php } ?>


</tr>

</thead>


<tbody>


<?php

$no = 1;


while (
    $row = mysqli_fetch_assoc(
        $result
    )
) {

?>


<tr>


    <!-- NO -->

    <td>

        <?= $no++; ?>

    </td>


    <!-- NAMA -->

    <td>


        <strong>

            <?= htmlspecialchars(
                $row['nama_lengkap']
            ); ?>

        </strong>


        <?php if (
            !empty($row['gelar_depan']) ||
            !empty($row['gelar_belakang'])
        ) { ?>


            <br>


            <small>


                <?php if (
                    !empty(
                        $row['gelar_depan']
                    )
                ) { ?>

                    <?= htmlspecialchars(
                        $row['gelar_depan']
                    ); ?>

                <?php } ?>


                <?php if (
                    !empty(
                        $row['gelar_belakang']
                    )
                ) { ?>

                    <?= htmlspecialchars(
                        $row['gelar_belakang']
                    ); ?>

                <?php } ?>


            </small>


        <?php } ?>


    </td>


    <!-- PROFESI -->

    <td>

        <?= htmlspecialchars(
            $row['nama_profesi'] ?? '-'
        ); ?>

    </td>


    <!-- UNIT -->

    <td>

        <?= htmlspecialchars(
            $row['nama_unit'] ?? '-'
        ); ?>

    </td>


    <!-- JABATAN -->

    <td>

        <?= htmlspecialchars(
            $row['nama_jabatan'] ?? '-'
        ); ?>

    </td>


    <!-- STATUS KEPEGAWAIAN -->

    <td>

        <?= htmlspecialchars(
            $row['nama_status'] ?? '-'
        ); ?>

    </td>


    <!-- STATUS AKTIF -->

    <td>


        <?php if (
            strtoupper(
                $row['status_aktif'] ?? ''
            ) === 'AKTIF'
        ) { ?>


            <span class="status-aktif">

                AKTIF

            </span>


        <?php } else { ?>


            <?= htmlspecialchars(
                $row['status_aktif'] ?? '-'
            ); ?>


        <?php } ?>


    </td>


    <!-- AKSI -->

    <?php if (
        $role === 'ADMIN' ||
        $role === 'PENGURUS'
    ) { ?>


        <td class="aksi">


            <!-- DETAIL -->

            <a
                href="detail.php?id=<?= $row['id_nakes']; ?>"
                class="btn btn-detail"
            >

                👁 Detail

            </a>


            <!-- EDIT -->

            <a
                href="edit.php?id=<?= $row['id_nakes']; ?>"
                class="btn btn-edit"
            >

                ✏ Edit

            </a>


        </td>


    <?php } ?>


</tr>


<?php

}

?>


</tbody>


</table>


</div>


<?php } else { ?>


<!-- =====================================================
     DATA KOSONG
===================================================== -->

<div class="empty">


    <strong>
        Data tidak ditemukan.
    </strong>


    <br><br>


    Silakan ubah kata kunci atau filter pencarian.


</div>


<?php } ?>


</div>


</body>

</html>