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

$id_penilaian = (int)($_GET['id'] ?? 0);

if ($id_penilaian <= 0) {
    die("ID penilaian tidak valid.");
}


/*
|--------------------------------------------------------------------------
| CEK DATA PENILAIAN
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare($conn, "
    SELECT
        p.id_penilaian,
        p.id_periode,
        p.tanggal_penilaian,
        p.penilai,
        p.total_nilai,
        p.nilai_akhir,
        p.kategori,
        p.status,

        op.nama_periode,

        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,

        pr.nama_profesi,

        u.nama_unit

    FROM oppe_penilaian p

    LEFT JOIN oppe_periode op
        ON p.id_periode = op.id_periode

    LEFT JOIN tenaga_kesehatan t
        ON p.id_nakes = t.id_nakes

    LEFT JOIN profesi pr
        ON t.id_profesi = pr.id_profesi

    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit

    WHERE p.id_penilaian = ?

    LIMIT 1
");


if (!$stmt) {

    die(
        "Gagal menyiapkan pemeriksaan penilaian: " .
        htmlspecialchars(mysqli_error($conn))
    );

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_penilaian
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


if (!$result || mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    die("Data penilaian OPPE tidak ditemukan.");

}


$penilaian = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| SUSUN NAMA NAKES
|--------------------------------------------------------------------------
*/

$nama_nakes = '';

if (!empty($penilaian['gelar_depan'])) {

    $nama_nakes .=
        $penilaian['gelar_depan'] . ' ';

}


$nama_nakes .=
    $penilaian['nama_lengkap'];


if (!empty($penilaian['gelar_belakang'])) {

    $nama_nakes .=
        ', ' . $penilaian['gelar_belakang'];

}


/*
|--------------------------------------------------------------------------
| KONFIRMASI HAPUS
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['confirm']) ||
    $_GET['confirm'] !== 'yes'
) {

    ?>

    <!DOCTYPE html>
    <html lang="id">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Konfirmasi Hapus Penilaian OPPE</title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {

                margin: 0;

                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;

                background: #f4f6f9;

            }

            .container {

                width: 95%;

                max-width: 700px;

                margin: 60px auto;

            }

            .card {

                background: white;

                padding: 30px;

                border-radius: 8px;

                box-shadow:
                    0 2px 8px
                    rgba(0,0,0,0.08);

            }

            .warning {

                background: #fff3cd;

                border-left:
                    5px solid #ffc107;

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

                flex-wrap: wrap;

            }

            .btn {

                padding:
                    11px 18px;

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

            <h2>
                ⚠️ Hapus Penilaian OPPE
            </h2>


            <div class="warning">

                <strong>Perhatian!</strong><br>

                Penilaian OPPE ini akan dihapus
                beserta seluruh detail nilai
                indikatornya.

                <br><br>

                <strong>
                    Data yang sudah dihapus
                    tidak dapat dikembalikan.
                </strong>

            </div>


            <div class="info">

                <strong>
                    Tenaga Kesehatan:
                </strong>
                <br>

                <?= htmlspecialchars(
                    $nama_nakes,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>


                <br><br>


                <strong>
                    Profesi:
                </strong>
                <br>

                <?= htmlspecialchars(
                    $penilaian['nama_profesi'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>


                <br><br>


                <strong>
                    Unit:
                </strong>
                <br>

                <?= htmlspecialchars(
                    $penilaian['nama_unit'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>


                <br><br>


                <strong>
                    Periode:
                </strong>
                <br>

                <?= htmlspecialchars(
                    $penilaian['nama_periode'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>


                <br><br>


                <strong>
                    Tanggal Penilaian:
                </strong>
                <br>

                <?php

                if (
                    !empty(
                        $penilaian['tanggal_penilaian']
                    )
                ) {

                    echo date(
                        'd-m-Y',
                        strtotime(
                            $penilaian[
                                'tanggal_penilaian'
                            ]
                        )
                    );

                } else {

                    echo '-';

                }

                ?>


                <br><br>


                <strong>
                    Penilai:
                </strong>
                <br>

                <?= htmlspecialchars(
                    $penilaian['penilai'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>


                <br><br>


                <strong>
                    Nilai Akhir:
                </strong>
                <br>

                <?= htmlspecialchars(
                    $penilaian['nilai_akhir'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>


                <?php

                if (
                    !empty(
                        $penilaian['kategori']
                    )
                ):

                ?>

                    <br><br>

                    <strong>
                        Kategori:
                    </strong>
                    <br>

                    <?= htmlspecialchars(
                        $penilaian['kategori'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                <?php endif; ?>

            </div>


            <div class="actions">


                <a
                    href="penilaian_hapus.php?id=<?= $id_penilaian; ?>&confirm=yes"
                    class="btn btn-danger"
                    onclick="return confirm('Yakin ingin menghapus penilaian OPPE ini beserta seluruh detail nilainya?');"
                >
                    🗑️ Ya, Hapus Penilaian
                </a>


                <a
                    href="index.php?id_periode=<?= (int)$penilaian['id_periode']; ?>"
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
    | HAPUS DETAIL NILAI
    |--------------------------------------------------------------------------
    */

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


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_penilaian
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        throw new Exception(
            "Gagal menghapus detail nilai: " .
            mysqli_stmt_error($stmt)
        );

    }


    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | HAPUS HEADER PENILAIAN
    |--------------------------------------------------------------------------
    */

    $stmt = mysqli_prepare($conn, "
        DELETE FROM oppe_penilaian
        WHERE id_penilaian = ?
        LIMIT 1
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
        $id_penilaian
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        throw new Exception(
            "Gagal menghapus penilaian: " .
            mysqli_stmt_error($stmt)
        );

    }


    if (
        mysqli_stmt_affected_rows($stmt) <= 0
    ) {

        throw new Exception(
            "Data penilaian tidak ditemukan atau sudah dihapus."
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

    header(
        "Location: index.php?id_periode=" .
        (int)$penilaian['id_periode'] .
        "&hapus_penilaian=success"
    );

    exit;


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    mysqli_rollback($conn);


    die(

        "<h3>Gagal menghapus penilaian OPPE</h3>" .

        "<p>" .

        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        ) .

        "</p>" .

        "<p>" .

        "<a href='index.php?id_periode=" .
        (int)$penilaian['id_periode'] .
        "'>" .

        "Kembali ke Dashboard OPPE" .

        "</a>" .

        "</p>"

    );

}