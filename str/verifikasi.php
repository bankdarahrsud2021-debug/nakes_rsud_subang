<?php

require_once "../config/auth.php";
require_once "../config/database.php";
$role = $_SESSION['role'] ?? 'ANGGOTA';

if ($role !== 'ADMIN' && $role !== 'PENGURUS') {
    die("
        <div style='
            font-family: Arial;
            text-align: center;
            margin-top: 100px;
        '>
            <h2 style='color:#dc3545;'>
                Akses Ditolak
            </h2>

            <p>
                Anda tidak memiliki hak akses
                untuk melakukan verifikasi STR.
            </p>

            <a href='../dashboard/index.php'
               style='
                   display:inline-block;
                   padding:10px 18px;
                   background:#0d6efd;
                   color:white;
                   text-decoration:none;
                   border-radius:6px;
               '>
                Kembali ke Dashboard
            </a>
        </div>
    ");

}
/*
|--------------------------------------------------------------------------
| VALIDASI ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: index.php?error=id");
    exit;
}

$id_str = (int) $_GET['id'];

if ($id_str <= 0) {

    header("Location: index.php?error=id");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA STR
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        s.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi,
        u.nama_unit
     FROM str_nakes s
     INNER JOIN tenaga_kesehatan t
        ON s.id_nakes = t.id_nakes
     LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
     LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
     WHERE s.id_str = ?"
);

if (!$stmt) {

    die(
        "Prepare query gagal: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_str
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| CEK DATA
|--------------------------------------------------------------------------
*/

if (!$data) {

    header("Location: index.php?error=notfound");
    exit;
}


/*
|--------------------------------------------------------------------------
| USER LOGIN
|--------------------------------------------------------------------------
*/

$nama_user = $_SESSION['nama_user']
    ?? $_SESSION['username']
    ?? 'Petugas';


/*
|--------------------------------------------------------------------------
| STATUS VERIFIKASI SAAT INI
|--------------------------------------------------------------------------
*/

$status_sekarang = strtoupper(
    trim($data['status_verifikasi'] ?? '')
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Verifikasi STR</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px;
    background: #f4f6f9;
    font-family: Arial, sans-serif;
    color: #333;
}

.container {
    max-width: 850px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

h2 {
    margin-top: 0;
    color: #1f4e79;
    border-bottom: 2px solid #eee;
    padding-bottom: 15px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
}

.data-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.data-table td:first-child {
    width: 220px;
    font-weight: bold;
    color: #555;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: bold;
}

.status-verified {
    background: #d1e7dd;
    color: #0f5132;
}

.status-rejected {
    background: #f8d7da;
    color: #842029;
}

.status-pending {
    background: #fff3cd;
    color: #664d03;
}

.warning {
    padding: 14px;
    background: #fff3cd;
    color: #664d03;
    border-radius: 6px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.error {
    padding: 12px;
    background: #f8d7da;
    color: #842029;
    border-radius: 6px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

select {
    width: 100%;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}

.btn {
    display: inline-block;
    padding: 11px 18px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

.btn-save {
    background: #198754;
    color: white;
}

.btn-back {
    background: #6c757d;
    color: white;
    margin-left: 5px;
}

.btn-document {
    background: #0d6efd;
    color: white;
}

</style>

</head>

<body>

<div class="container">

    <h2>🔎 Verifikasi STR</h2>


    <?php if (isset($_GET['error'])): ?>

        <div class="error">

            <?php

            if ($_GET['error'] === 'status') {

                echo "❌ Hasil verifikasi belum dipilih.";

            } elseif ($_GET['error'] === 'database') {

                echo "❌ Terjadi kesalahan saat menyimpan data.";

            } else {

                echo "❌ Terjadi kesalahan.";

            }

            ?>

        </div>

    <?php endif; ?>


    <table class="data-table">

        <tr>

            <td>Nama Tenaga Kesehatan</td>

            <td>

                <?= htmlspecialchars(
                    $data['gelar_depan'] ?? ''
                ); ?>

                <?= htmlspecialchars(
                    $data['nama_lengkap'] ?? ''
                ); ?>

                <?= htmlspecialchars(
                    $data['gelar_belakang'] ?? ''
                ); ?>

            </td>

        </tr>


        <tr>

            <td>Profesi</td>

            <td>

                <?= htmlspecialchars(
                    $data['nama_profesi'] ?? '-'
                ); ?>

            </td>

        </tr>


        <tr>

            <td>Unit Kerja</td>

            <td>

                <?= htmlspecialchars(
                    $data['nama_unit'] ?? '-'
                ); ?>

            </td>

        </tr>


        <tr>

            <td>Nomor STR</td>

            <td>

                <?= htmlspecialchars(
                    $data['nomor_str'] ?? '-'
                ); ?>

            </td>

        </tr>


        <tr>

            <td>Jenis STR</td>

            <td>

                <?= htmlspecialchars(
                    $data['jenis_str'] ?? '-'
                ); ?>

            </td>

        </tr>


        <tr>

            <td>Tanggal Terbit</td>

            <td>

                <?= !empty($data['tanggal_terbit'])
                    ? date(
                        'd-m-Y',
                        strtotime(
                            $data['tanggal_terbit']
                        )
                    )
                    : '-';
                ?>

            </td>

        </tr>


        <tr>

            <td>Tanggal Berakhir</td>

            <td>

                <?= !empty($data['tanggal_berakhir'])
                    ? date(
                        'd-m-Y',
                        strtotime(
                            $data['tanggal_berakhir']
                        )
                    )
                    : '-';
                ?>

            </td>

        </tr>


        <tr>

            <td>Status Masa Berlaku</td>

            <td>

                <?= htmlspecialchars(
                    $data['status'] ?? '-'
                ); ?>

            </td>

        </tr>


        <tr>

            <td>Status Verifikasi Saat Ini</td>

            <td>

                <?php

                if (
                    $status_sekarang === 'TERVERIFIKASI'
                ) {

                    echo '<span class="status status-verified">
                            TERVERIFIKASI
                          </span>';

                } elseif (
                    $status_sekarang === 'DITOLAK'
                ) {

                    echo '<span class="status status-rejected">
                            DITOLAK
                          </span>';

                } else {

                    echo '<span class="status status-pending">
                            BELUM DIVERIFIKASI
                          </span>';
                }

                ?>

            </td>

        </tr>


        <tr>

            <td>Petugas Login</td>

            <td>

                <?= htmlspecialchars(
                    $nama_user
                ); ?>

            </td>

        </tr>


        <?php if (!empty($data['file_str'])): ?>

        <tr>

            <td>Dokumen STR</td>

            <td>

                <a
                    href="../uploads/str_nakes/<?= urlencode($data['file_str']); ?>"
                    target="_blank"
                    class="btn btn-document">

                    📄 Lihat Dokumen STR

                </a>

            </td>

        </tr>

        <?php endif; ?>

    </table>


    <div class="warning">

        <strong>Perhatian:</strong><br>

        Pastikan identitas tenaga kesehatan,
        nomor STR, jenis STR, tanggal terbit,
        tanggal berakhir, dan dokumen STR telah
        diperiksa sebelum menentukan hasil verifikasi.

    </div>


    <form
        action="proses_verifikasi.php"
        method="POST">

        <input
            type="hidden"
            name="id_str"
            value="<?= (int)$data['id_str']; ?>">


        <div class="form-group">

            <label for="status">
                Hasil Verifikasi
            </label>

            <select
                name="status"
                id="status"
                required>

                <option value="">
                    -- Pilih Hasil Verifikasi --
                </option>

                <option
                    value="TERVERIFIKASI"
                    <?= $status_sekarang === 'TERVERIFIKASI'
                        ? 'selected'
                        : ''; ?>>

                    TERVERIFIKASI

                </option>

                <option
                    value="DITOLAK"
                    <?= $status_sekarang === 'DITOLAK'
                        ? 'selected'
                        : ''; ?>>

                    DITOLAK

                </option>

            </select>

        </div>


        <button
            type="submit"
            class="btn btn-save"
            onclick="return confirm('Yakin ingin menyimpan hasil verifikasi STR ini?');">

            💾 Simpan Verifikasi

        </button>


        <a
            href="index.php"
            class="btn btn-back">

            ← Kembali

        </a>

    </form>

</div>

</body>

</html>