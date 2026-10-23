<?php

require_once "../config/auth.php";
require_once "../config/database.php";

/* =========================================================
   AMBIL DATA TENAGA KESEHATAN
   ========================================================= */

$sql = "
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

$result_nakes = mysqli_query($conn, $sql);

if (!$result_nakes) {
    die("Query tenaga kesehatan gagal: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrasi Kasus Etik - RSUD Subang</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .header {
            background: white;
            padding: 22px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .header h2 {
            margin: 0 0 7px 0;
        }

        .header p {
            margin: 4px 0;
            color: #666;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 25px;
        }

        .section-title {
            font-size: 17px;
            font-weight: bold;
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
            font-size: 14px;
        }

        .required {
            color: #dc3545;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #86b7fe;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.12);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .nakes-info {
            display: none;
            background: #e7f1ff;
            border: 1px solid #b6d4fe;
            color: #084298;
            padding: 14px;
            border-radius: 7px;
            margin-top: 10px;
        }

        .nakes-info strong {
            display: inline-block;
            min-width: 100px;
        }

        .help {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }

        .alert {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            color: #664d03;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .buttons {
            display: flex;
            gap: 8px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-save {
            background: #198754;
        }

        .btn-back {
            background: #6c757d;
        }

        .btn:hover {
            opacity: 0.85;
        }

        @media(max-width:700px) {

            body {
                padding: 10px;
            }

            .row {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 18px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <!-- HEADER -->

    <div class="header">

        <h2>⚖️ Registrasi Kasus Etik</h2>

        <p>
            Sub Komite Etik - RSUD Subang
        </p>

        <p>
            Form registrasi laporan/kasus etik tenaga kesehatan.
        </p>

    </div>


    <!-- FORM -->

    <div class="card">

        <div class="alert">

            <strong>Perhatian:</strong>
            Pastikan data laporan dan identitas tenaga kesehatan
            diisi dengan benar. Data ini menjadi dasar proses
            telaah dan pemeriksaan etik.

        </div>


        <form
            action="simpan.php"
            method="POST"
            onsubmit="return validasiForm();"
        >


            <!-- =================================================
                 IDENTITAS KASUS
                 ================================================= -->

            <div class="section-title">
                A. Identitas Kasus
            </div>


            <div class="row">


                <div class="form-group">

                    <label>
                        Nomor Kasus
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="nomor_kasus"
                        placeholder="Contoh: ETK/001/RSUD/2026"
                        required
                    >

                    <div class="help">
                        Nomor registrasi kasus etik.
                    </div>

                </div>


                <div class="form-group">

                    <label>
                        Tanggal Laporan
                        <span class="required">*</span>
                    </label>

                    <input
                        type="date"
                        name="tanggal_laporan"
                        value="<?= date('Y-m-d'); ?>"
                        required
                    >

                </div>

            </div>


            <!-- =================================================
                 TENAGA KESEHATAN
                 ================================================= -->

            <div class="form-group">

                <label>
                    Tenaga Kesehatan yang Dilaporkan
                    <span class="required">*</span>
                </label>


                <select
                    name="id_nakes"
                    id="id_nakes"
                    required
                    onchange="tampilkanNakes();"
                >

                    <option value="">
                        -- Pilih Tenaga Kesehatan --
                    </option>


                    <?php while ($nakes = mysqli_fetch_assoc($result_nakes)): ?>

                        <?php

                        $nama = trim(
                            ($nakes['gelar_depan'] ?? '') .
                            ' ' .
                            ($nakes['nama_lengkap'] ?? '') .
                            ' ' .
                            ($nakes['gelar_belakang'] ?? '')
                        );

                        ?>

                        <option
                            value="<?= (int)$nakes['id_nakes']; ?>"
                            data-nama="<?= htmlspecialchars($nama, ENT_QUOTES); ?>"
                            data-profesi="<?= htmlspecialchars($nakes['nama_profesi'] ?? '-', ENT_QUOTES); ?>"
                            data-unit="<?= htmlspecialchars($nakes['nama_unit'] ?? '-', ENT_QUOTES); ?>"
                            data-induk="<?= htmlspecialchars($nakes['nomor_induk'] ?? '-', ENT_QUOTES); ?>"
                        >

                            <?= htmlspecialchars($nama); ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <!-- INFORMASI NAKES -->

                <div
                    class="nakes-info"
                    id="nakesInfo"
                >

                    <div>
                        <strong>Nama:</strong>
                        <span id="infoNama">-</span>
                    </div>

                    <div>
                        <strong>No. Induk:</strong>
                        <span id="infoInduk">-</span>
                    </div>

                    <div>
                        <strong>Profesi:</strong>
                        <span id="infoProfesi">-</span>
                    </div>

                    <div>
                        <strong>Unit Kerja:</strong>
                        <span id="infoUnit">-</span>
                    </div>

                </div>

            </div>


            <!-- =================================================
                 PELAPOR
                 ================================================= -->

            <div class="section-title">
                B. Data Pelapor
            </div>


            <div class="row">


                <div class="form-group">

                    <label>
                        Nama Pelapor
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        name="pelapor"
                        placeholder="Nama pelapor"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Hubungan Pelapor
                    </label>

                    <select name="hubungan_pelapor">

                        <option value="">
                            -- Pilih --
                        </option>

                        <option value="Pasien">
                            Pasien
                        </option>

                        <option value="Keluarga Pasien">
                            Keluarga Pasien
                        </option>

                        <option value="Tenaga Kesehatan">
                            Tenaga Kesehatan
                        </option>

                        <option value="Atasan">
                            Atasan
                        </option>

                        <option value="Manajemen Rumah Sakit">
                            Manajemen Rumah Sakit
                        </option>

                        <option value="Komite">
                            Komite
                        </option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>

                </div>

            </div>


            <!-- =================================================
                 KLASIFIKASI KASUS
                 ================================================= -->

            <div class="section-title">
                C. Klasifikasi Masalah Etik
            </div>


            <div class="row">


                <div class="form-group">

                    <label>
                        Jenis Masalah
                        <span class="required">*</span>
                    </label>

                    <select
                        name="jenis_masalah"
                        required
                    >

                        <option value="">
                            -- Pilih Jenis Masalah --
                        </option>

                        <option value="Pelanggaran Etik Profesi">
                            Pelanggaran Etik Profesi
                        </option>

                        <option value="Pelanggaran Disiplin">
                            Pelanggaran Disiplin
                        </option>

                        <option value="Konflik Kepentingan">
                            Konflik Kepentingan
                        </option>

                        <option value="Kerahasiaan Pasien">
                            Kerahasiaan Pasien
                        </option>

                        <option value="Hak Pasien">
                            Hak Pasien
                        </option>

                        <option value="Komunikasi">
                            Komunikasi
                        </option>

                        <option value="Perilaku Profesional">
                            Perilaku Profesional
                        </option>

                        <option value="Hubungan Antarprofesi">
                            Hubungan Antarprofesi
                        </option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Klasifikasi Etik
                    </label>

                    <select name="klasifikasi_etik">

                        <option value="">
                            -- Pilih Klasifikasi --
                        </option>

                        <option value="Etik Profesional">
                            Etik Profesional
                        </option>

                        <option value="Etik Pelayanan">
                            Etik Pelayanan
                        </option>

                        <option value="Etik Klinis">
                            Etik Klinis
                        </option>

                        <option value="Etik Administratif">
                            Etik Administratif
                        </option>

                        <option value="Etik Antarprofesi">
                            Etik Antarprofesi
                        </option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>

                </div>

            </div>


            <div class="row">


                <div class="form-group">

                    <label>
                        Sumber Laporan
                    </label>

                    <select name="sumber_laporan">

                        <option value="">
                            -- Pilih --
                        </option>

                        <option value="Pengaduan Pasien">
                            Pengaduan Pasien
                        </option>

                        <option value="Pengaduan Keluarga">
                            Pengaduan Keluarga
                        </option>

                        <option value="Laporan Unit">
                            Laporan Unit
                        </option>

                        <option value="Laporan Komite">
                            Laporan Komite
                        </option>

                        <option value="Laporan Manajemen">
                            Laporan Manajemen
                        </option>

                        <option value="Audit Internal">
                            Audit Internal
                        </option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Tingkat Kasus
                    </label>

                    <select name="tingkat_kasus">

                        <option value="">
                            -- Pilih Tingkat --
                        </option>

                        <option value="RENDAH">
                            Rendah
                        </option>

                        <option value="SEDANG">
                            Sedang
                        </option>

                        <option value="TINGGI">
                            Tinggi
                        </option>

                    </select>

                </div>

            </div>


            <!-- =================================================
                 URAIAN
                 ================================================= -->

            <div class="section-title">
                D. Uraian Kasus
            </div>


            <div class="form-group">

                <label>
                    Uraian Kejadian
                    <span class="required">*</span>
                </label>

                <textarea
                    name="uraian_kasus"
                    placeholder="Tuliskan kronologi atau uraian kejadian secara objektif..."
                    required
                ></textarea>

                <div class="help">
                    Hindari kesimpulan atau tuduhan. Tuliskan fakta/kejadian
                    yang dilaporkan secara objektif.
                </div>

            </div>


            <div class="form-group">

                <label>
                    Catatan Awal
                </label>

                <textarea
                    name="catatan"
                    placeholder="Catatan tambahan bila diperlukan..."
                ></textarea>

            </div>


            <!-- =================================================
                 TOMBOL
                 ================================================= -->

            <div class="buttons">

                <a
                    href="index.php"
                    class="btn btn-back"
                >
                    ← Batal
                </a>


                <button
                    type="submit"
                    class="btn btn-save"
                >
                    💾 Simpan Registrasi
                </button>

            </div>


        </form>

    </div>

</div>


<script>

function tampilkanNakes()
{

    const select =
        document.getElementById("id_nakes");

    const option =
        select.options[select.selectedIndex];

    const info =
        document.getElementById("nakesInfo");


    if (!select.value) {

        info.style.display = "none";

        return;
    }


    document.getElementById("infoNama").innerText =
        option.dataset.nama || "-";


    document.getElementById("infoInduk").innerText =
        option.dataset.induk || "-";


    document.getElementById("infoProfesi").innerText =
        option.dataset.profesi || "-";


    document.getElementById("infoUnit").innerText =
        option.dataset.unit || "-";


    info.style.display = "block";
}


function validasiForm()
{

    const nakes =
        document.getElementById("id_nakes").value;


    if (!nakes) {

        alert(
            "Silakan pilih tenaga kesehatan yang dilaporkan."
        );

        return false;
    }


    return true;
}

</script>


</body>

</html>