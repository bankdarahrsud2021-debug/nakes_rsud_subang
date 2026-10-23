<?php
require_once "../config/auth.php";
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Ambil ID Kasus
|--------------------------------------------------------------------------
*/
$id_kasus = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_kasus <= 0) {
    die("ID kasus tidak valid.");
}

/*
|--------------------------------------------------------------------------
| Ambil Data Kasus
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        k.*,
        n.nama_lengkap,
        n.nomor_induk,
        n.gelar_depan,
        n.gelar_belakang,
        p.nama_profesi,
        u.nama_unit
    FROM etik_kasus k
    LEFT JOIN tenaga_kesehatan n
        ON k.id_nakes = n.id_nakes
    LEFT JOIN profesi p
        ON n.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON n.id_unit = u.id_unit
    WHERE k.id_kasus = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_kasus);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    die("Kasus tidak ditemukan. ID Kasus: " . $id_kasus);
}

/*
|--------------------------------------------------------------------------
| Ambil Data Tenaga Kesehatan Aktif
|--------------------------------------------------------------------------
*/
$sql_nakes = "
    SELECT
        n.id_nakes,
        n.nama_lengkap,
        n.gelar_depan,
        n.gelar_belakang,
        n.nomor_induk,
        p.nama_profesi,
        u.nama_unit
    FROM tenaga_kesehatan n
    LEFT JOIN profesi p
        ON n.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON n.id_unit = u.id_unit
    WHERE n.status_aktif = 'AKTIF'
    ORDER BY n.nama_lengkap ASC
";

$result_nakes = mysqli_query($conn, $sql_nakes);

if (!$result_nakes) {
    die("Gagal mengambil data tenaga kesehatan: " . mysqli_error($conn));
}

