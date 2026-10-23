<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/* =========================================================
   ID PERIODE
   ========================================================= */

$id_periode = (int)($_GET['id_periode'] ?? 0);

if ($id_periode <= 0) {
    die("ID periode tidak valid.");
}


/* =========================================================
   DATA PERIODE
   ========================================================= */

$stmt = mysqli_prepare($conn, "
    SELECT
        id_periode,
        nama_periode,
        tanggal_mulai,
        tanggal_selesai,
        status
    FROM oppe_periode
    WHERE id_periode = ?
    LIMIT 1
");

if (!$stmt) {
    die(
        "Gagal menyiapkan data periode: " .
        mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_periode
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Periode OPPE tidak ditemukan.");
}

$periode = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   PROFESI
   ========================================================= */

$query_profesi = mysqli_query($conn, "
    SELECT
        id_profesi,
        kode_profesi,
        nama_profesi
    FROM profesi
    ORDER BY nama_profesi ASC
");

if (!$query_profesi) {
    die(
        "Gagal mengambil data profesi: " .
        mysqli_error($conn)
    );
}


/* =========================================================
   UNIT
   ========================================================= */

$query_unit = mysqli_query($conn, "
    SELECT
        id_unit,
        nama_unit
    FROM unit_kerja
    ORDER BY nama_unit ASC
");

if (!$query_unit) {
    die(
        "Gagal mengambil data unit kerja: " .
        mysqli_error($conn)
    );
}


/* =========================================================
   DATA TENAGA KESEHATAN
   ========================================================= */

$query_nakes = mysqli_query($conn, "

    SELECT
        t.id_nakes,
        t.nomor_induk,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi,
        u.nama_unit

    FROM tenaga_kesehatan t

    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi

    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit

    WHERE t.status_aktif = 'AKTIF'

    ORDER BY
        t.nama_lengkap ASC

");

if (!$query_nakes) {
    die(
        "Gagal mengambil data tenaga kesehatan: " .
        mysqli_error($conn)
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Tambah Penilaian OPPE
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
}

.header {
    background: #0dcaf0;
    color: #212529;
    padding: 20px 30px;
}

.header h1 {
    margin: 0;
}

.container {
    width: 95%;
    max-width: 1000px;
    margin: 30px auto;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.info {
    background: #e7f1ff;
    border-left: 4px solid #0d6efd;
    padding: 15px;
    margin-bottom: 20px;
    line-height: 1.7;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}

select,
input {
    width: 100%;
    padding: 11px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary {
    background: #0d6efd;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.nakes-box {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px;
    max-height: 400px;
    overflow-y: auto;
}

.nakes-item {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

.nakes-item:last-child {
    border-bottom: none;
}

.nakes-item label {
    font-weight: normal;
    margin: 0;
    cursor: pointer;
}

.nakes-item input {
    width: auto;
    margin-right: 8px;
}

</style>

</head>

<body>


<div class="header">

    <h1>
        ➕ Tambah Penilaian OPPE
    </h1>

</div>


<div class="container">

<div class="card">


<div class="info">

    <strong>Periode:</strong>

    <?= htmlspecialchars(
        $periode['nama_periode'] ?? '-'
    ); ?>

    <br>

    <strong>Periode Penilaian:</strong>

    <?php

    if (!empty($periode['tanggal_mulai'])) {

        echo date(
            'd-m-Y',
            strtotime(
                $periode['tanggal_mulai']
            )
        );

    } else {

        echo '-';

    }

    ?>

    s/d

    <?php

    if (!empty($periode['tanggal_selesai'])) {

        echo date(
            'd-m-Y',
            strtotime(
                $periode['tanggal_selesai']
            )
        );

    } else {

        echo '-';

    }

    ?>

</div>


<form
    action="penilaian_simpan.php"
    method="POST"
>


    <input
        type="hidden"
        name="id_periode"
        value="<?= $id_periode; ?>"
    >


    <div class="form-group">

        <label>
            Tanggal Penilaian *
        </label>

        <input
            type="date"
            name="tanggal_penilaian"
            value="<?= date('Y-m-d'); ?>"
            required
        >

    </div>


    <div class="form-group">

        <label>
            Nama Penilai *
        </label>

        <input
            type="text"
            name="penilai"
            placeholder="Nama pejabat/atasan yang melakukan penilaian"
            required
        >

    </div>


    <div class="form-group">

        <label>
            Pilih Tenaga Kesehatan *
        </label>


        <div class="nakes-box">


        <?php if (
            mysqli_num_rows($query_nakes) > 0
        ): ?>


            <?php while (
                $nakes =
                mysqli_fetch_assoc(
                    $query_nakes
                )
            ): ?>


                <?php

                $nama = '';

                if (
                    !empty(
                        $nakes['gelar_depan']
                    )
                ) {

                    $nama .=
                        $nakes['gelar_depan']
                        . ' ';

                }

                $nama .=
                    $nakes['nama_lengkap'];

                if (
                    !empty(
                        $nakes['gelar_belakang']
                    )
                ) {

                    $nama .=
                        ', ' .
                        $nakes['gelar_belakang'];

                }

                ?>


                <div class="nakes-item">

                    <label>

                        <input
                            type="radio"
                            name="id_nakes"
                            value="<?= (int)$nakes['id_nakes']; ?>"
                            required
                        >

                        <strong>
                            <?= htmlspecialchars(
                                $nama
                            ); ?>
                        </strong>

                        <br>

                        <small>

                            NIK/ID:
                            <?= htmlspecialchars(
                                $nakes['nomor_induk']
                                ?? '-'
                            ); ?>

                            |

                            Profesi:
                            <?= htmlspecialchars(
                                $nakes['nama_profesi']
                                ?? '-'
                            ); ?>

                            |

                            Unit:
                            <?= htmlspecialchars(
                                $nakes['nama_unit']
                                ?? '-'
                            ); ?>

                        </small>

                    </label>

                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p>
                Belum ada tenaga kesehatan aktif.
            </p>


        <?php endif; ?>


        </div>

    </div>


    <div class="actions">

        <button
            type="submit"
            class="btn btn-primary"
        >

            ➡️ Lanjut Penilaian

        </button>


        <a
            href="index.php?id_periode=<?= $id_periode; ?>"
            class="btn btn-secondary"
        >

            ↩ Kembali

        </a>

    </div>


</form>


</div>

</div>

</body>

</html>