<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

$query = mysqli_query($conn, "
    SELECT 
        i.*,
        p.nama_profesi
    FROM oppe_indikator i
    LEFT JOIN profesi p 
        ON i.id_profesi = p.id_profesi
    ORDER BY i.id_indikator DESC
");

if (!$query) {
    die("Query indikator gagal: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Indikator OPPE</title>

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
    background: #198754;
    color: white;
    padding: 20px 30px;
}

.header h1 {
    margin: 0;
    font-size: 25px;
}

.header p {
    margin: 6px 0 0;
}

.container {
    width: 95%;
    max-width: 1400px;
    margin: 25px auto;
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 10px;
    flex-wrap: wrap;
}

.toolbar-left,
.toolbar-right {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 10px 15px;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
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

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #ddd;
}

.card-header h2 {
    margin: 0;
    font-size: 20px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px 10px;
    border-bottom: 1px solid #ddd;
    text-align: left;
    font-size: 14px;
}

th {
    background: #f1f3f5;
}

tr:hover {
    background: #f8f9fa;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
}

.badge-active {
    background: #d1e7dd;
    color: #0f5132;
}

.badge-inactive {
    background: #f8d7da;
    color: #842029;
}

.badge-all {
    background: #cff4fc;
    color: #055160;
}

.alert {
    padding: 14px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-weight: bold;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
}

.alert-danger {
    background: #f8d7da;
    color: #842029;
}

.empty {
    padding: 50px;
    text-align: center;
    color: #777;
}

.actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

@media (max-width: 700px) {

    .container {
        width: 92%;
    }

}

</style>

</head>

<body>

<div class="header">

    <h1>📋 Master Indikator OPPE</h1>

    <p>
        Indikator Evaluasi Kinerja Profesional Tenaga Kesehatan Lainnya
    </p>

</div>


<div class="container">


    <?php if ($success === '1'): ?>

        <div class="alert alert-success">
            ✅ Indikator berhasil disimpan.
        </div>

    <?php endif; ?>


    <?php if ($success === 'hapus'): ?>

        <div class="alert alert-success">
            ✅ Indikator berhasil dihapus.
        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="toolbar">

        <div class="toolbar-left">

            <a
                href="index.php"
                class="btn btn-secondary">
                🏠 OPPE
            </a>

        </div>


        <div class="toolbar-right">

            <a
                href="indikator_tambah.php"
                class="btn btn-primary">
                ➕ Tambah Indikator
            </a>

        </div>

    </div>


    <div class="card">

        <div class="card-header">

            <h2>
                Daftar Indikator OPPE
            </h2>

        </div>


        <div class="table-wrapper">

        <?php if (mysqli_num_rows($query) > 0): ?>

            <table>

                <thead>

                    <tr>

                        <th width="50">
                            No
                        </th>

                        <th>
                            Profesi
                        </th>

                        <th>
                            Indikator
                        </th>

                        <th>
                            Deskripsi
                        </th>

                        <th width="80">
                            Bobot
                        </th>

                        <th width="100">
                            Nilai Maks.
                        </th>

                        <th width="100">
                            Status
                        </th>

                        <th width="180">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                $no = 1;

                while ($row = mysqli_fetch_assoc($query)):

                ?>

                    <tr>

                        <td>
                            <?= $no++; ?>
                        </td>

                        <td>

                            <?php if (!empty($row['nama_profesi'])): ?>

                                <span class="badge badge-active">
                                    <?= htmlspecialchars($row['nama_profesi']); ?>
                                </span>

                            <?php else: ?>

                                <span class="badge badge-all">
                                    SEMUA PROFESI
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <strong>
                                <?= htmlspecialchars($row['nama_indikator']); ?>
                            </strong>

                        </td>


                        <td>

                            <?= nl2br(
                                htmlspecialchars(
                                    $row['deskripsi'] ?? '-'
                                )
                            ); ?>

                        </td>


                        <td>
                            <?= number_format(
                                (float)$row['bobot'],
                                2
                            ); ?>
                        </td>


                        <td>
                            <?= (int)$row['nilai_maksimal']; ?>
                        </td>


                        <td>

                            <?php if ($row['status_aktif'] == 1): ?>

                                <span class="badge badge-active">
                                    AKTIF
                                </span>

                            <?php else: ?>

                                <span class="badge badge-inactive">
                                    NONAKTIF
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <div class="actions">

                                <a
                                    href="indikator_edit.php?id=<?= (int)$row['id_indikator']; ?>"
                                    class="btn btn-warning">
                                    ✏️ Edit
                                </a>

                                <a
                                    href="indikator_hapus.php?id=<?= (int)$row['id_indikator']; ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Yakin ingin menghapus indikator ini?');">
                                    🗑 Hapus
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">

                <h3>
                    Belum ada indikator OPPE
                </h3>

                <p>
                    Silakan tambahkan indikator terlebih dahulu.
                </p>

                <a
                    href="indikator_tambah.php"
                    class="btn btn-primary">
                    ➕ Tambah Indikator
                </a>

            </div>

        <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>