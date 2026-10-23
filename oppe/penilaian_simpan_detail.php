<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

/* =========================================================
   1. AMBIL DATA POST
   ========================================================= */

$id_penilaian = isset($_POST['id_penilaian']) ? (int)$_POST['id_penilaian'] : 0;
$tanggal_penilaian = isset($_POST['tanggal_penilaian'])
    ? trim($_POST['tanggal_penilaian'])
    : '';

$penilai = isset($_POST['penilai'])
    ? trim($_POST['penilai'])
    : '';

$catatan_umum = isset($_POST['catatan_umum'])
    ? trim($_POST['catatan_umum'])
    : '';

$tindak_lanjut = isset($_POST['tindak_lanjut'])
    ? trim($_POST['tindak_lanjut'])
    : '';

$nilai_post = isset($_POST['nilai']) && is_array($_POST['nilai'])
    ? $_POST['nilai']
    : [];

$catatan_indikator = isset($_POST['catatan']) && is_array($_POST['catatan'])
    ? $_POST['catatan']
    : [];


/* =========================================================
   2. VALIDASI DATA UTAMA
   ========================================================= */

if ($id_penilaian <= 0) {
    die("ID penilaian tidak valid.");
}

if ($tanggal_penilaian === '') {
    die("Tanggal penilaian wajib diisi.");
}

if ($penilai === '') {
    die("Nama penilai wajib diisi.");
}


/* =========================================================
   3. CEK DATA PENILAIAN
   ========================================================= */

$sql_penilaian = "
    SELECT 
        p.id_penilaian,
        p.id_nakes,
        p.id_periode,
        n.nama_lengkap,
        n.id_profesi
    FROM oppe_penilaian p
    INNER JOIN tenaga_kesehatan n 
        ON p.id_nakes = n.id_nakes
    WHERE p.id_penilaian = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql_penilaian);

