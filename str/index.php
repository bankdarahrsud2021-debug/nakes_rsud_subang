<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$role = $_SESSION['role'] ?? 'ANGGOTA';

/*
|--------------------------------------------------------------------------
| AMBIL DATA STR
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        s.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi,
        u.nama_unit,
        usr.nama_user AS nama_verifikator
    FROM str_nakes s
    INNER JOIN tenaga_kesehatan t
        ON s.id_nakes = t.id_nakes
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
    LEFT JOIN users usr
        ON s.verified_by = usr.id_user
    ORDER BY s.tanggal_berakhir ASC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query gagal: " . mysqli_error($conn));
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Data STR Tenaga Kesehatan</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 25px;
    background: #f4f6f9;
    font-family: Arial, sans-serif;
    color: #333;
}

.container {
    width: 100%;
    max-width: 1600px;
    margin: auto;
}

.header {
    background: #ffffff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.header h2 {
    margin: 0 0 15px 0;
    color: #1f4e79;
}

.nav {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.nav a {
    display: inline-block;
    padding: 9px 14px;
    background: #1f4e79;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
}

.nav a:hover {
    background: #163a5c;
}

.card {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.btn-add {
    display: inline-block;
    padding: 10px 15px;
    background: #198754;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 15px;
}

.btn-add:hover {
    background: #146c43;
}

.alert {
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
}

.alert-error {
    background: #f8d7da;
    color: #842029;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1250px;
}

th {
    background: #1f4e79;
    color: white;
    padding: 10px 8px;
    border: 1px solid #ddd;
    font-size: 13px;
    white-space: nowrap;
}

td {
    padding: 9px 8px;
    border: 1px solid #ddd;
    vertical-align: middle;
    font-size: 13px;
}

tr:nth-child(even) {
    background: #f8f9fa;
}

.badge {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 5px;
    font-size: 11px;
    font-weight: bold;
    white-space: nowrap;
}

/* Status masa berlaku */

.badge-active {
    background: #d1e7dd;
    color: #0f5132;
}

.badge-warning {
    background: #fff3cd;
    color: #664d03;
}

.badge-danger {
    background: #f8d7da;
    color: #842029;
}

/* Status verifikasi */

.badge-success {
    background: #d1e7dd;
    color: #0f5132;
}

.badge-rejected {
    background: #f8d7da;
    color: #842029;
}

.badge-pending {
    background: #fff3cd;
    color: #664d03;
}

