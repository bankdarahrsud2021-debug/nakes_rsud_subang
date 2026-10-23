<?php

require_once "../config/admin.php";
require_once "../config/database.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID user tidak valid.");
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM users
     WHERE id_user = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("User tidak ditemukan.");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit User | RSUD Subang</title>

<style>

body {
    margin: 0;
    background: #f4f7fb;
    font-family: "Segoe UI", Arial, sans-serif;
}

.container {
    max-width: 650px;
    margin: 50px auto;
    padding: 20px;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.07);
}

h2 {
    margin-top: 0;
    color: #0d6efd;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 600;
}

input,
select {
    width: 100%;
    height: 45px;
    padding: 0 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}

input:focus,
select:focus {
    outline: none;
    border-color: #0d6efd;
}

.buttons {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

button,
a {
    padding: 11px 17px;
    border-radius: 8px;
    border: none;
    text-decoration: none;
    cursor: pointer;
    font-size: 13px;
}

button {
    background: #0d6efd;
    color: white;
}

a {
    background: #6c757d;
    color: white;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h2>Edit User</h2>

<form
    method="POST"
    action="update.php"
>

<input
    type="hidden"
    name="id_user"
    value="<?= $user['id_user']; ?>"
>


<div class="form-group">

<label>
Username
</label>

<input
    type="text"
    name="username"
    value="<?= htmlspecialchars($user['username']); ?>"
    required
    maxlength="50"
>

</div>


<div class="form-group">

<label>
Nama User
</label>

<input
    type="text"
    name="nama_user"
    value="<?= htmlspecialchars($user['nama_user']); ?>"
    required
    maxlength="150"
>

</div>


<div class="form-group">

<label>
Role
</label>

<select name="role">

<option
    value="USER"
    <?= $user['role'] === 'USER'
        ? 'selected'
        : ''; ?>
>
USER
</option>

<option
    value="ADMIN"
    <?= $user['role'] === 'ADMIN'
        ? 'selected'
        : ''; ?>
>
ADMIN
</option>

</select>

</div>


<div class="form-group">

<label>
Status
</label>

<select name="status">

<option
    value="AKTIF"
    <?= $user['status'] === 'AKTIF'
        ? 'selected'
        : ''; ?>
>
AKTIF
</option>

<option
    value="NONAKTIF"
    <?= $user['status'] === 'NONAKTIF'
        ? 'selected'
        : ''; ?>
>
NONAKTIF
</option>

</select>

</div>


<div class="buttons">

<button type="submit">
Simpan Perubahan
</button>

<a href="index.php">
Batal
</a>

</div>

</form>

</div>

</div>

</body>

</html>