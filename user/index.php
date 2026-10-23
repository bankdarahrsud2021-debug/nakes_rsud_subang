<?php

require_once "../config/admin.php";
require_once "../config/database.php";

$query = mysqli_query(
    $conn,
    "SELECT *
     FROM users
     ORDER BY id_user DESC"
);

if (!$query) {
    die("Query gagal: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manajemen User | RSUD Subang</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f4f7fb;
    color: #263238;
}

.container {
    max-width: 1200px;
    margin: 35px auto;
    padding: 0 20px;
}

.header {
    background: white;
    padding: 25px 30px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,.06);
    margin-bottom: 20px;
}

.header h2 {
    margin: 0 0 6px;
    color: #0d6efd;
}

.header p {
    margin: 0;
    color: #6b7280;
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.btn {
    display: inline-block;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
}

.btn-primary {
    background: #0d6efd;
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

.btn-secondary {
    background: #6c757d;
    color: white;
}

.table-card {
    background: white;
    border-radius: 14px;
    overflow-x: auto;
    box-shadow: 0 4px 15px rgba(0,0,0,.06);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f1f5f9;
    color: #374151;
    padding: 14px;
    text-align: left;
    font-size: 13px;
}

td {
    padding: 14px;
    border-top: 1px solid #e5e7eb;
    font-size: 13px;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-admin {
    background: #cfe2ff;
    color: #084298;
}

.badge-user {
    background: #e2e3e5;
    color: #41464b;
}

.badge-active {
    background: #d1e7dd;
    color: #0f5132;
}

.badge-inactive {
    background: #f8d7da;
    color: #842029;
}

.actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

</style>

</head>

<body>

<div class="container">

    <div class="header">

        <h2>Manajemen User</h2>

        <p>
            Pengelolaan akun pengguna Sistem Informasi
            Tenaga Kesehatan Lainnya RSUD Subang
        </p>

    </div>


    <div class="toolbar">

        <a
            href="tambah.php"
            class="btn btn-primary"
        >
            + Tambah User
        </a>

        <a
            href="../dashboard/index.php"
            class="btn btn-secondary"
        >
            ← Dashboard
        </a>

    </div>


    <div class="table-card">

        <table>

            <thead>

                <tr>

                    <th>No</th>

                    <th>Username</th>

                    <th>Nama User</th>

                    <th>Role</th>

                    <th>Status</th>

                    <th>Dibuat</th>

                    <th>Aksi</th>

                </tr>

            </thead>

            <tbody>

            <?php

            $no = 1;

            while ($user = mysqli_fetch_assoc($query)):

            ?>

                <tr>

                    <td>
                        <?= $no++; ?>
                    </td>

                    <td>
                        <strong>
                            <?= htmlspecialchars($user['username']); ?>
                        </strong>
                    </td>

                    <td>
                        <?= htmlspecialchars($user['nama_user']); ?>
                    </td>

                    <td>

                        <?php if ($user['role'] === 'ADMIN'): ?>

                            <span class="badge badge-admin">
                                ADMIN
                            </span>

                        <?php else: ?>

                            <span class="badge badge-user">
                                USER
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <?php if ($user['status'] === 'AKTIF'): ?>

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
                        <?= date(
                            'd-m-Y H:i',
                            strtotime($user['created_at'])
                        ); ?>
                    </td>

                    <td>

                        <div class="actions">

                            <a
                                href="edit.php?id=<?= $user['id_user']; ?>"
                                class="btn btn-warning"
                            >
                                Edit
                            </a>

                            <a
                                href="reset_password.php?id=<?= $user['id_user']; ?>"
                                class="btn btn-secondary"
                                onclick="return confirm('Reset password user ini?')"
                            >
                                Reset Password
                            </a>

                            <?php if (
                                $user['id_user'] !=
                                $_SESSION['id_user']
                            ): ?>

                                <a
                                    href="hapus.php?id=<?= $user['id_user']; ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Yakin ingin menghapus user ini?')"
                                >
                                    Hapus
                                </a>

                            <?php endif; ?>

                        </div>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>