.action {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.btn-small {
    display: inline-block;
    padding: 6px 8px;
    border-radius: 4px;
    text-decoration: none;
    color: white;
    font-size: 11px;
    white-space: nowrap;
}

.btn-view {
    background: #0d6efd;
}

.btn-view:hover {
    background: #0b5ed7;
}

.btn-edit {
    background: #ffc107;
    color: #212529;
}

.btn-edit:hover {
    background: #ffca2c;
}

.btn-delete {
    background: #dc3545;
}

.btn-delete:hover {
    background: #bb2d3b;
}

.document-link {
    color: #0d6efd;
    text-decoration: none;
}

.document-link:hover {
    text-decoration: underline;
}

.empty {
    text-align: center;
    padding: 30px;
    color: #777;
}

</style>

</head>

<body>

<div class="container">

    <div class="header">

        <h2>📋 Data STR Tenaga Kesehatan</h2>

        <div class="nav">

            <a href="../dashboard/index.php">
                🏠 Dashboard
            </a>

            <a href="../nakes/index.php">
                👨‍⚕️ Data Tenaga Kesehatan
            </a>


            <?php if (
                $role === 'ADMIN' ||
                $role === 'PENGURUS'
            ): ?>

                <a href="tambah.php">
                    ➕ Tambah STR
                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- PESAN SUKSES -->

    <?php if (isset($_GET['success'])): ?>

        <?php if ($_GET['success'] === 'tambah'): ?>

            <div class="alert alert-success">
                ✅ Data STR berhasil ditambahkan.
            </div>

        <?php elseif ($_GET['success'] === 'update'): ?>

            <div class="alert alert-success">
                ✅ Data STR berhasil diperbarui.
            </div>

        <?php elseif ($_GET['success'] === 'hapus'): ?>

            <div class="alert alert-success">
                ✅ Data STR berhasil dihapus.
            </div>

        <?php elseif ($_GET['success'] === 'verifikasi'): ?>

            <div class="alert alert-success">
                ✅ Verifikasi STR berhasil disimpan.
            </div>

        <?php endif; ?>

    <?php endif; ?>


    <!-- PESAN ERROR -->

    <?php if (isset($_GET['error'])): ?>

        <div class="alert alert-error">

            <?php

            switch ($_GET['error']) {

                case 'id':
                    echo "❌ ID STR tidak valid.";
                    break;

                case 'notfound':
                    echo "❌ Data STR tidak ditemukan.";
                    break;

                case 'hapus':
                    echo "❌ Data STR gagal dihapus.";
                    break;

                default:
                    echo "❌ Terjadi kesalahan dalam proses.";
                    break;
            }

            ?>

        </div>

    <?php endif; ?>


    <div class="card">


        <?php if (
            $role === 'ADMIN' ||
            $role === 'PENGURUS'
        ): ?>

            <a href="tambah.php" class="btn-add">
                ➕ Tambah Data STR
            </a>

        <?php endif; ?>


        <?php if (mysqli_num_rows($result) > 0): ?>

        <table>

            <thead>

                <tr>

                    <th>No</th>

                    <th>Nama Tenaga Kesehatan</th>

                    <th>Profesi</th>

                    <th>Unit Kerja</th>

                    <th>Jenis STR</th>

                    <th>Nomor STR</th>

                    <th>Tanggal Terbit</th>

                    <th>Tanggal Berakhir</th>

                    <th>Status Masa Berlaku</th>

                    <th>Status Verifikasi</th>

                    <th>Diverifikasi Oleh</th>

                    <th>Tanggal Verifikasi</th>

                    <th>Dokumen</th>


                    <?php if (
                        $role === 'ADMIN' ||
                        $role === 'PENGURUS'
                    ): ?>

                        <th>Aksi</th>

                    <?php endif; ?>

                </tr>

            </thead>

            <tbody>

            <?php

            $no = 1;

            while ($row = mysqli_fetch_assoc($result)):

                /*
                |--------------------------------------------------------------------------
                | STATUS MASA BERLAKU
                |--------------------------------------------------------------------------
                */

                $status_masa_berlaku = strtoupper(
                    trim($row['status'] ?? '')
                );

                $badge_masa_berlaku = 'badge-active';

                if ($status_masa_berlaku === 'KADALUARSA') {

                    $badge_masa_berlaku = 'badge-danger';

                } elseif (
                    $status_masa_berlaku === 'AKAN BERAKHIR'
                ) {

                    $badge_masa_berlaku = 'badge-warning';

                } else {

                    if (!empty($row['tanggal_berakhir'])) {

                        $today = new DateTime();

                        $expired = new DateTime(
                            $row['tanggal_berakhir']
                        );

                        $selisih = (int)$today
                            ->diff($expired)
                            ->format('%r%a');

                        if ($selisih < 0) {

                            $status_masa_berlaku = 'KADALUARSA';

                            $badge_masa_berlaku =
                                'badge-danger';

                        } elseif ($selisih <= 90) {

                            $status_masa_berlaku =
                                'AKAN BERAKHIR';

                            $badge_masa_berlaku =
                                'badge-warning';

                        } else {

                            $status_masa_berlaku =
                                'AKTIF';

                            $badge_masa_berlaku =
                                'badge-active';
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | STATUS VERIFIKASI
                |--------------------------------------------------------------------------
                */

                $status_verifikasi = strtoupper(
                    trim($row['status_verifikasi'] ?? '')
                );

                if (
                    $status_verifikasi === 'TERVERIFIKASI' ||
                    $status_verifikasi === 'VERIFIED'
                ) {

                    $label_verifikasi =
                        'TERVERIFIKASI';

                    $badge_verifikasi =
                        'badge-success';

                } elseif (
                    $status_verifikasi === 'DITOLAK'
                ) {

                    $label_verifikasi =
                        'DITOLAK';

                    $badge_verifikasi =
                        'badge-rejected';

                } else {

                    $label_verifikasi =
                        'BELUM DIVERIFIKASI';

                    $badge_verifikasi =
                        'badge-pending';
                }

            ?>

                <tr>

                    <td>
                        <?= $no++; ?>
                    </td>


                    <td>

                        <?= htmlspecialchars(
                            $row['gelar_depan'] ?? ''
                        ); ?>

                        <?= htmlspecialchars(
                            $row['nama_lengkap'] ?? ''
                        ); ?>

                        <?= htmlspecialchars(
                            $row['gelar_belakang'] ?? ''
                        ); ?>

                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $row['nama_profesi'] ?? '-'
                        ); ?>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $row['nama_unit'] ?? '-'
                        ); ?>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $row['jenis_str'] ?? '-'
                        ); ?>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $row['nomor_str'] ?? '-'
                        ); ?>
                    </td>


                    <td>

                        <?= !empty($row['tanggal_terbit'])
                            ? date(
                                'd-m-Y',
                                strtotime(
                                    $row['tanggal_terbit']
                                )
                            )
                            : '-';
                        ?>

                    </td>


                    <td>

                        <?= !empty($row['tanggal_berakhir'])
                            ? date(
                                'd-m-Y',
                                strtotime(
                                    $row['tanggal_berakhir']
                                )
                            )
                            : '-';
                        ?>

                    </td>


                    <td>

                        <span class="badge <?= $badge_masa_berlaku; ?>">

                            <?= htmlspecialchars(
                                $status_masa_berlaku
                            ); ?>

                        </span>

                    </td>


                    <td>

                        <span class="badge <?= $badge_verifikasi; ?>">

                            <?= htmlspecialchars(
                                $label_verifikasi
                            ); ?>

                        </span>

                    </td>


                    <td>

                        <?= !empty($row['nama_verifikator'])
                            ? htmlspecialchars(
                                $row['nama_verifikator']
                            )
                            : '-';
                        ?>

                    </td>


                    <td>

                        <?= !empty($row['verified_at'])
                            ? date(
                                'd-m-Y H:i',
                                strtotime(
                                    $row['verified_at']
                                )
                            )
                            : '-';
                        ?>

                    </td>


                    <td>

                        <?php if (!empty($row['file_str'])): ?>

                            <a
                                href="../uploads/str_nakes/<?= urlencode($row['file_str']); ?>"
                                target="_blank"
                                class="document-link"
                            >
                                📄 Lihat
                            </a>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </td>


                    <?php if (
                        $role === 'ADMIN' ||
                        $role === 'PENGURUS'
                    ): ?>

                        <td>

                            <div class="action">

                                <a
                                    href="verifikasi.php?id=<?= (int)$row['id_str']; ?>"
                                    class="btn-small btn-view"
                                >
                                    🔎 Verifikasi
                                </a>

                                <a
                                    href="edit.php?id=<?= (int)$row['id_str']; ?>"
                                    class="btn-small btn-edit"
                                >
                                    ✏ Edit
                                </a>

                                <a
                                    href="hapus.php?id=<?= (int)$row['id_str']; ?>"
                                    class="btn-small btn-delete"
                                    onclick="return confirm('Yakin ingin menghapus data STR ini?');"
                                >
                                    🗑 Hapus
                                </a>

                            </div>

                        </td>

                    <?php endif; ?>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

        <?php else: ?>

            <div class="empty">
                Belum ada data STR.
            </div>

        <?php endif; ?>

    </div>

</div>

</body>

</html>