if (!$stmt) {
    die("Gagal menyiapkan query penilaian: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_penilaian);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_penilaian = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data_penilaian) {
    die("Data penilaian tidak ditemukan.");
}

$id_profesi = (int)$data_penilaian['id_profesi'];


/* =========================================================
   4. AMBIL KOMPONEN OPPE
   ========================================================= */

$sql_komponen = "
    SELECT 
        id_komponen,
        nama_komponen,
        bobot,
        urutan
    FROM oppe_komponen
    WHERE status_aktif = 1
    ORDER BY urutan ASC, id_komponen ASC
";

$result_komponen = mysqli_query($conn, $sql_komponen);

if (!$result_komponen) {
    die("Gagal mengambil komponen OPPE: " . mysqli_error($conn));
}

$komponen = [];
$total_bobot = 0;

while ($row = mysqli_fetch_assoc($result_komponen)) {

    $id_komponen = (int)$row['id_komponen'];
    $bobot = (float)$row['bobot'];

    $komponen[$id_komponen] = [
        'id_komponen' => $id_komponen,
        'nama_komponen' => $row['nama_komponen'],
        'bobot' => $bobot,
        'indikator' => [],
        'jumlah' => 0,
        'total_nilai' => 0
    ];

    $total_bobot += $bobot;
}

if (count($komponen) === 0) {
    die("Komponen OPPE belum tersedia.");
}

if (abs($total_bobot - 100) > 0.01) {
    die(
        "Total bobot komponen OPPE harus 100%. " .
        "Saat ini total bobot = " .
        number_format($total_bobot, 2) .
        "%"
    );
}


/* =========================================================
   5. AMBIL INDIKATOR OPPE
   ========================================================= */

$sql_indikator = "
    SELECT
        i.id_indikator,
        i.id_profesi,
        i.id_komponen,
        i.komponen,
        i.nama_indikator,
        i.deskripsi,
        i.bobot,
        i.nilai_maksimal,
        c.nama_komponen,
        c.bobot AS bobot_komponen,
        c.urutan
    FROM oppe_indikator i
    INNER JOIN oppe_komponen c
        ON i.id_komponen = c.id_komponen
    WHERE i.status_aktif = 1
      AND c.status_aktif = 1
      AND (
            i.id_profesi IS NULL
            OR i.id_profesi = 0
            OR i.id_profesi = ?
      )
    ORDER BY 
        c.urutan ASC,
        i.id_indikator ASC
";

$stmt = mysqli_prepare($conn, $sql_indikator);

if (!$stmt) {
    die("Gagal menyiapkan query indikator: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_profesi);
mysqli_stmt_execute($stmt);

$result_indikator = mysqli_stmt_get_result($stmt);

$indikator = [];

while ($row = mysqli_fetch_assoc($result_indikator)) {

    $id_indikator = (int)$row['id_indikator'];
    $id_komponen = (int)$row['id_komponen'];

    if (!isset($komponen[$id_komponen])) {
        continue;
    }

    $indikator[$id_indikator] = [
        'id_indikator' => $id_indikator,
        'id_komponen' => $id_komponen,
        'nama_indikator' => $row['nama_indikator'],
        'bobot' => (float)$row['bobot'],
        'nilai_maksimal' => (int)$row['nilai_maksimal']
    ];

    $komponen[$id_komponen]['indikator'][$id_indikator] = $id_indikator;
    $komponen[$id_komponen]['jumlah']++;
}

mysqli_stmt_close($stmt);

if (count($indikator) === 0) {
    die(
        "Tidak ditemukan indikator OPPE yang aktif untuk profesi tenaga kesehatan ini."
    );
}


/* =========================================================
   6. VALIDASI JUMLAH NILAI
   ========================================================= */

foreach ($indikator as $id_indikator => $data) {

    if (!isset($nilai_post[$id_indikator])) {

        die(
            "Nilai indikator belum diisi: " .
            htmlspecialchars(
                $data['nama_indikator'],
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    $nilai_raw = $nilai_post[$id_indikator];

    /* -----------------------------------------
       Nilai harus numerik
       ----------------------------------------- */

    if ($nilai_raw === '' || !is_numeric($nilai_raw)) {

        die(
            "Nilai indikator tidak valid: " .
            htmlspecialchars(
                $data['nama_indikator'],
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    /*
     * PERBAIKAN PENTING:
     *
     * Jangan menggunakan:
     * in_array($nilai, [1,2,3,4], true)
     *
     * karena $nilai bisa menjadi 1.0 / 2.0 / 3.0 / 4.0.
     */

    $nilai_float = (float)$nilai_raw;
    $nilai_int = (int)$nilai_float;

    /* Harus bilangan bulat */
    if ($nilai_float != $nilai_int) {

        die(
            "Nilai indikator harus berupa angka bulat 1, 2, 3 atau 4: " .
            htmlspecialchars(
                $data['nama_indikator'],
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    /* Harus 1, 2, 3 atau 4 */
    if (!in_array($nilai_int, [1, 2, 3, 4], true)) {

        die(
            "Nilai indikator harus 1, 2, 3 atau 4: " .
            htmlspecialchars(
                $data['nama_indikator'],
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    /* Simpan kembali sebagai integer */
    $nilai_post[$id_indikator] = $nilai_int;
}


/* =========================================================
   7. HITUNG NILAI PER KOMPONEN
   ========================================================= */

$total_nilai_raw = 0;

foreach ($indikator as $id_indikator => $data) {

    $nilai = (int)$nilai_post[$id_indikator];

    $id_komponen = $data['id_komponen'];

    $komponen[$id_komponen]['total_nilai'] += $nilai;

    $total_nilai_raw += $nilai;
}


/* =========================================================
   8. HITUNG NILAI AKHIR BERBOBOT
   ========================================================= */

$nilai_akhir = 0;

foreach ($komponen as $id_komponen => $data) {

    $jumlah = (int)$data['jumlah'];

    if ($jumlah <= 0) {
        continue;
    }

    $rata_rata = $data['total_nilai'] / $jumlah;

    $bobot = (float)$data['bobot'];

    /*
     * Contoh:
     * rata-rata komponen = 3.50
     * bobot = 20%
     *
     * kontribusi = 3.50 x 20 / 100
     */

    $kontribusi = ($rata_rata * $bobot) / 100;

    $nilai_akhir += $kontribusi;
}


/* =========================================================
   9. TENTUKAN KATEGORI
   ========================================================= */

if ($nilai_akhir >= 3.51) {

    $kategori = "SANGAT BAIK";

} elseif ($nilai_akhir >= 2.76) {

    $kategori = "BAIK";

} elseif ($nilai_akhir >= 2.00) {

    $kategori = "CUKUP";

} else {

    $kategori = "PERLU PERBAIKAN";
}


/* =========================================================
   10. TRANSAKSI DATABASE
   ========================================================= */

mysqli_begin_transaction($conn);

try {

    /* -----------------------------------------
       UPDATE HEADER PENILAIAN
       ----------------------------------------- */

    $sql_update = "
        UPDATE oppe_penilaian
        SET
            tanggal_penilaian = ?,
            penilai = ?,
            total_nilai = ?,
            nilai_akhir = ?,
            kategori = ?,
            catatan = ?,
            tindak_lanjut = ?,
            status = 'DINILAI'
        WHERE id_penilaian = ?
    ";

    $stmt = mysqli_prepare($conn, $sql_update);

    if (!$stmt) {
        throw new Exception(
            "Gagal menyiapkan update penilaian: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssddsssi",
        $tanggal_penilaian,
        $penilai,
        $total_nilai_raw,
        $nilai_akhir,
        $kategori,
        $catatan_umum,
        $tindak_lanjut,
        $id_penilaian
    );

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(
            "Gagal menyimpan penilaian: " .
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);


    /* -----------------------------------------
       HAPUS DETAIL LAMA
       ----------------------------------------- */

    $sql_delete = "
        DELETE FROM oppe_detail
        WHERE id_penilaian = ?
    ";

    $stmt = mysqli_prepare($conn, $sql_delete);

    if (!$stmt) {
        throw new Exception(
            "Gagal menyiapkan penghapusan detail: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_penilaian
    );

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(
            "Gagal menghapus detail lama: " .
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);


    /* -----------------------------------------
       INSERT DETAIL BARU
       ----------------------------------------- */

    $sql_insert = "
        INSERT INTO oppe_detail
        (
            id_penilaian,
            id_indikator,
            nilai,
            catatan
        )
        VALUES (?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($conn, $sql_insert);

    if (!$stmt) {
        throw new Exception(
            "Gagal menyiapkan detail penilaian: " .
            mysqli_error($conn)
        );
    }

    foreach ($indikator as $id_indikator => $data) {

        $nilai = (int)$nilai_post[$id_indikator];

        $catatan = isset($catatan_indikator[$id_indikator])
            ? trim($catatan_indikator[$id_indikator])
            : '';

        mysqli_stmt_bind_param(
            $stmt,
            "iids",
            $id_penilaian,
            $id_indikator,
            $nilai,
            $catatan
        );

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                "Gagal menyimpan detail indikator " .
                $data['nama_indikator'] .
                ": " .
                mysqli_stmt_error($stmt)
            );
        }
    }

    mysqli_stmt_close($stmt);


    /* -----------------------------------------
       COMMIT
       ----------------------------------------- */

    mysqli_commit($conn);


    /* -----------------------------------------
       REDIRECT
       ----------------------------------------- */

    header(
        "Location: penilaian_detail.php?id=" .
        $id_penilaian .
        "&simpan=berhasil"
    );

    exit;


} catch (Exception $e) {

    mysqli_rollback($conn);

    die(
        "<div style='font-family:Arial;padding:30px'>" .
        "<h2>Gagal menyimpan penilaian</h2>" .
        "<p>" .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        ) .
        "</p>" .
        "<p>" .
        "<a href='javascript:history.back()'>← Kembali</a>" .
        "</p>" .
        "</div>"
    );
}
?>