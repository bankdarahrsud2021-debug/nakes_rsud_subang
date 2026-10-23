<?php

require_once "../config/auth.php";
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| VALIDASI REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: index.php");
    exit;
}

$id_periode = (int)($_GET['id_periode'] ?? 0);

if ($id_periode <= 0) {
    die("ID periode tidak valid.");
}


/*
|--------------------------------------------------------------------------
| CEK PERIODE
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($conn, "
    SELECT
        id_periode,
        nama_periode,
        tanggal_mulai,
        tanggal_selesai
    FROM oppe_periode
    WHERE id_periode = ?
    LIMIT 1
");

if (!$stmt) {
    die(
        "Gagal menyiapkan pemeriksaan periode: " .
        htmlspecialchars(mysqli_error($conn))
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

    mysqli_stmt_close($stmt);

    die("Periode OPPE tidak ditemukan.");
}

$periode = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| KONFIRMASI HAPUS
|--------------------------------------------------------------------------
*/

if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {

    $nama_periode = htmlspecialchars(
        $periode['nama_periode'],
        ENT_QUOTES,
        'UTF-8'
    );

    $tanggal_mulai = !empty($periode['tanggal_mulai'])
        ? date('d-m-Y', strtotime($periode['tanggal_mulai']))
        : '-';

    $tanggal_selesai = !empty($periode['tanggal_selesai'])
        ? date('d-m-Y', strtotime($periode['tanggal_selesai']))
        : '-';

    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Konfirmasi Hapus Periode OPPE</title>

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, Helvetica, sans-serif;
                background: #f4f6f9;
            }

            .container {
                width: 95%;
                max-width: 650px;
                margin: 60px auto;
            }

            .card {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }

            .warning {
                background: #fff3cd;
                border-left: 5px solid #ffc107;
                padding: 15px;
                margin-bottom: 20px;
                line-height: 1.6;
            }

            h2 {
                margin-top: 0;
                color: #dc3545;
            }

            .info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
                margin-bottom: 20px;
                line-height: 1.8;
            }

            .actions {
                display: flex;
                gap: 10px;
                margin-top: 25px;
            }

            .btn {
                padding: 11px 18px;
                border: none;
                border-radius: 6px;
                text-decoration: none;
                cursor: pointer;
                font-weight: bold;
                display: inline-block;
            }

            .btn-danger {
                background: #dc3545;
                color: white;
            }

            .btn-secondary {
                background: #6c757d;
                color: white;
            }
        </style>
    </head>

    <body>

    <div class="container">

        <div class="card">

            <h2>⚠️ Hapus Periode OPPE</h2>

            <div class="warning">
                <strong>Perhatian!</strong><br>
                Menghapus periode OPPE akan menghapus seluruh
                penilaian yang berada di dalam periode tersebut,
                termasuk detail nilai indikator.
                <br><br>
                <strong>Data yang sudah dihapus tidak dapat dikembalikan.</strong>
            </div>

            <div class="info">

                <strong>Nama Periode:</strong><br>
                <?= $nama_periode; ?>

                <br><br>

                <strong>Periode:</strong><br>
                <?= $tanggal_mulai; ?>
                s/d
                <?= $tanggal_selesai; ?>

            </div>

            <div class="actions">

                <a
                    href="periode_hapus.php?id_periode=<?= $id_periode; ?>&confirm=yes"
                    class="btn btn-danger"
                    onclick="return confirm('Yakin ingin menghapus periode OPPE ini beserta seluruh penilaiannya?');"
                >
                    🗑️ Ya, Hapus Periode
                </a>

                <a
                    href="index.php?id_periode=<?= $id_periode; ?>"
                    class="btn btn-secondary"
                >
                    ↩ Batal
                </a>

            </div>

        </div>

    </div>

    </body>
    </html>

    <?php

    exit;
}


/*
|--------------------------------------------------------------------------
| MULAI TRANSAKSI
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conn);

try {

    /*
    |--------------------------------------------------------------------------
    | AMBIL SEMUA ID PENILAIAN DALAM PERIODE
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare($conn, "
        SELECT id_penilaian
        FROM oppe_penilaian
        WHERE id_periode = ?
    ");

    if (!$stmt) {
        throw new Exception(
            "Gagal mengambil data penilaian: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_periode
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Gagal mengambil data penilaian: " .
            mysqli_stmt_error($stmt)
        );
    }

    $result_penilaian = mysqli_stmt_get_result($stmt);

    $id_penilaian_list = [];

    while ($row = mysqli_fetch_assoc($result_penilaian)) {
        $id_penilaian_list[] = (int)$row['id_penilaian'];
    }

    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | HAPUS DETAIL PENILAIAN
    |--------------------------------------------------------------------------
    */

    if (count($id_penilaian_list) > 0) {

        $stmt = mysqli_prepare($conn, "
            DELETE FROM oppe_detail
            WHERE id_penilaian = ?
        ");

        if (!$stmt) {
            throw new Exception(
                "Gagal menyiapkan penghapusan detail: " .
                mysqli_error($conn)
            );
        }

        foreach ($id_penilaian_list as $id_penilaian) {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id_penilaian
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(
                    "Gagal menghapus detail penilaian: " .
                    mysqli_stmt_error($stmt)
                );
            }
        }

        mysqli_stmt_close($stmt);
    }


    /*
    |--------------------------------------------------------------------------
    | HAPUS PENILAIAN
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare($conn, "
        DELETE FROM oppe_penilaian
        WHERE id_periode = ?
    ");

    if (!$stmt) {
        throw new Exception(
            "Gagal menyiapkan penghapusan penilaian: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_periode
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Gagal menghapus penilaian: " .
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | HAPUS PERIODE
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare($conn, "
        DELETE FROM oppe_periode
        WHERE id_periode = ?
    ");

    if (!$stmt) {
        throw new Exception(
            "Gagal menyiapkan penghapusan periode: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_periode
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            "Gagal menghapus periode: " .
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    mysqli_commit($conn);


    /*
    |--------------------------------------------------------------------------
    | KEMBALI KE DASHBOARD
    |--------------------------------------------------------------------------
    */

    header("Location: index.php?hapus_periode=success");
    exit;


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    mysqli_rollback($conn);

    die(
        "<h3>Gagal menghapus periode OPPE</h3>" .
        "<p>" .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        ) .
        "</p>" .
        "<p><a href='index.php'>Kembali ke Dashboard OPPE</a></p>"
    );
}