function h($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Kasus Etik</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #f4f6f9;
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .header {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .header h2 {
            margin: 0 0 8px 0;
        }

        .header p {
            margin: 0;
            color: #666;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 14px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .info-nakes {
            margin-top: 10px;
            padding: 12px 15px;
            background: #f1f8ff;
            border-left: 4px solid #3498db;
            border-radius: 5px;
            display: none;
        }

        .info-nakes strong {
            display: block;
            margin-bottom: 5px;
        }

        .buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 7px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-secondary {
            background: #7f8c8d;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn:hover {
            opacity: .9;
        }

        .status-info {
            background: #fff8e1;
            border-left: 4px solid #f39c12;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h2>✏️ Edit Kasus Etik</h2>
        <p>Sub Komite Etik RSUD Subang</p>
    </div>

    <div class="status-info">
        <strong>Nomor Kasus:</strong>
        <?= h($data['nomor_kasus']); ?>

        <br>

        <strong>Status Saat Ini:</strong>
        <?= h($data['status_kasus']); ?>

        <br><br>

        Status proses tidak diubah melalui halaman ini.
        Perubahan status dilakukan melalui menu <strong>Proses Etik</strong>.
    </div>

    <form action="update.php" method="POST">

        <input type="hidden" name="id_kasus" value="<?= (int)$data['id_kasus']; ?>">

        <div class="card">

            <div class="card-title">
                📋 Identitas Kasus
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label>Nomor Kasus *</label>

                    <input
                        type="text"
                        name="nomor_kasus"
                        value="<?= h($data['nomor_kasus']); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Tanggal Laporan *</label>

                    <input
                        type="date"
                        name="tanggal_laporan"
                        value="<?= h($data['tanggal_laporan']); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Nama Tenaga Kesehatan</label>

                    <select name="id_nakes" id="id_nakes">

                        <option value="">-- Tidak terkait Nakes tertentu --</option>

                        <?php while ($nakes = mysqli_fetch_assoc($result_nakes)): ?>

                            <?php
                            $nama_nakes = trim(
                                ($nakes['gelar_depan'] ? $nakes['gelar_depan'] . ' ' : '') .
                                $nakes['nama_lengkap'] .
                                ($nakes['gelar_belakang'] ? ', ' . $nakes['gelar_belakang'] : '')
                            );
                            ?>

                            <option
                                value="<?= (int)$nakes['id_nakes']; ?>"
                                data-nama="<?= h($nama_nakes); ?>"
                                data-nomor="<?= h($nakes['nomor_induk']); ?>"
                                data-profesi="<?= h($nakes['nama_profesi']); ?>"
                                data-unit="<?= h($nakes['nama_unit']); ?>"
                                <?= ((int)$data['id_nakes'] === (int)$nakes['id_nakes']) ? 'selected' : ''; ?>
                            >
                                <?= h($nama_nakes); ?>
                                <?php if (!empty($nakes['nomor_induk'])): ?>
                                    - <?= h($nakes['nomor_induk']); ?>
                                <?php endif; ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                    <div id="infoNakes" class="info-nakes">
                        <strong id="infoNama"></strong>
                        <span id="infoNomor"></span><br>
                        <span id="infoProfesi"></span><br>
                        <span id="infoUnit"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pelapor *</label>

                    <input
                        type="text"
                        name="pelapor"
                        value="<?= h($data['pelapor']); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Hubungan Pelapor</label>

                    <input
                        type="text"
                        name="hubungan_pelapor"
                        value="<?= h($data['hubungan_pelapor']); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Jenis Masalah *</label>

                    <input
                        type="text"
                        name="jenis_masalah"
                        value="<?= h($data['jenis_masalah']); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Klasifikasi Etik</label>

                    <input
                        type="text"
                        name="klasifikasi_etik"
                        value="<?= h($data['klasifikasi_etik']); ?>"
                        placeholder="Contoh: Etika Profesi"
                    >
                </div>

                <div class="form-group">
                    <label>Sumber Laporan</label>

                    <select name="sumber_laporan">

                        <option value="">-- Pilih --</option>

                        <option value="Pengaduan Pasien"
                            <?= $data['sumber_laporan'] === 'Pengaduan Pasien' ? 'selected' : ''; ?>>
                            Pengaduan Pasien
                        </option>

                        <option value="Pengaduan Keluarga"
                            <?= $data['sumber_laporan'] === 'Pengaduan Keluarga' ? 'selected' : ''; ?>>
                            Pengaduan Keluarga
                        </option>

                        <option value="Laporan Unit"
                            <?= $data['sumber_laporan'] === 'Laporan Unit' ? 'selected' : ''; ?>>
                            Laporan Unit
                        </option>

                        <option value="Laporan Manajemen"
                            <?= $data['sumber_laporan'] === 'Laporan Manajemen' ? 'selected' : ''; ?>>
                            Laporan Manajemen
                        </option>

                        <option value="Lainnya"
                            <?= $data['sumber_laporan'] === 'Lainnya' ? 'selected' : ''; ?>>
                            Lainnya
                        </option>

                    </select>
                </div>

                <div class="form-group">
                    <label>Tingkat Kasus</label>

                    <select name="tingkat_kasus">

                        <option value="">-- Pilih --</option>

                        <option value="Ringan"
                            <?= $data['tingkat_kasus'] === 'Ringan' ? 'selected' : ''; ?>>
                            Ringan
                        </option>

                        <option value="Sedang"
                            <?= $data['tingkat_kasus'] === 'Sedang' ? 'selected' : ''; ?>>
                            Sedang
                        </option>

                        <option value="Berat"
                            <?= $data['tingkat_kasus'] === 'Berat' ? 'selected' : ''; ?>>
                            Berat
                        </option>

                    </select>
                </div>

            </div>

        </div>

        <div class="card">

            <div class="card-title">
                📝 Uraian Kasus
            </div>

            <div class="form-group">

                <label>Uraian Kasus *</label>

                <textarea
                    name="uraian_kasus"
                    required
                ><?= h($data['uraian_kasus']); ?></textarea>

            </div>

        </div>

        <div class="card">

            <div class="card-title">
                🔎 Hasil Telaah dan Tindak Lanjut
            </div>

            <div class="form-grid">

                <div class="form-group full">

                    <label>Hasil Telaah Awal</label>

                    <textarea
                        name="hasil_telaah"
                    ><?= h($data['hasil_telaah']); ?></textarea>

                </div>

                <div class="form-group full">

                    <label>Rekomendasi</label>

                    <textarea
                        name="rekomendasi"
                    ><?= h($data['rekomendasi']); ?></textarea>

                </div>

                <div class="form-group full">

                    <label>Keputusan</label>

                    <textarea
                        name="keputusan"
                    ><?= h($data['keputusan']); ?></textarea>

                </div>

                <div class="form-group full">

                    <label>Catatan</label>

                    <textarea
                        name="catatan"
                    ><?= h($data['catatan']); ?></textarea>

                </div>

            </div>

        </div>

        <div class="card">

            <div class="buttons">

                <button type="submit" class="btn btn-success">
                    💾 Simpan Perubahan
                </button>

                <a
                    href="detail.php?id=<?= (int)$data['id_kasus']; ?>"
                    class="btn btn-secondary"
                >
                    ↩ Kembali ke Detail
                </a>

                <a
                    href="proses.php?id=<?= (int)$data['id_kasus']; ?>"
                    class="btn btn-warning"
                >
                    🔄 Proses Etik
                </a>

                <a
                    href="dokumen.php?id=<?= (int)$data['id_kasus']; ?>"
                    class="btn btn-primary"
                >
                    📁 Dokumen
                </a>

            </div>

        </div>

    </form>

</div>

<script>

function tampilkanNakes() {

    const select = document.getElementById('id_nakes');
    const option = select.options[select.selectedIndex];

    const info = document.getElementById('infoNakes');

    if (!select.value) {

        info.style.display = 'none';

        return;
    }

    document.getElementById('infoNama').innerHTML =
        option.getAttribute('data-nama') || '';

    document.getElementById('infoNomor').innerHTML =
        'Nomor Induk: ' +
        (option.getAttribute('data-nomor') || '-');

    document.getElementById('infoProfesi').innerHTML =
        'Profesi: ' +
        (option.getAttribute('data-profesi') || '-');

    document.getElementById('infoUnit').innerHTML =
        'Unit: ' +
        (option.getAttribute('data-unit') || '-');

    info.style.display = 'block';
}

document.getElementById('id_nakes').addEventListener(
    'change',
    tampilkanNakes
);

tampilkanNakes();

</script>

</body>
</html>