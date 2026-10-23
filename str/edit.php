<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Validasi ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    die("ID STR tidak valid.");

}

$id_str = (int) $_GET['id'];


/*
|--------------------------------------------------------------------------
| Ambil Data STR
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        s.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi

    FROM str_nakes s

    INNER JOIN tenaga_kesehatan t
        ON s.id_nakes = t.id_nakes

    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi

    WHERE s.id_str = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_str
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$data) {

    die("Data STR tidak ditemukan.");

}


/*
|--------------------------------------------------------------------------
| Ambil Data Tenaga Kesehatan
|--------------------------------------------------------------------------
*/

$query_nakes = "
    SELECT
        t.id_nakes,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi

    FROM tenaga_kesehatan t

    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi

    WHERE t.status_aktif = 'AKTIF'

    ORDER BY t.nama_lengkap ASC
";

$nakes = mysqli_query(
    $conn,
    $query_nakes
);

if (!$nakes) {

    die(
        "Gagal mengambil data tenaga kesehatan: "
        . mysqli_error($conn)
    );

}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Data STR</title>


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
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .header h2 {
            margin: 0 0 5px;
            color: #1f4e79;
        }

        .header p {
            margin: 0;
            color: #666;
        }

        .navigation {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
            color: white;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .btn-dashboard {
            background: #198754;
        }

        .btn-back {
            background: #6c757d;
        }

        .btn-save {
            background: #0d6efd;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #444;
        }

        .required {
            color: red;
        }

        input,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #0d6efd;
        }

        .info {
            background: #cff4fc;
            color: #055160;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .current-file {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 6px;
            margin-top: 8px;
        }

        .current-file a {
            color: #0d6efd;
            text-decoration: none;
        }

        .help {
            display: block;
            margin-top: 5px;
            color: #777;
            font-size: 12px;
        }

        .form-actions {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

    </style>

</head>


<body>

<div class="container">


    <!-- HEADER -->

    <div class="header">

        <h2>
            Edit Data STR
        </h2>

        <p>
            Mengubah data Surat Tanda Registrasi
            Tenaga Kesehatan
        </p>

    </div>


    <!-- NAVIGASI -->

    <div class="navigation">

        <a
            href="../dashboard/index.php"
            class="btn btn-dashboard"
        >
            🏠 Dashboard
        </a>

        <a
            href="index.php"
            class="btn btn-back"
        >
            ← Kembali ke Data STR
        </a>

    </div>


    <!-- FORM -->

    <div class="card">

        <div class="info">

            Silakan ubah data STR yang diperlukan.
            Jika tidak ingin mengganti dokumen STR,
            biarkan kolom file kosong.

        </div>


        <form
            action="update.php"
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="id_str"
                value="<?= $data['id_str']; ?>"
            >


            <!-- TENAGA KESEHATAN -->

            <div class="form-group">

                <label>
                    Tenaga Kesehatan
                    <span class="required">*</span>
                </label>

                <select
                    name="id_nakes"
                    required
                >

                    <option value="">
                        -- Pilih Tenaga Kesehatan --
                    </option>


                    <?php while ($row = mysqli_fetch_assoc($nakes)) { ?>

                        <?php

                        $nama = trim(

                            ($row['gelar_depan']
                                ? $row['gelar_depan'] . ' '
                                : ''
                            )

                            . $row['nama_lengkap'] .

                            ($row['gelar_belakang']
                                ? ', ' . $row['gelar_belakang']
                                : ''
                            )

                        );

                        ?>

                        <option
                            value="<?= $row['id_nakes']; ?>"

                            <?= $data['id_nakes']
                                == $row['id_nakes']
                                ? 'selected'
                                : '';
                            ?>
                        >

                            <?= htmlspecialchars($nama); ?>

                            <?php if (!empty($row['nama_profesi'])) { ?>

                                -
                                <?= htmlspecialchars(
                                    $row['nama_profesi']
                                ); ?>

                            <?php } ?>

                        </option>

                    <?php } ?>

                </select>

            </div>


            <!-- NOMOR STR -->

            <div class="form-group">

                <label>
                    Nomor STR
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="nomor_str"
                    value="<?= htmlspecialchars(
                        $data['nomor_str']
                    ); ?>"
                    required
                >

            </div>


            <!-- JENIS STR -->

            <div class="form-group">

                <label>
                    Jenis STR
                    <span class="required">*</span>
                </label>

                <select
                    name="jenis_str"
                    required
                >

                    <option value="">
                        -- Pilih Jenis STR --
                    </option>

                    <option
                        value="STR Lama"
                        <?= $data['jenis_str'] == 'STR Lama'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        STR Lama
                    </option>

                    <option
                        value="STR Elektronik"
                        <?= $data['jenis_str'] == 'STR Elektronik'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        STR Elektronik
                    </option>

                    <option
                        value="STR Seumur Hidup"
                        <?= $data['jenis_str'] == 'STR Seumur Hidup'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        STR Seumur Hidup
                    </option>

                    <option
                        value="STR Lainnya"
                        <?= $data['jenis_str'] == 'STR Lainnya'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        STR Lainnya
                    </option>

                </select>

            </div>


            <!-- TANGGAL TERBIT -->

            <div class="form-group">

                <label>
                    Tanggal Terbit
                </label>

                <input
                    type="date"
                    name="tanggal_terbit"
                    value="<?= htmlspecialchars(
                        $data['tanggal_terbit'] ?? ''
                    ); ?>"
                >

            </div>


            <!-- TANGGAL BERAKHIR -->

            <div class="form-group">

                <label>
                    Tanggal Berakhir
                </label>

                <input
                    type="date"
                    name="tanggal_berakhir"
                    value="<?= htmlspecialchars(
                        $data['tanggal_berakhir'] ?? ''
                    ); ?>"
                >

                <small class="help">

                    Jika STR tidak mempunyai
                    tanggal berakhir, dapat dikosongkan.

                </small>

            </div>


            <!-- FILE STR -->

            <div class="form-group">

                <label>
                    File STR
                </label>


                <?php if (!empty($data['file_str'])) { ?>

                    <div class="current-file">

                        📄 File saat ini:

                        <a
                            href="../uploads/str_nakes/<?= htmlspecialchars(
                                $data['file_str']
                            ); ?>"
                            target="_blank"
                        >

                            Lihat Dokumen STR

                        </a>

                    </div>

                <?php } ?>


                <input
                    type="file"
                    name="file_str"
                    accept=".pdf,.jpg,.jpeg,.png"
                >

                <small class="help">

                    Kosongkan jika tidak ingin
                    mengganti file STR.

                    Maksimal 5 MB.

                </small>

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option
                        value="BELUM DIVERIFIKASI"

                        <?= strtoupper(
                            $data['status'] ?? ''
                        ) == 'BELUM DIVERIFIKASI'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        BELUM DIVERIFIKASI
                    </option>

                    <option
                        value="TERVERIFIKASI"

                        <?= strtoupper(
                            $data['status'] ?? ''
                        ) == 'TERVERIFIKASI'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        TERVERIFIKASI
                    </option>

                    <option
                        value="DITOLAK"

                        <?= strtoupper(
                            $data['status'] ?? ''
                        ) == 'DITOLAK'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        DITOLAK
                    </option>

                </select>

            </div>


            <!-- TOMBOL -->

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-save"
                >
                    💾 Simpan Perubahan
                </button>

                <a
                    href="index.php"
                    class="btn btn-back"
                >
                    Batal
                </a>

            </div>

        </form>

    </div>

</div>

</body>

</html>