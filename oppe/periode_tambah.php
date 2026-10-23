<?php

require_once "../config/auth.php";
require_once "../config/database.php";

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Tambah Periode OPPE</title>

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

.header {
    background: #0d6efd;
    color: white;
    padding: 20px 30px;
}

.header h1 {
    margin: 0;
    font-size: 24px;
}

.header p {
    margin: 6px 0 0;
}

.container {
    width: 95%;
    max-width: 900px;
    margin: 30px auto;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 25px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    margin-bottom: 7px;
    font-weight: bold;
}

input,
select,
textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.btn {
    display: inline-block;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
}

.btn-primary {
    background: #0d6efd;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.actions {
    margin-top: 25px;
    display: flex;
    gap: 10px;
}

.info {
    background: #e7f1ff;
    border-left: 4px solid #0d6efd;
    padding: 12px 15px;
    margin-bottom: 20px;
    font-size: 14px;
}

@media (max-width: 600px) {

    .row {
        grid-template-columns: 1fr;
    }

    .container {
        width: 92%;
    }

}

</style>

</head>

<body>

<div class="header">

    <h1>➕ Tambah Periode OPPE</h1>

    <p>
        Evaluasi Kinerja Profesional Tenaga Kesehatan Lainnya
    </p>

</div>


<div class="container">

    <div class="card">

        <div class="info">

            <strong>Informasi:</strong><br>

            Periode OPPE digunakan untuk menentukan rentang waktu
            pelaksanaan evaluasi kinerja tenaga kesehatan.

            Contoh: Januari–Juni 2026 atau Juli–Desember 2026.

        </div>


        <form action="periode_simpan.php" method="POST">


            <div class="form-group">

                <label>
                    Nama Periode OPPE *
                </label>

                <input
                    type="text"
                    name="nama_periode"
                    placeholder="Contoh: OPPE Semester I Tahun 2026"
                    required
                >

            </div>


            <div class="row">

                <div class="form-group">

                    <label>
                        Tanggal Mulai *
                    </label>

                    <input
                        type="date"
                        name="tanggal_mulai"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Tanggal Selesai *
                    </label>

                    <input
                        type="date"
                        name="tanggal_selesai"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label>
                    Status *
                </label>

                <select name="status" required>

                    <option value="DRAFT">
                        DRAFT
                    </option>

                    <option value="AKTIF">
                        AKTIF
                    </option>

                    <option value="SELESAI">
                        SELESAI
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Keterangan
                </label>

                <textarea
                    name="keterangan"
                    placeholder="Keterangan periode OPPE..."
                ></textarea>

            </div>


            <div class="actions">

                <button
                    type="submit"
                    class="btn btn-primary">
                    💾 Simpan Periode
                </button>

                <a
                    href="index.php"
                    class="btn btn-secondary">
                    ↩ Kembali
                </a>

            </div>


        </form>

    </div>

</div>

</body>

</html>