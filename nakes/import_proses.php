<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| CEK DATA IMPORT
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['import_nakes_data']) ||
    empty($_SESSION['import_nakes_headers'])
) {

    die("
        <script>
            alert('Tidak ada data Excel yang siap diimport.');
            window.location='import.php';
        </script>
    ");
}

$data    = $_SESSION['import_nakes_data'];
$headers = $_SESSION['import_nakes_headers'];


/*
|--------------------------------------------------------------------------
| FUNGSI BANTU
|--------------------------------------------------------------------------
*/

function normalisasi($text)
{
    $text = trim((string)$text);

    // Hilangkan spasi berulang
    $text = preg_replace('/\s+/', ' ', $text);

    return $text;
}


function normalisasi_profesi($text)
{
    $text = normalisasi($text);

    $map = [

        'Teknologi Laboratorium   Medik'
            => 'Teknologi Laboratorium Medik',

        'Fisioterafis'
            => 'Fisioterapis',

        'Fisioterpis'
            => 'Fisioterapis',

        'Refraksionis Optisien'
            => 'Refraksi Optisien',

        'Terapi Wicara'
            => 'Terapis Wicara',

        'Penata Anestesi'
            => 'Penata Anesthesi'
    ];

    return $map[$text] ?? $text;
}


function normalisasi_unit($text)
{
    $text = normalisasi($text);

    if (strtolower($text) === 'bank darah') {
        return 'Bank Darah';
    }

    return $text;
}


function normalisasi_gender($text)
{
    $text = strtolower(normalisasi($text));

    if (
        $text === 'laki-laki' ||
        $text === 'laki laki' ||
        $text === 'l' ||
        $text === 'pria'
    ) {
        return 'L';
    }

    if (
        $text === 'perempuan' ||
        $text === 'p' ||
        $text === 'wanita'
    ) {
        return 'P';
    }

    return '';
}


function format_tanggal($value)
{
    if ($value === null || trim((string)$value) === '') {
        return null;
    }

    $value = trim((string)$value);

    /*
    |--------------------------------------------------------------------------
    | DD/MM/YYYY
    |--------------------------------------------------------------------------
    */

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m)) {

        return sprintf(
            '%04d-%02d-%02d',
            $m[3],
            $m[2],
            $m[1]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | YYYY-MM-DD
    |--------------------------------------------------------------------------
    */

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | COBA DATE
    |--------------------------------------------------------------------------
    */

    $timestamp = strtotime($value);

    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| CARI INDEX KOLOM EXCEL
|--------------------------------------------------------------------------
*/

$columnMap = [];

foreach ($headers as $key => $header) {

    $h = strtolower(normalisasi($header));

    $columnMap[$h] = $key;
}


function excel_value($row, $columnMap, $name)
{
    $name = strtolower($name);

    if (!isset($columnMap[$name])) {
        return '';
    }

    $key = $columnMap[$name];

    return $row[$key] ?? '';
}


/*
|--------------------------------------------------------------------------
| MASTER DATA
|--------------------------------------------------------------------------
*/

$profesi = [];
$unit = [];
$jabatan = [];
$status = [];


/*
|--------------------------------------------------------------------------
| PROFESI
|--------------------------------------------------------------------------
*/

$q = mysqli_query(
    $conn,
    "SELECT id_profesi, nama_profesi
     FROM profesi
     WHERE status='AKTIF'"
);

while ($r = mysqli_fetch_assoc($q)) {

    $profesi[mb_strtolower(normalisasi($r['nama_profesi']))]
        = $r['id_profesi'];
}


/*
|--------------------------------------------------------------------------
| UNIT
|--------------------------------------------------------------------------
*/

$q = mysqli_query(
    $conn,
    "SELECT id_unit, nama_unit
     FROM unit_kerja
     WHERE status='AKTIF'"
);

while ($r = mysqli_fetch_assoc($q)) {

    $unit[mb_strtolower(normalisasi($r['nama_unit']))]
        = $r['id_unit'];
}


/*
|--------------------------------------------------------------------------
| JABATAN
|--------------------------------------------------------------------------
*/

$q = mysqli_query(
    $conn,
    "SELECT id_jabatan, nama_jabatan
     FROM jabatan
     WHERE status='AKTIF'"
);

while ($r = mysqli_fetch_assoc($q)) {

    $jabatan[mb_strtolower(normalisasi($r['nama_jabatan']))]
        = $r['id_jabatan'];
}


/*
|--------------------------------------------------------------------------
| STATUS KEPEGAWAIAN
|--------------------------------------------------------------------------
*/

$q = mysqli_query(
    $conn,
    "SELECT id_status, nama_status
     FROM status_kepegawaian
     WHERE status='AKTIF'"
);

while ($r = mysqli_fetch_assoc($q)) {

    $status[mb_strtolower(normalisasi($r['nama_status']))]
        = $r['id_status'];
}


/*
|--------------------------------------------------------------------------
| HASIL
|--------------------------------------------------------------------------
*/

$berhasil = 0;
$gagal = 0;

$errorList = [];


/*
|--------------------------------------------------------------------------
| TRANSACTION
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conn);


try {

    foreach ($data as $index => $row) {

        $barisExcel = $index + 2;


        /*
        |--------------------------------------------------------------------------
        | AMBIL DATA EXCEL
        |--------------------------------------------------------------------------
        */

        $nik = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'nik'
            )
        );

        $nip = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'nip'
            )
        );

        $nama = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'nama lengkap'
            )
        );

        $gelarDepan = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'gelar depan'
            )
        );

        $gelarBelakang = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'gelar belakang'
            )
        );

        $tempatLahir = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'tempat lahir'
            )
        );

        $tanggalLahir = format_tanggal(
            excel_value(
                $row,
                $columnMap,
                'tanggal lahir'
            )
        );

        $gender = normalisasi_gender(
            excel_value(
                $row,
                $columnMap,
                'jenis kelamin'
            )
        );

        $noHp = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'no. hp'
            )
        );

        $email = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'email'
            )
        );

        $alamat = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'alamat'
            )
        );

        $profesiExcel = normalisasi_profesi(
            excel_value(
                $row,
                $columnMap,
                'profesi'
            )
        );

        $unitExcel = normalisasi_unit(
            excel_value(
                $row,
                $columnMap,
                'unit kerja'
            )
        );

        $jabatanExcel = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'jabatan'
            )
        );

        $statusExcel = normalisasi(
            excel_value(
                $row,
                $columnMap,
                'status kepegawaian'
            )
        );

        $tanggalMulai = format_tanggal(
            excel_value(
                $row,
                $columnMap,
                'tanggal muali kerja'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDASI WAJIB
        |--------------------------------------------------------------------------
        */

        $errors = [];

        if ($nama === '') {
            $errors[] = 'Nama lengkap kosong';
        }

        if ($gender === '') {
            $errors[] = 'Jenis kelamin tidak valid';
        }

        /*
        |--------------------------------------------------------------------------
        | PROFESI
        |--------------------------------------------------------------------------
        */

        $profesiKey = mb_strtolower($profesiExcel);

        if (!isset($profesi[$profesiKey])) {

            $errors[] =
                "Profesi tidak ditemukan: " .
                $profesiExcel;

        } else {

            $idProfesi = $profesi[$profesiKey];
        }


        /*
        |--------------------------------------------------------------------------
        | UNIT
        |--------------------------------------------------------------------------
        */

        $unitKey = mb_strtolower($unitExcel);

        if (!isset($unit[$unitKey])) {

            $errors[] =
                "Unit kerja tidak ditemukan: " .
                $unitExcel;

        } else {

            $idUnit = $unit[$unitKey];
        }


        /*
        |--------------------------------------------------------------------------
        | JABATAN
        |--------------------------------------------------------------------------
        */

        $jabatanKey = mb_strtolower($jabatanExcel);

        if (!isset($jabatan[$jabatanKey])) {

            $errors[] =
                "Jabatan tidak ditemukan: " .
                $jabatanExcel;

        } else {

            $idJabatan = $jabatan[$jabatanKey];
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS KEPEGAWAIAN
        |--------------------------------------------------------------------------
        */

        $statusKey = mb_strtolower($statusExcel);

        if (!isset($status[$statusKey])) {

            $errors[] =
                "Status kepegawaian tidak ditemukan: " .
                $statusExcel;

        } else {

            $idStatus = $status[$statusKey];
        }


        /*
        |--------------------------------------------------------------------------
        | CEK NIP DUPLIKAT
        |--------------------------------------------------------------------------
        */

        if ($nip !== '') {

            $nipEsc = mysqli_real_escape_string(
                $conn,
                $nip
            );

            $cek = mysqli_query(
                $conn,
                "SELECT id_nakes
                 FROM tenaga_kesehatan
                 WHERE nip='$nipEsc'
                 LIMIT 1"
            );

            if (mysqli_num_rows($cek) > 0) {

                $errors[] =
                    "NIP sudah terdaftar: " .
                    $nip;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | JIKA ERROR
        |--------------------------------------------------------------------------
        */

        if (!empty($errors)) {

            $gagal++;

            $errorList[] = [
                'baris' => $barisExcel,
                'nama' => $nama,
                'nip' => $nip,
                'error' => implode('; ', $errors)
            ];

            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | ESCAPE DATA
        |--------------------------------------------------------------------------
        */

        $nikSQL =
            ($nik === '')
            ? "NULL"
            : "'" . mysqli_real_escape_string($conn, $nik) . "'";

        $nipSQL =
            ($nip === '')
            ? "NULL"
            : "'" . mysqli_real_escape_string($conn, $nip) . "'";

        $namaSQL =
            "'" . mysqli_real_escape_string($conn, $nama) . "'";

        $gelarDepanSQL =
            ($gelarDepan === '')
            ? "NULL"
            : "'" . mysqli_real_escape_string($conn, $gelarDepan) . "'";

        $gelarBelakangSQL =
            ($gelarBelakang === '')
            ? "NULL"
            : "'" . mysqli_real_escape_string($conn, $gelarBelakang) . "'";

        $tempatLahirSQL =
            ($tempatLahir === '')
            ? "NULL"
            : "'" . mysqli_real_escape_string($conn, $tempatLahir) . "'";

        $tanggalLahirSQL =
            ($tanggalLahir === null)
            ? "NULL"
            : "'" . $tanggalLahir . "'";

        $noHpSQL =
            ($noHp === '')
            ? "NULL"
            : "'" . mysqli_real_escape_string($conn, $noHp) . "'";

        $emailSQL =
            ($email === '')
            ? "NULL"
            : "'" . mysqli_real_escape_string($conn, $email) . "'";

        $alamatSQL =
            ($alamat === '')
            ? "NULL"
            : "'" . mysqli_real_escape_string($conn, $alamat) . "'";

        $tanggalMulaiSQL =
            ($tanggalMulai === null)
            ? "NULL"
            : "'" . $tanggalMulai . "'";


        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        $sql = "
            INSERT INTO tenaga_kesehatan
            (
                nomor_induk,
                nik,
                nip,
                nama_lengkap,
                gelar_depan,
                gelar_belakang,
                tempat_lahir,
                tanggal_lahir,
                jenis_kelamin,
                no_hp,
                email,
                alamat,
                id_profesi,
                id_unit,
                id_jabatan,
                id_status_kepegawaian,
                tanggal_mulai_kerja,
                status_aktif,
                foto,
                created_at,
                updated_at
            )
            VALUES
            (
                NULL,
                $nikSQL,
                $nipSQL,
                $namaSQL,
                $gelarDepanSQL,
                $gelarBelakangSQL,
                $tempatLahirSQL,
                $tanggalLahirSQL,
                '$gender',
                $noHpSQL,
                $emailSQL,
                $alamatSQL,
                $idProfesi,
                $idUnit,
                $idJabatan,
                $idStatus,
                $tanggalMulaiSQL,
                'AKTIF',
                NULL,
                NOW(),
                NOW()
            )
        ";

        if (!mysqli_query($conn, $sql)) {

            throw new Exception(
                "Gagal menyimpan baris Excel $barisExcel: " .
                mysqli_error($conn)
            );
        }

        $berhasil++;
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    mysqli_commit($conn);

} catch (Throwable $e) {

    mysqli_rollback($conn);

    die("
        <div style='font-family:Arial;padding:30px'>
            <h2>Import dibatalkan</h2>
            <p style='color:red'>
                " . htmlspecialchars($e->getMessage()) . "
            </p>

            <a href='import.php'>
                Kembali
            </a>
        </div>
    ");
}


/*
|--------------------------------------------------------------------------
| HAPUS SESSION
|--------------------------------------------------------------------------
*/

unset($_SESSION['import_nakes_data']);
unset($_SESSION['import_nakes_headers']);
unset($_SESSION['import_nakes_filename']);

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<title>Hasil Import</title>

<style>

body {
    font-family:Arial;
    background:#f5f6fa;
    padding:30px;
}

.container {
    max-width:1200px;
    margin:auto;
}

.card {
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
}

.success {
    background:#dcfce7;
    color:#166534;
    padding:15px;
    border-radius:6px;
    margin-bottom:15px;
}

.warning {
    background:#fef3c7;
    color:#92400e;
    padding:15px;
    border-radius:6px;
    margin-bottom:15px;
}

table {
    border-collapse:collapse;
    width:100%;
    margin-top:15px;
}

th {
    background:#1f2937;
    color:white;
    padding:8px;
}

td {
    border:1px solid #ddd;
    padding:8px;
}

.btn {
    display:inline-block;
    padding:10px 18px;
    background:#2563eb;
    color:white;
    text-decoration:none;
    border-radius:6px;
    margin-top:15px;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h2>Hasil Import Data Nakes</h2>

<div class="success">

Data berhasil diimport:

<strong><?= $berhasil ?></strong> data

</div>

<?php if ($gagal > 0): ?>

<div class="warning">

<strong><?= $gagal ?></strong> data tidak diimport karena terdapat kesalahan.

</div>

<table>

<thead>

<tr>

<th>Baris Excel</th>
<th>Nama</th>
<th>NIP</th>
<th>Kesalahan</th>

</tr>

</thead>

<tbody>

<?php foreach ($errorList as $err): ?>

<tr>

<td><?= htmlspecialchars($err['baris']) ?></td>

<td><?= htmlspecialchars($err['nama']) ?></td>

<td><?= htmlspecialchars($err['nip']) ?></td>

<td><?= htmlspecialchars($err['error']) ?></td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>


<a href="index.php" class="btn">
Kembali ke Data Nakes
</a>

<a href="import.php" class="btn">
Import Lagi
</a>

</div>

</div>

</body>

</html>