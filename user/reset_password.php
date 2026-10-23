<?php

require_once "../config/admin.php";
require_once "../config/database.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID user tidak valid.");
}


/*
|--------------------------------------------------------------------------
| Password sementara
|--------------------------------------------------------------------------
*/

$password_baru = "Rsud@" . date("Y");


$password_hash =
    password_hash(
        $password_baru,
        PASSWORD_DEFAULT
    );


$stmt = mysqli_prepare(
    $conn,
    "UPDATE users
     SET password = ?
     WHERE id_user = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $password_hash,
    $id
);

if (mysqli_stmt_execute($stmt)) {

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Password Berhasil Direset</title>

<style>

body {
    margin: 0;
    background: #f4f7fb;
    font-family: "Segoe UI", Arial, sans-serif;
}

.box {
    max-width: 500px;
    margin: 100px auto;
    background: white;
    padding: 35px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 5px 25px rgba(0,0,0,.08);
}

h2 {
    color: #198754;
}

.password {
    background: #f1f5f9;
    padding: 15px;
    border-radius: 8px;
    font-size: 20px;
    font-weight: bold;
    margin: 20px 0;
}

a {
    display: inline-block;
    background: #0d6efd;
    color: white;
    padding: 11px 18px;
    border-radius: 8px;
    text-decoration: none;
}

</style>

</head>

<body>

<div class="box">

<h2>✓ Password Berhasil Direset</h2>

<p>
Password sementara user adalah:
</p>

<div class="password">

<?= htmlspecialchars($password_baru); ?>

</div>

<p>
Silakan berikan password sementara kepada pengguna
dan minta pengguna menggantinya setelah login.
</p>

<a href="index.php">
Kembali ke Manajemen User
</a>

</div>

</body>

</html>

<?php

} else {

    die(
        "Gagal reset password: " .
        mysqli_error($conn)
    );

}