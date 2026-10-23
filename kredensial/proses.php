<?php

include "../config/auth.php";
include "../config/database.php";

$id_kredensial =
    (int)($_GET['id'] ?? $_POST['id_kredensial'] ?? 0);

if ($id_kredensial <= 0) {
    die("ID kredensial tidak valid.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tanggal_penilaian =
        $_POST['tanggal_penilaian'] ?: null;

    $tanggal_keputusan =
        $_POST['tanggal_keputusan'] ?: null;

    $tanggal_mulai =
        $_POST['tanggal_mulai'] ?: null;

    $tanggal_berakhir =
        $_POST['tanggal_berakhir'] ?: null;

    $status =
        strtoupper(
            trim($_POST['status'] ?? '')
        );

    $catatan =
        trim($_POST['catatan'] ?? '');

    $allowed_status = [
        'DIAJUKAN',
        'DALAM PENILAIAN',
        'DISETUJUI',
        'DITOLAK',
        'SELESAI'
    ];

    if (!in_array(
        $status,
        $allowed_status
    )) {

        die("Status kredensial tidak valid.");

    }

    $query = "
        UPDATE kredensial
        SET
            tanggal_penilaian = ?,
            tanggal_keputusan = ?,
            tanggal_mulai = ?,
            tanggal_berakhir = ?,
            status = ?,
            catatan = ?,
            updated_at = NOW()
        WHERE id_kredensial = ?
    ";

    $stmt = mysqli_prepare(
        $conn,
        $query
    );

    if (!$stmt) {

        die(
            "Prepare gagal: " .
            mysqli_error($conn)
        );

    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssi",
        $tanggal_penilaian,
        $tanggal_keputusan,
        $tanggal_mulai,
        $tanggal_berakhir,
        $status,
        $catatan,
        $id_kredensial
    );

    if (mysqli_stmt_execute($stmt)) {

        echo "
        <script>
            alert('Data proses kredensial berhasil disimpan.');
            window.location.href = 'index.php';
        </script>
        ";

        exit;

    } else {

        die(
            "Gagal menyimpan proses: " .
            mysqli_stmt_error($stmt)
        );

    }
}


/* Ambil data */

$query = "
    SELECT
        k.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi,
        u.nama_unit
    FROM kredensial k
    INNER JOIN tenaga_kesehatan t
        ON k.id_nakes = t.id_nakes
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
    WHERE k.id_kredensial = ?
";

$stmt = mysqli_prepare(
    $conn,
    $query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_kredensial
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$data =
    mysqli_fetch_assoc($result);

if (!$data) {
    die("Data kredensial tidak ditemukan.");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Proses Kredensial</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    padding: 20px;
}

.container {
    background: white;
    max-width: 700px;
    margin: auto;
    padding: 25px;
    border-radius: 8px;
}

.info {
    background: #f8f9fa;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
}

input,
select,
textarea {
    width: 100%;
    padding: 9px;
    margin-top: 5px;
    box-sizing: border-box;
}

textarea {
    min-height: 100px;
}

button {
    margin-top: 20px;
    padding: 10px 18px;
    border: none;
    border-radius: 5px;
    background: #198754;
    color: white;
    cursor: pointer;
}

</style>

</head>

<body>

<div class="container">

<a href="index.php">
← Kembali
</a>

<h2>Proses Kredensial</h2>

<div class="info">

<strong>Tenaga Kesehatan:</strong><br>

<?= htmlspecialchars(
    trim(
        ($data['gelar_depan'] ?? '') . ' ' .
        $data['nama_lengkap'] . ' ' .
        ($data['gelar_belakang'] ?? '')
    )
); ?>

<br><br>

<strong>Profesi:</strong>

<?= htmlspecialchars(
    $data['nama_profesi'] ?? '-'
); ?>

<br>

<strong>Unit:</strong>

<?= htmlspecialchars(
    $data['nama_unit'] ?? '-'
); ?>

<br>

<strong>Jenis:</strong>

<?= htmlspecialchars(
    $data['jenis_kredensial']
); ?>

</div>

<form
    method="POST"
    action="proses.php">

<input
    type="hidden"
    name="id_kredensial"
    value="<?= $data['id_kredensial']; ?>">

<label>
Status Kredensial
</label>

<select
    name="status"
    required>

<option value="DIAJUKAN"
<?= $data['status'] == 'DIAJUKAN'
    ? 'selected'
    : ''; ?>>
DIAJUKAN
</option>

<option value="DALAM PENILAIAN"
<?= $data['status'] == 'DALAM PENILAIAN'
    ? 'selected'
    : ''; ?>>
DALAM PENILAIAN
</option>

<option value="DISETUJUI"
<?= $data['status'] == 'DISETUJUI'
    ? 'selected'
    : ''; ?>>
DISETUJUI
</option>

<option value="DITOLAK"
<?= $data['status'] == 'DITOLAK'
    ? 'selected'
    : ''; ?>>
DITOLAK
</option>

<option value="SELESAI"
<?= $data['status'] == 'SELESAI'
    ? 'selected'
    : ''; ?>>
SELESAI
</option>

</select>

<label>
Tanggal Penilaian
</label>

<input
    type="date"
    name="tanggal_penilaian"
    value="<?= htmlspecialchars(
        $data['tanggal_penilaian'] ?? ''
    ); ?>">

<label>
Tanggal Keputusan
</label>

<input
    type="date"
    name="tanggal_keputusan"
    value="<?= htmlspecialchars(
        $data['tanggal_keputusan'] ?? ''
    ); ?>">

<label>
Tanggal Mulai Berlaku
</label>

<input
    type="date"
    name="tanggal_mulai"
    value="<?= htmlspecialchars(
        $data['tanggal_mulai'] ?? ''
    ); ?>">

<label>
Tanggal Berakhir
</label>

<input
    type="date"
    name="tanggal_berakhir"
    value="<?= htmlspecialchars(
        $data['tanggal_berakhir'] ?? ''
    ); ?>">

<label>
Catatan
</label>

<textarea
    name="catatan"><?= htmlspecialchars(
        $data['catatan'] ?? ''
    ); ?></textarea>

<button type="submit">
💾 Simpan Proses Kredensial
</button>

</form>

</div>

</body>

</html>