<?php
include "../config/auth.php";
include "../config/database.php";
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
                untuk melakukan proses verifikasi SIP.
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_sip = (int)($_POST['id_sip'] ?? 0);

$status_verifikasi = strtoupper(
    trim($_POST['status_verifikasi'] ?? '')
);

$id_user = (int)$_SESSION['id_user'];

if ($id_sip <= 0) {
    die("ID SIP tidak valid.");
}

$allowed_status = [
    'BELUM DIVERIFIKASI',
    'TERVERIFIKASI',
    'DITOLAK'
];

if (!in_array(
    $status_verifikasi,
    $allowed_status
)) {
    die("Status verifikasi tidak valid.");
}

$query = "
    UPDATE sip_nakes
    SET
        status_verifikasi = ?,
        verified_by = ?,
        verified_at = NOW(),
        updated_at = NOW()
    WHERE id_sip = ?
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
    "sii",
    $status_verifikasi,
    $id_user,
    $id_sip
);

if (mysqli_stmt_execute($stmt)) {

    echo "
    <script>
        alert('Verifikasi SIP berhasil disimpan.');
        window.location.href = 'index.php';
    </script>
    ";

    exit;

} else {

    die(
        "Gagal menyimpan verifikasi: " .
        mysqli_stmt_error($stmt)
    );
}