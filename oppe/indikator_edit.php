<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID indikator tidak valid.");
}

$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM oppe_indikator
    WHERE id_indikator = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    die("Data indikator tidak ditemukan.");
}

$data = mysqli_fetch_assoc($result);

$query_profesi = mysqli_query($conn, "
    SELECT id_profesi, kode_profesi, nama_profesi
    FROM profesi
    ORDER BY nama_profesi
");

if (!$query_profesi) {
    die("Query profesi gagal: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Indikator OPPE</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
}

.header {
    background: #198754;
    color: white;
    padding: 20px 30px;
}

.header h1 {
    margin: 0;
}

.container {
    width: 95%;
    max-width: 900px;
    margin: 30px auto;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
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

.info {
    background: #fff3cd;
    color: #664d03;
    padding: 14px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary {
    background: #0d6efd;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

</style>

</head>

<body>

<div class="header">

    <h1>✏️ Edit Indikator OPPE</h1>

</div>


<div class="container">

<div class="card">

<div class="info">

    Anda sedang mengubah indikator OPPE.
    Perubahan akan digunakan pada penilaian berikutnya.

</div>


<form action="indikator_update.php" method="POST">

    <input
        type="hidden"
        name="id_indikator"
        value="<?= (int)$data['id_indikator']; ?>"
    >


    <div class="form-group">

        <label>
            Profesi *
        </label>

        <select name="id_profesi" required>

            <option value="0"
                <?= ((int)$data['id_profesi'] === 0)
                    ? 'selected'
                    : ''; ?>>

                SEMUA PROFESI

            </option>


            <?php while ($profesi = mysqli_fetch_assoc($query_profesi)): ?>

                <option
                    value="<?= (int)$profesi['id_profesi']; ?>"
                    <?= ((int)$data['id_profesi'] === (int)$profesi['id_profesi'])
                        ? 'selected'
                        : ''; ?>
                >

                    <?= htmlspecialchars(
                        $profesi['kode_profesi']
                    ); ?>

                    -

                    <?= htmlspecialchars(
                        $profesi['nama_profesi']
                    ); ?>

                </option>

            <?php endwhile; ?>

        </select>

    </div>


    <div class="form-group">

        <label>
            Nama Indikator *
        </label>

        <input
            type="text"
            name="nama_indikator"
            value="<?= htmlspecialchars(
                $data['nama_indikator']
            ); ?>"
            required
        >

    </div>


    <div class="form-group">

        <label>
            Deskripsi
        </label>

        <textarea
            name="deskripsi"
        ><?= htmlspecialchars(
            $data['deskripsi'] ?? ''
        ); ?></textarea>

    </div>


    <div class="row">

        <div class="form-group">

            <label>
                Bobot *
            </label>

            <input
                type="number"
                name="bobot"
                value="<?= htmlspecialchars(
                    $data['bobot']
                ); ?>"
                min="0.01"
                step="0.01"
                required
            >

        </div>


        <div class="form-group">

            <label>
                Nilai Maksimal *
            </label>

            <select name="nilai_maksimal" required>

                <?php foreach ([4,5,10,100] as $nilai): ?>

                    <option
                        value="<?= $nilai; ?>"
                        <?= ((int)$data['nilai_maksimal'] === $nilai)
                            ? 'selected'
                            : ''; ?>
                    >

                        <?= $nilai; ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

    </div>


    <div class="form-group">

        <label>
            Status *
        </label>

        <select name="status_aktif" required>

            <option
                value="1"
                <?= ((int)$data['status_aktif'] === 1)
                    ? 'selected'
                    : ''; ?>
            >
                AKTIF
            </option>

            <option
                value="0"
                <?= ((int)$data['status_aktif'] === 0)
                    ? 'selected'
                    : ''; ?>
            >
                NONAKTIF
            </option>

        </select>

    </div>


    <div class="actions">

        <button
            type="submit"
            class="btn btn-primary">

            💾 Update Indikator

        </button>


        <a
            href="indikator.php"
            class="btn btn-secondary">

            ↩ Kembali

        </a>

    </div>

</form>

</div>

</div>

</body>

</html>