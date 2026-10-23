<?php

require_once "../config/admin.php";

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Tambah User | RSUD Subang</title>

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
    box-shadow: 0 0 0 3px rgba(13,110,253,.1);
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

<h2>Tambah User</h2>

<p>
Buat akun baru untuk pengguna sistem.
</p>

<form
    method="POST"
    action="simpan.php"
>

<div class="form-group">

<label>
Username
</label>

<input
    type="text"
    name="username"
    required
    maxlength="50"
    placeholder="Masukkan username"
>

</div>


<div class="form-group">

<label>
Nama User
</label>

<input
    type="text"
    name="nama_user"
    required
    maxlength="150"
    placeholder="Nama lengkap pengguna"
>

</div>


<div class="form-group">

<label>
Password
</label>

<input
    type="password"
    name="password"
    required
    minlength="6"
    placeholder="Minimal 6 karakter"
>

</div>


<div class="form-group">

<label>
Role
</label>

<select name="role" class="form-control" required>
    <option value="">-- Pilih Role --</option>
    <option value="ADMIN">ADMIN</option>
    <option value="PENGURUS">PENGURUS</option>
    <option value="ANGGOTA">ANGGOTA</option>
</select>

</div>


<div class="form-group">

<label>
Status
</label>

<select name="status">

<option value="AKTIF">
AKTIF
</option>

<option value="NONAKTIF">
NONAKTIF
</option>

</select>

</div>


<div class="buttons">

<button type="submit">
Simpan User
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