<?php
require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

/* =========================
   INPUT
========================= */
$id_kasus = isset($_POST['id_kasus']) ? (int)$_POST['id_kasus'] : 0;

$tanggal_proses = trim($_POST['tanggal_proses'] ?? '');
$jenis_proses   = trim($_POST['jenis_proses'] ?? '');
$uraian_proses  = trim($_POST['uraian_proses'] ?? '');
$pihak_terlibat = trim($_POST['pihak_terlibat'] ?? '');
$hasil_proses   = trim($_POST['hasil_proses'] ?? '');
$rekomendasi    = trim($_POST['rekomendasi'] ?? '');
$petugas        = trim($_POST['petugas'] ?? '');

$status_proses = trim($_POST['status_proses'] ?? 'SELESAI');

$created_by = isset($_SESSION['id_user'])
    ? (int)$_SESSION['id_user']
    : 0;


/* =========================
   VALIDASI DASAR
========================= */

if ($id_kasus <= 0) {
    die("ID kasus tidak valid.");
}

if ($tanggal_proses === '') {
    die("Tanggal proses wajib diisi.");
}

if ($jenis_proses === '') {
    die("Jenis proses tidak boleh kosong.");
}

if ($petugas === '') {
    $petugas = $_SESSION['nama_user'] ?? '';
}

if ($petugas === '') {
    $petugas = 'Admin';
}


/* =========================
   ALUR STATUS RESMI
========================= */

$alur_status = [
    'DILAPORKAN',
    'TELAAH AWAL',
    'DALAM PEMERIKSAAN',
    'RAPAT ETIK',
    'REKOMENDASI',
    'KEPUTUSAN',
    'SELESAI'
];


/* =========================
   VALIDASI JENIS PROSES
========================= */

if (!in_array($jenis_proses, $alur_status, true)) {
    die("Jenis proses tidak valid.");
}


/* =========================
   VALIDASI STATUS PROSES
========================= */

$status_proses_valid = [
    'TERJADWAL',
    'DILAKSANAKAN',
    'SELESAI'
];

if (!in_array($status_proses, $status_proses_valid, true)) {
    die("Status proses tidak valid.");
}


/* =========================
   AMBIL DATA KASUS
========================= */

$stmt = mysqli_prepare($conn, "
    SELECT
        id_kasus,
        nomor_kasus,
        status_kasus
    FROM etik_kasus
    WHERE id_kasus = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id_kasus);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$kasus = mysqli_fetch_assoc($result);

if (!$kasus) {
    die("Data kasus etik tidak ditemukan.");
}


$status_sekarang = $kasus['status_kasus'];


/* =========================
   CEK KASUS SUDAH SELESAI
========================= */

