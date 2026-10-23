<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| VALIDASI ID PENILAIAN
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID penilaian tidak valid.");
}

$id_penilaian = (int) $_GET['id'];

if ($id_penilaian <= 0) {
    die("ID penilaian tidak valid.");
}


/*
|--------------------------------------------------------------------------
| FUNGSI ESCAPE
|--------------------------------------------------------------------------
*/

function e($text)
{
    return htmlspecialchars(
        (string)$text,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PENILAIAN
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id_penilaian,
        p.id_nakes,
        p.id_periode,
        p.tanggal_penilaian,
        p.penilai,
        p.catatan,
        p.tindak_lanjut,

        o.nama_periode,
        o.tanggal_mulai,
        o.tanggal_selesai,

        n.nama_lengkap,
        n.nomor_induk,
        n.nip,
        n.id_profesi,
        n.id_unit,
        n.id_jabatan,
        n.id_status_kepegawaian,

        pr.nama_profesi,
        u.nama_unit,

        j.nama_jabatan,
        sk.nama_status

    FROM oppe_penilaian p

    LEFT JOIN oppe_periode o
        ON p.id_periode = o.id_periode

    LEFT JOIN tenaga_kesehatan n
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
    die(
        "Gagal menyiapkan query penilaian: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_penilaian
);


if (!mysqli_stmt_execute($stmt)) {
    die(
        "Gagal mengambil data penilaian: "
        . mysqli_stmt_error($stmt)
    );
}


$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$data) {
    die("Data penilaian tidak ditemukan.");
}


$id_profesi = (int)$data['id_profesi'];


/*
|--------------------------------------------------------------------------
| AMBIL KOMPONEN OPPE
|--------------------------------------------------------------------------
*/

$komponen = [];

$sql_komponen = "
    SELECT
        id_komponen,
        nama_komponen,
        bobot,
        urutan
    FROM oppe_komponen
    WHERE status_aktif = 1
    ORDER BY
        urutan ASC,
        id_komponen ASC
";


$result_komponen = mysqli_query(
    $conn,
    $sql_komponen
);


if (!$result_komponen) {
    die(
        "Gagal mengambil komponen OPPE: "
        . mysqli_error($conn)
    );
}


$total_bobot = 0;


while ($row = mysqli_fetch_assoc($result_komponen)) {

    $id_komponen = (int)$row['id_komponen'];

    $komponen[$id_komponen] = [

        'id_komponen' => $id_komponen,

        'nama_komponen' => $row['nama_komponen'],

        'bobot' => (float)$row['bobot'],

        'urutan' => (int)$row['urutan']

    ];

    $total_bobot += (float)$row['bobot'];
}


/*
|--------------------------------------------------------------------------
| VALIDASI TOTAL BOBOT
|--------------------------------------------------------------------------
*/

if (count($komponen) === 0) {

    die(
        "Belum terdapat komponen OPPE yang aktif."
    );
}


if (abs($total_bobot - 100) > 0.01) {

    die(
        "Total bobot komponen OPPE harus 100%. "
        . "Total saat ini: "
        . number_format($total_bobot, 2)
        . "%"
    );
}


/*
|--------------------------------------------------------------------------
| AMBIL INDIKATOR
|--------------------------------------------------------------------------
*/

$indikator = [];


$sql_indikator = "
    SELECT
        i.id_indikator,
        i.id_profesi,
        i.id_komponen,
        i.komponen,
        i.nama_indikator,
        i.deskripsi,
        i.bobot,
        i.nilai_maksimal,

        c.nama_komponen,
        c.bobot AS bobot_komponen,
        c.urutan

    FROM oppe_indikator i

    INNER JOIN oppe_komponen c
        ON i.id_komponen = c.id_komponen

    WHERE i.status_aktif = 1

      AND c.status_aktif = 1

      AND (
            i.id_profesi IS NULL
            OR i.id_profesi = 0
            OR i.id_profesi = ?
      )

    ORDER BY
        c.urutan ASC,
        i.id_indikator ASC
";


$stmt = mysqli_prepare(
    $conn,
    $sql_indikator
);


if (!$stmt) {

    die(
        "Gagal menyiapkan query indikator: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_profesi
);


if (!mysqli_stmt_execute($stmt)) {

    die(
        "Gagal mengambil indikator: "
        . mysqli_stmt_error($stmt)
    );
}


$result_indikator = mysqli_stmt_get_result($stmt);


while ($row = mysqli_fetch_assoc($result_indikator)) {

    $id_indikator =
        (int)$row['id_indikator'];

    $indikator[$id_indikator] = $row;
}


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| VALIDASI INDIKATOR
|--------------------------------------------------------------------------
*/

if (count($indikator) === 0) {

    die(
        "Belum terdapat indikator OPPE aktif "
        . "untuk profesi tenaga kesehatan ini."
    );
}


/*
|--------------------------------------------------------------------------
| GROUPING INDIKATOR
|--------------------------------------------------------------------------
*/

$indikator_group = [];


foreach ($indikator as $item) {

    $id_komponen =
        (int)$item['id_komponen'];


    if (!isset($indikator_group[$id_komponen])) {

        $indikator_group[$id_komponen] = [];
    }


    $indikator_group[$id_komponen][] =
        $item;
}


/*
|--------------------------------------------------------------------------
| AMBIL NILAI YANG SUDAH ADA
|--------------------------------------------------------------------------
*/

$nilai_lama = [];


$sql_detail = "
    SELECT
        id_indikator,
        nilai,
        catatan
    FROM oppe_detail
    WHERE id_penilaian = ?
";


$stmt = mysqli_prepare(
    $conn,
    $sql_detail
);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_penilaian
    );


    mysqli_stmt_execute($stmt);


    $result_detail =
        mysqli_stmt_get_result($stmt);


    while ($row = mysqli_fetch_assoc($result_detail)) {

        $nilai_lama[
            (int)$row['id_indikator']
        ] = [

            'nilai' => $row['nilai'],

            'catatan' => $row['catatan']

        ];
    }


    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| NOMOR URUT
|--------------------------------------------------------------------------
*/

$nomor = 1;

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Form Penilaian OPPE
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            padding: 20px;

            background: #f4f6f9;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #222;
        }


        .container {

            max-width: 1200px;

            margin: 0 auto;
        }


        .card {

            background: #fff;

            border-radius: 10px;

            padding: 25px;

            margin-bottom: 20px;

            box-shadow:
                0 2px 8px
                rgba(0,0,0,.08);
        }


        h1 {

            margin-top: 0;

            margin-bottom: 8px;

            font-size: 25px;
        }


        h2 {

            margin-top: 0;

            font-size: 19px;
        }


        .subtitle {

            color: #666;

            margin-bottom: 22px;
        }


        /*
        |--------------------------------------------------------------------------
        | IDENTITAS
        |--------------------------------------------------------------------------
        */

        .identity {

            display: grid;

            grid-template-columns:
                180px 1fr 180px 1fr;

            gap: 10px 15px;
        }


        .identity-label {

            font-weight: bold;
        }


        /*
        |--------------------------------------------------------------------------
        | PETUNJUK
        |--------------------------------------------------------------------------
        */

        .scale {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 10px;
        }


        .scale-item {

            padding: 14px;

            border: 1px solid #ddd;

            border-radius: 8px;

            background: #fafafa;
        }


        .scale-item strong {

            display: block;

            margin-bottom: 6px;
        }


        /*
        |--------------------------------------------------------------------------
        | KOMPONEN
        |--------------------------------------------------------------------------
        */

        .component {

            margin-bottom: 25px;

            border: 1px solid #d9dee5;

            border-radius: 8px;

            overflow: hidden;
        }


        .component-header {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

            padding: 15px 17px;

            background: #eef2f7;
        }


        .component-title {

            font-weight: bold;

            font-size: 17px;
        }


        .component-weight {

            font-weight: bold;

            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        table {

            width: 100%;

            border-collapse:
                collapse;
        }


        th,
        td {

            border: 1px solid #d9dee5;

            padding: 10px;

            vertical-align: top;
        }


        th {

            background: #f7f8fa;

            text-align: left;
        }


        .no {

            width: 50px;

            text-align: center;
        }


        .score {

            width: 160px;
        }


        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        select,
        input[type="date"],
        input[type="text"],
        textarea {

            width: 100%;

            padding: 9px;

            border: 1px solid #cfd5dc;

            border-radius: 6px;

            font-size: 14px;

            font-family: inherit;
        }


        textarea {

            min-height: 80px;

            resize: vertical;
        }


        .form-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 20px;
        }


        .form-group {

            margin-bottom: 18px;
        }


        .form-group label {

            display: block;

            font-weight: bold;

            margin-bottom: 7px;
        }


        .required {

            color: red;
        }


        .description {

            color: #666;

            font-size: 13px;

            line-height: 1.5;
        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .buttons {

            display: flex;

            gap: 10px;

            margin-top: 20px;
        }


        .btn {

            display: inline-block;

            padding: 11px 18px;

            border: none;

            border-radius: 7px;

            text-decoration: none;

            cursor: pointer;

            font-size: 14px;
        }


        .btn-primary {

            background: #198754;

            color: #fff;
        }


        .btn-secondary {

            background: #6c757d;

            color: #fff;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .identity {

                grid-template-columns:
                    160px 1fr;
            }


            .scale {

                grid-template-columns:
                    1fr 1fr;
            }


            .form-row {

                grid-template-columns:
                    1fr;
            }
        }


        @media (max-width: 600px) {

            body {

                padding: 10px;
            }


            .card {

                padding: 15px;
            }


            .identity {

                grid-template-columns:
                    1fr;
            }


            .scale {

                grid-template-columns:
                    1fr;
            }


            .component-header {

                align-items: flex-start;

                flex-direction: column;

                gap: 5px;
            }


            table {

                font-size: 13px;
            }


            th,
            td {

                padding: 7px;
            }
        }

    </style>

