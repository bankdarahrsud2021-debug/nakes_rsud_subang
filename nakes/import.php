<?php
require_once "../config/auth.php";
require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CEK LIBRARY PHPSPREADSHEET
|--------------------------------------------------------------------------
*/
$autoload = "../vendor/autoload.php";

if (!file_exists($autoload)) {
    die("
        <div style='font-family:Arial;padding:30px'>
            <h2>PhpSpreadsheet belum terpasang</h2>
            <p>File <b>vendor/autoload.php</b> tidak ditemukan.</p>
            <p>Silakan pasang PhpSpreadsheet terlebih dahulu.</p>
        </div>
    ");
}

require_once $autoload;

use PhpOffice\PhpSpreadsheet\IOFactory;

/*
|--------------------------------------------------------------------------
| PROSES UPLOAD
|--------------------------------------------------------------------------
*/
$error = "";
$preview = [];
$headers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $error = "File Excel belum dipilih atau gagal diupload.";
    } else {

        $file = $_FILES['file_excel'];

        $allowed = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $error = "Format file harus .xlsx atau .xls.";
        } else {

            try {

                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();

                $rows = $sheet->toArray(null, true, true, true);

                if (count($rows) < 2) {
                    $error = "File Excel tidak memiliki data.";
                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | HEADER
                    |--------------------------------------------------------------------------
                    */
                    $firstRow = array_shift($rows);

                    foreach ($firstRow as $key => $value) {
                        $headers[$key] = trim((string)$value);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | SIMPAN DATA KE SESSION
                    |--------------------------------------------------------------------------
                    */
                    $_SESSION['import_nakes_data'] = $rows;
                    $_SESSION['import_nakes_headers'] = $headers;
                    $_SESSION['import_nakes_filename'] = $file['name'];

                    /*
                    |--------------------------------------------------------------------------
                    | PREVIEW MAKSIMAL 20 BARIS
                    |--------------------------------------------------------------------------
                    */
                    $preview = array_slice($rows, 0, 20);
                }

            } catch (Throwable $e) {

                $error = "Gagal membaca Excel: " . $e->getMessage();

            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Import Data Nakes</title>

<style>
body {
    font-family: Arial, sans-serif;
    background:#f5f6fa;
    margin:0;
    padding:25px;
}

.container {
    max-width:1400px;
    margin:auto;
}

.card {
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
    margin-bottom:20px;
}

h2 {
    margin-top:0;
}

input[type=file] {
    padding:10px;
}

button,
.btn {
    border:0;
    padding:10px 18px;
    border-radius:6px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
}

.btn-upload {
    background:#2563eb;
    color:white;
}

.btn-import {
    background:#16a34a;
    color:white;
}

.btn-back {
    background:#6b7280;
    color:white;
}

.error {
    background:#fee2e2;
    color:#991b1b;
    padding:12px;
    border-radius:6px;
    margin-bottom:15px;
}

.success {
    background:#dcfce7;
    color:#166534;
    padding:12px;
    border-radius:6px;
    margin-bottom:15px;
}

.table-wrapper {
    overflow-x:auto;
}

table {
    border-collapse:collapse;
    width:100%;
    background:white;
    font-size:13px;
}

th {
    background:#1f2937;
    color:white;
    padding:8px;
    border:1px solid #ddd;
    white-space:nowrap;
}

td {
    padding:8px;
    border:1px solid #ddd;
    white-space:nowrap;
}

.info {
    background:#eff6ff;
    color:#1e40af;
    padding:12px;
    border-radius:6px;
    margin-bottom:15px;
}
</style>
</head>

<body>

<div class="container">

    <div class="card">

        <h2>Import Data Tenaga Kesehatan</h2>

        <div class="info">
            Pilih file Excel data Nakes. Data akan diperiksa terlebih dahulu
            sebelum disimpan ke database.
        </div>

        <?php if ($error): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <input
                type="file"
                name="file_excel"
                accept=".xlsx,.xls"
                required
            >

            <button type="submit" class="btn btn-upload">
                Upload & Preview
            </button>

            <a href="index.php" class="btn btn-back">
                Kembali
            </a>

        </form>

    </div>


<?php if (!empty($preview)): ?>

    <div class="card">

        <h3>
            Preview Data
        </h3>

        <div class="success">
            File:
            <b><?= htmlspecialchars($_SESSION['import_nakes_filename']) ?></b>

            <br>

            Total data:
            <b><?= count($_SESSION['import_nakes_data']) ?></b>

            <br>

            Yang ditampilkan:
            maksimal 20 baris pertama.
        </div>

        <div class="table-wrapper">

            <table>

                <thead>

                <tr>

                    <th>No</th>

                    <?php foreach ($headers as $header): ?>

                        <th>
                            <?= htmlspecialchars($header) ?>
                        </th>

                    <?php endforeach; ?>

                </tr>

                </thead>

                <tbody>

                <?php $no = 1; ?>

                <?php foreach ($preview as $row): ?>

                    <tr>

                        <td><?= $no++ ?></td>

                        <?php foreach ($headers as $key => $header): ?>

                            <td>
                                <?= htmlspecialchars((string)($row[$key] ?? '')) ?>
                            </td>

                        <?php endforeach; ?>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <br>

        <form method="POST" action="import_proses.php">

            <button
                type="submit"
                class="btn btn-import"
                onclick="return confirm('Yakin ingin mengimport seluruh data Excel ke database?');"
            >
                Import Seluruh Data
            </button>

        </form>

    </div>

<?php endif; ?>

</div>

</body>
</html>