if ($status_sekarang === 'SELESAI') {

    die("
        <h3>Kasus sudah selesai</h3>
        <p>Kasus <strong>" .
        htmlspecialchars($kasus['nomor_kasus']) .
        "</strong> sudah berstatus SELESAI.</p>
        <p>Tidak dapat menambahkan proses baru.</p>
        <a href='proses.php?id=" . $id_kasus . "'>
            Kembali ke Proses Etik
        </a>
    ");
}


/* =========================
   TENTUKAN STATUS BERIKUTNYA
========================= */

$index_sekarang = array_search(
    $status_sekarang,
    $alur_status,
    true
);

if ($index_sekarang === false) {
    die("Status kasus tidak dikenali: " .
        htmlspecialchars($status_sekarang));
}

$index_berikutnya = $index_sekarang + 1;

if (!isset($alur_status[$index_berikutnya])) {
    die("Tidak ada tahap proses berikutnya.");
}

$status_berikutnya = $alur_status[$index_berikutnya];


/* =========================
   KUNCI URUTAN PROSES
========================= */

if ($jenis_proses !== $status_berikutnya) {

    die("
        <h3>Urutan proses tidak valid</h3>

        <p>
        Status kasus saat ini:
        <strong>" .
        htmlspecialchars($status_sekarang) .
        "</strong>
        </p>

        <p>
        Tahap berikutnya yang diperbolehkan:
        <strong>" .
        htmlspecialchars($status_berikutnya) .
        "</strong>
        </p>

        <p>
        Sistem tidak mengizinkan proses dilewati.
        </p>

        <a href='proses.php?id=" . $id_kasus . "'>
            Kembali ke Proses Etik
        </a>
    ");
}


/* =========================
   CEK DUPLIKASI PROSES
========================= */

$stmt_cek = mysqli_prepare($conn, "
    SELECT id_proses
    FROM etik_proses
    WHERE id_kasus = ?
      AND jenis_proses = ?
    LIMIT 1
");

mysqli_stmt_bind_param(
    $stmt_cek,
    "is",
    $id_kasus,
    $jenis_proses
);

mysqli_stmt_execute($stmt_cek);

$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) > 0) {

    die("
        <h3>Proses sudah pernah dicatat</h3>

        <p>
        Tahap <strong>" .
        htmlspecialchars($jenis_proses) .
        "</strong>
        sudah tercatat untuk kasus ini.
        </p>

        <a href='proses.php?id=" . $id_kasus . "'>
            Kembali ke Proses Etik
        </a>
    ");
}


/* =========================
   MULAI TRANSAKSI
========================= */

mysqli_begin_transaction($conn);

try {

    /* =========================
       SIMPAN RIWAYAT PROSES
    ========================= */

    $stmt_insert = mysqli_prepare($conn, "
        INSERT INTO etik_proses (
            id_kasus,
            tanggal_proses,
            jenis_proses,
            uraian_proses,
            pihak_terlibat,
            hasil_proses,
            rekomendasi,
            petugas,
            status_proses,
            created_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    mysqli_stmt_bind_param(
        $stmt_insert,
        "issssssssi",
        $id_kasus,
        $tanggal_proses,
        $jenis_proses,
        $uraian_proses,
        $pihak_terlibat,
        $hasil_proses,
        $rekomendasi,
        $petugas,
        $status_proses,
        $created_by
    );

    if (!mysqli_stmt_execute($stmt_insert)) {
        throw new Exception(
            "Gagal menyimpan proses: " .
            mysqli_stmt_error($stmt_insert)
        );
    }


    /* =========================
       UPDATE STATUS KASUS
    ========================= */

    $stmt_update = mysqli_prepare($conn, "
        UPDATE etik_kasus
        SET status_kasus = ?
        WHERE id_kasus = ?
    ");

    mysqli_stmt_bind_param(
        $stmt_update,
        "si",
        $status_berikutnya,
        $id_kasus
    );

    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception(
            "Gagal memperbarui status kasus: " .
            mysqli_stmt_error($stmt_update)
        );
    }


    /* =========================
       SIMPAN REKOMENDASI
    ========================= */

    if (
        $status_berikutnya === 'REKOMENDASI' &&
        $rekomendasi !== ''
    ) {

        $stmt_rekomendasi = mysqli_prepare($conn, "
            UPDATE etik_kasus
            SET rekomendasi = ?
            WHERE id_kasus = ?
        ");

        mysqli_stmt_bind_param(
            $stmt_rekomendasi,
            "si",
            $rekomendasi,
            $id_kasus
        );

        mysqli_stmt_execute($stmt_rekomendasi);
    }


    /* =========================
       SIMPAN KEPUTUSAN
    ========================= */

    if (
        $status_berikutnya === 'KEPUTUSAN' &&
        $hasil_proses !== ''
    ) {

        $stmt_keputusan = mysqli_prepare($conn, "
            UPDATE etik_kasus
            SET keputusan = ?
            WHERE id_kasus = ?
        ");

        mysqli_stmt_bind_param(
            $stmt_keputusan,
            "si",
            $hasil_proses,
            $id_kasus
        );

        mysqli_stmt_execute($stmt_keputusan);
    }


    /* =========================
       SELESAI
    ========================= */

    if ($status_berikutnya === 'SELESAI') {

        $stmt_selesai = mysqli_prepare($conn, "
            UPDATE etik_kasus
            SET tanggal_selesai = ?
            WHERE id_kasus = ?
        ");

        mysqli_stmt_bind_param(
            $stmt_selesai,
            "si",
            $tanggal_proses,
            $id_kasus
        );

        mysqli_stmt_execute($stmt_selesai);
    }


    /* =========================
       COMMIT
    ========================= */

    mysqli_commit($conn);

    header(
        "Location: proses.php?id=" .
        $id_kasus .
        "&simpan=berhasil"
    );

    exit;


} catch (Exception $e) {

    mysqli_rollback($conn);

    die("
        <h3>Gagal menyimpan proses etik</h3>

        <p>" .
        htmlspecialchars($e->getMessage()) .
        "</p>

        <a href='proses_tambah.php?id=" .
        $id_kasus .
        "'>
            Kembali
        </a>
    ");
}