</head>


<body>


<div class="container">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="card">

        <h1>
            Form Penilaian OPPE
        </h1>

        <div class="subtitle">

            Objective Professional Practice Evaluation
            Tenaga Kesehatan Lainnya

        </div>


        <!-- =================================================
             IDENTITAS
        ================================================== -->

        <div class="identity">

            <div class="identity-label">
                Nama
            </div>

            <div>
                <?= e($data['nama_lengkap']) ?>
            </div>


            <div class="identity-label">
                Nomor Induk
            </div>

            <div>
                <?= e($data['nomor_induk']) ?>
            </div>


            <div class="identity-label">
                NIP
            </div>

            <div>
                <?= e($data['nip']) ?>
            </div>


            <div class="identity-label">
                Profesi
            </div>

            <div>
                <?= e($data['nama_profesi']) ?>
            </div>


            <div class="identity-label">
                Unit Kerja
            </div>

            <div>
                <?= e($data['nama_unit']) ?>
            </div>


            <div class="identity-label">
                Jabatan
            </div>

            <div>
                <?= e($data['nama_jabatan']) ?>
            </div>


            <div class="identity-label">
                Status Kepegawaian
            </div>

            <div>
                <?= e($data['nama_status']) ?>
            </div>


            <div class="identity-label">
                Periode OPPE
            </div>

            <div>
                <?= e($data['nama_periode']) ?>
            </div>

        </div>

    </div>



    <!-- =====================================================
         PETUNJUK PENILAIAN
    ====================================================== -->

    <div class="card">

        <h2>
            Petunjuk Penilaian
        </h2>


        <p>
            Berikan nilai berdasarkan hasil pengamatan,
            bukti pelaksanaan tugas, dokumentasi pelayanan,
            serta perilaku profesional tenaga kesehatan
            selama periode penilaian.
        </p>


        <div class="scale">


            <div class="scale-item">

                <strong>
                    4 — Sangat Baik
                </strong>

                Melampaui standar yang ditetapkan.

            </div>


            <div class="scale-item">

                <strong>
                    3 — Baik
                </strong>

                Memenuhi standar yang ditetapkan.

            </div>


            <div class="scale-item">

                <strong>
                    2 — Cukup
                </strong>

                Memenuhi sebagian standar
                dan masih memerlukan perbaikan.

            </div>


            <div class="scale-item">

                <strong>
                    1 — Perlu Perbaikan
                </strong>

                Belum memenuhi standar
                yang ditetapkan.

            </div>


        </div>

    </div>



    <!-- =====================================================
         FORM PENILAIAN
    ====================================================== -->

    <form
        method="POST"
        action="penilaian_simpan_detail.php"
        id="formPenilaian"
    >


        <input
            type="hidden"
            name="id_penilaian"
            value="<?= $id_penilaian ?>"
        >



        <!-- =================================================
             INDIKATOR
        ================================================== -->

        <?php foreach ($komponen as $id_komponen => $c): ?>


            <?php

            if (
                !isset(
                    $indikator_group[$id_komponen]
                )
            ) {
                continue;
            }

            ?>


            <div class="card component">


                <!-- =========================================
                     HEADER KOMPONEN
                ========================================== -->

                <div class="component-header">


                    <div class="component-title">

                        <?= e(
                            $c['nama_komponen']
                        ) ?>

                    </div>


                    <div class="component-weight">

                        Bobot:

                        <?= number_format(
                            $c['bobot'],
                            0
                        ) ?>%

                    </div>


                </div>



                <!-- =========================================
                     TABEL INDIKATOR
                ========================================== -->

                <table>


                    <thead>

                    <tr>

                        <th class="no">
                            No
                        </th>

                        <th>
                            Indikator Penilaian
                        </th>

                        <th>
                            Deskripsi
                        </th>

                        <th class="score">
                            Nilai
                            <span class="required">
                                *
                            </span>
                        </th>

                        <th>
                            Catatan
                        </th>

                    </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $indikator_group[$id_komponen]
                        as $item
                    ): ?>


                        <?php

                        $id_indikator =
                            (int)$item[
                                'id_indikator'
                            ];


                        $nilai = '';

                        $catatan = '';


                        if (
                            isset(
                                $nilai_lama[
                                    $id_indikator
                                ]
                            )
                        ) {

                            $nilai =
                                $nilai_lama[
                                    $id_indikator
                                ]['nilai'];


                            $catatan =
                                $nilai_lama[
                                    $id_indikator
                                ]['catatan'];
                        }

                        ?>


                        <tr>


                            <!-- NO -->

                            <td class="no">

                                <?= $nomor++ ?>

                            </td>



                            <!-- INDIKATOR -->

                            <td>

                                <strong>

                                    <?= e(
                                        $item[
                                            'nama_indikator'
                                        ]
                                    ) ?>

                                </strong>

                            </td>



                            <!-- DESKRIPSI -->

                            <td>

                                <div class="description">

                                    <?= nl2br(
                                        e(
                                            $item[
                                                'deskripsi'
                                            ]
                                        )
                                    ) ?>

                                </div>

                            </td>



                            <!-- NILAI -->

                            <td>

                                <select
                                    name="nilai[<?= $id_indikator ?>]"
                                    class="nilai-indikator"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Nilai --
                                    </option>


                                    <option
                                        value="4"
                                        <?= (
                                            (string)$nilai
                                            === '4'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        4 - Sangat Baik
                                    </option>


                                    <option
                                        value="3"
                                        <?= (
                                            (string)$nilai
                                            === '3'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        3 - Baik
                                    </option>


                                    <option
                                        value="2"
                                        <?= (
                                            (string)$nilai
                                            === '2'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        2 - Cukup
                                    </option>


                                    <option
                                        value="1"
                                        <?= (
                                            (string)$nilai
                                            === '1'
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        1 - Perlu Perbaikan
                                    </option>

                                </select>

                            </td>



                            <!-- CATATAN INDIKATOR -->

                            <td>

                                <textarea
                                    name="catatan[<?= $id_indikator ?>]"
                                    placeholder="Catatan penilai..."
                                ><?= e(
                                    $catatan
                                ) ?></textarea>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>


            </div>


        <?php endforeach; ?>



        <!-- =================================================
             DATA PENILAI
        ================================================== -->

        <div class="card">


            <h2>
                Data Penilaian
            </h2>


            <div class="form-row">


                <!-- TANGGAL -->

                <div class="form-group">

                    <label>

                        Tanggal Penilaian

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="date"
                        name="tanggal_penilaian"
                        value="<?= e(
                            $data[
                                'tanggal_penilaian'
                            ]
                        ) ?>"
                        required
                    >

                </div>



                <!-- PENILAI -->

                <div class="form-group">

                    <label>

                        Nama Penilai

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        name="penilai"
                        value="<?= e(
                            $data[
                                'penilai'
                            ]
                        ) ?>"
                        placeholder="Nama lengkap penilai"
                        required
                    >

                </div>


            </div>



            <!-- =================================================
                 CATATAN UMUM
            ================================================== -->

            <div class="form-group">

                <label>
                    Catatan Umum
                </label>


                <textarea
                    name="catatan_umum"
                    placeholder="Catatan umum hasil penilaian..."
                ><?= e(
                    $data['catatan']
                ) ?></textarea>

            </div>



            <!-- =================================================
                 TINDAK LANJUT
            ================================================== -->

            <div class="form-group">

                <label>
                    Tindak Lanjut
                </label>


                <textarea
                    name="tindak_lanjut"
                    placeholder="Rencana tindak lanjut apabila diperlukan..."
                ><?= e(
                    $data['tindak_lanjut']
                ) ?></textarea>

            </div>



            <!-- =================================================
                 BUTTON
            ================================================== -->

            <div class="buttons">


                <a
                    href="penilaian.php"
                    class="btn btn-secondary"
                >
                    ← Kembali
                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    💾 Simpan Penilaian
                </button>


            </div>


        </div>


    </form>

</div>



<script>

/*
|--------------------------------------------------------------------------
| VALIDASI FORM SEBELUM SUBMIT
|--------------------------------------------------------------------------
*/

document
    .getElementById('formPenilaian')
    .addEventListener(
        'submit',
        function(event) {


            const selects =
                document.querySelectorAll(
                    '.nilai-indikator'
                );


            let lengkap = true;


            selects.forEach(
                function(select) {

                    if (
                        select.value === ''
                    ) {

                        lengkap = false;

                        select.focus();
                    }

                }
            );


            if (!lengkap) {

                event.preventDefault();


                alert(
                    'Semua indikator OPPE wajib diberikan nilai sebelum disimpan.'
                );


                return false;
            }


            /*
             * Konfirmasi sebelum menyimpan
             */

            const konfirmasi =
                confirm(
                    'Apakah semua nilai OPPE sudah benar dan penilaian akan disimpan?'
                );


            if (!konfirmasi) {

                event.preventDefault();

                return false;
            }


        }
    );

</script>


</body>

</html>