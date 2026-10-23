<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$nama_user = $_SESSION['nama_user'] ?? 'Pengguna';
$role      = $_SESSION['role'] ?? 'ANGGOTA';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard - Sistem Informasi Nakes RSUD Subang
    </title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #333;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #0d6efd, #084298);
            color: white;
            padding: 25px 18px;
            z-index: 1000;
        }

        .logo-area {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.25);
            margin-bottom: 20px;
        }

        .logo-box {
            width: 70px;
            height: 70px;
            margin: auto;
            background: white;
            color: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: bold;
        }

        .logo-area h2 {
            margin-top: 12px;
            font-size: 18px;
        }

        .logo-area p {
            font-size: 12px;
            margin-top: 5px;
            line-height: 1.5;
            opacity: 0.85;
        }

        .menu-title {
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.65;
            margin: 18px 10px 8px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            padding: 11px 13px;
            border-radius: 8px;
            margin-bottom: 4px;
            font-size: 14px;
            transition: 0.2s;
        }

        .menu a:hover {
            background: rgba(255,255,255,0.15);
        }

        .menu-icon {
            width: 25px;
            text-align: center;
            font-size: 17px;
        }

        .logout {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 20px;
        }

        .logout a {
            background: rgba(220,53,69,0.9);
            display: block;
            text-align: center;
            padding: 11px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .logout a:hover {
            background: #dc3545;
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        /* TOPBAR */

        .topbar {
            min-height: 75px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
        }

        .topbar h1 {
            font-size: 22px;
            color: #1f2937;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: #e9f2ff;
            color: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .user-text {
            text-align: right;
        }

        .user-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .user-role {
            display: inline-block;
            margin-top: 3px;
            padding: 3px 8px;
            border-radius: 10px;
            background: #e9f2ff;
            color: #0d6efd;
            font-size: 10px;
            font-weight: bold;
        }

        /* CONTENT */

        .content {
            padding: 30px;
        }

        .welcome {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
            padding: 28px 30px;
            border-radius: 14px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(13,110,253,0.15);
        }

        .welcome h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .welcome p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* STATISTICS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
            border: 1px solid #edf0f5;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: #eaf2ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .stat-number {
            font-size: 22px;
            font-weight: bold;
            color: #1f2937;
        }

        .stat-label {
            font-size: 12px;
            color: #777;
            margin-top: 4px;
        }

        /* MENU */

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1f2937;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .menu-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            text-decoration: none;
            color: #333;
            border: 1px solid #edf0f5;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
            transition: 0.2s;
            display: block;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(0,0,0,0.09);
            border-color: #cfe2ff;
        }

        .menu-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: #eef5ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            font-size: 15px;
            margin-bottom: 7px;
            color: #1f2937;
        }

        .menu-card p {
            font-size: 12px;
            color: #777;
            line-height: 1.5;
        }

        .footer {
            margin-top: 40px;
            padding: 20px 0;
            text-align: center;
            color: #999;
            font-size: 11px;
        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .menu-grid {
                grid-template-columns: repeat(3, 1fr);
            }

        }

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .logout {
                position: relative;
                left: auto;
                right: auto;
                bottom: auto;
                margin-top: 20px;
            }

            .main {
                margin-left: 0;
            }

            .topbar {
                padding: 15px;
            }

            .content {
                padding: 15px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>


<!-- ==================================================
     SIDEBAR
================================================== -->

<div class="sidebar">

    <div class="logo-area">

        <div class="logo-box">
            RSUD
        </div>

        <h2>RSUD SUBANG</h2>

        <p>
            Sistem Informasi<br>
            Tenaga Kesehatan Lainnya
        </p>

    </div>


    <div class="menu">

        <div class="menu-title">
            Menu Utama
        </div>


        <!-- DASHBOARD -->

        <a href="../dashboard/index.php">

            <span class="menu-icon">
                🏠
            </span>

            Dashboard

        </a>


        <!-- DATA NAKES -->

        <a href="../nakes/index.php">

            <span class="menu-icon">
                👨‍⚕️
            </span>

            Data Tenaga Kesehatan

        </a>


        <!-- STR -->

        <a href="../str/index.php">

            <span class="menu-icon">
                📋
            </span>

            STR

        </a>


        <!-- SIP -->

        <a href="../sip/index.php">

            <span class="menu-icon">
                📄
            </span>

            SIP

        </a>


        <!-- PENDIDIKAN -->

        <a href="../pendidikan/index.php">

            <span class="menu-icon">
                🎓
            </span>

            Pendidikan

        </a>


        <?php

        /*
        |--------------------------------------------------------------------------
        | MENU KHUSUS ADMIN & PENGURUS
        |--------------------------------------------------------------------------
        */

        if (
            $role === 'ADMIN' ||
            $role === 'PENGURUS'
        ):

        ?>


            <!-- KREDENSIAL -->

            <a href="../kredensial/index.php">

                <span class="menu-icon">
                    📑
                </span>

                Kredensial

            </a>


            <!-- OPPE -->

            <a href="../oppe/index.php">

                <span class="menu-icon">
                    📊
                </span>

                OPPE

            </a>


            <!-- ETIK -->

            <a href="../etik/index.php">

                <span class="menu-icon">
                    ⚖️
                </span>

                Etik Profesi

            </a>


        <?php endif; ?>


        <?php

        /*
        |--------------------------------------------------------------------------
        | MANAJEMEN USER
        | HANYA ADMIN
        |--------------------------------------------------------------------------
        */

        if ($role === 'ADMIN'):

        ?>

            <div class="menu-title">
                Administrasi
            </div>


            <a href="../user/index.php">

                <span class="menu-icon">
                    👤
                </span>

                Manajemen User

            </a>


        <?php endif; ?>


    </div>


    <!-- LOGOUT -->

    <div class="logout">

        <a
            href="../auth/logout.php"
            onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');"
        >

            🚪 &nbsp; Keluar

        </a>

    </div>

</div>


<!-- ==================================================
     MAIN
================================================== -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <h1>
            Dashboard
        </h1>


        <div class="user-info">

            <div class="user-text">

                <div class="user-name">
                    <?= htmlspecialchars($nama_user); ?>
                </div>

                <div class="user-role">
                    <?= htmlspecialchars($role); ?>
                </div>

            </div>


            <div class="user-avatar">
                👤
            </div>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">


        <!-- WELCOME -->

        <div class="welcome">

            <h2>
                Selamat Datang,
                <?= htmlspecialchars($nama_user); ?>
            </h2>

            <p>
                Sistem Informasi Tenaga Kesehatan Lainnya
                RSUD Subang
            </p>

        </div>


        <!-- ==================================================
             STATISTIK
        ================================================== -->

        <?php

        $jumlah_nakes = 0;
        $jumlah_str = 0;
        $jumlah_sip = 0;
        $jumlah_kredensial = 0;


        /* NAKES */

        $query = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM tenaga_kesehatan"
        );

        if ($query) {

            $data = mysqli_fetch_assoc($query);

            $jumlah_nakes = $data['total'] ?? 0;

        }


        /* STR */

        $query = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM str_nakes"
        );

        if ($query) {

            $data = mysqli_fetch_assoc($query);

            $jumlah_str = $data['total'] ?? 0;

        }


        /* SIP */

        $query = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM sip_nakes"
        );

        if ($query) {

            $data = mysqli_fetch_assoc($query);

            $jumlah_sip = $data['total'] ?? 0;

        }


        /* KREDENSIAL */

        if (
            $role === 'ADMIN' ||
            $role === 'PENGURUS'
        ) {

            $query = mysqli_query(
                $conn,
                "SELECT COUNT(*) AS total
                 FROM kredensial"
            );

            if ($query) {

                $data = mysqli_fetch_assoc($query);

                $jumlah_kredensial =
                    $data['total'] ?? 0;

            }

        }

        ?>


        <div class="stats">


            <!-- NAKES -->

            <div class="stat-card">

                <div class="stat-icon">
                    👨‍⚕️
                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($jumlah_nakes); ?>
                    </div>

                    <div class="stat-label">
                        Tenaga Kesehatan
                    </div>

                </div>

            </div>


            <!-- STR -->

            <div class="stat-card">

                <div class="stat-icon">
                    📋
                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($jumlah_str); ?>
                    </div>

                    <div class="stat-label">
                        Data STR
                    </div>

                </div>

            </div>


            <!-- SIP -->

            <div class="stat-card">

                <div class="stat-icon">
                    📄
                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($jumlah_sip); ?>
                    </div>

                    <div class="stat-label">
                        Data SIP
                    </div>

                </div>

            </div>


            <!-- KREDENSIAL -->

            <div class="stat-card">

                <div class="stat-icon">
                    📑
                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($jumlah_kredensial); ?>
                    </div>

                    <div class="stat-label">
                        Kredensial
                    </div>

                </div>

            </div>


        </div>


        <!-- ==================================================
             MENU SISTEM
        ================================================== -->

        <div class="section-title">
            Menu Sistem
        </div>


        <div class="menu-grid">


            <!-- DATA NAKES -->

            <a
                href="../nakes/index.php"
                class="menu-card"
            >

                <div class="menu-card-icon">
                    👨‍⚕️
                </div>

                <h3>
                    Data Tenaga Kesehatan
                </h3>

                <p>
                    Mengelola data tenaga kesehatan
                    lainnya RSUD Subang.
                </p>

            </a>


            <!-- STR -->

            <a
                href="../str/index.php"
                class="menu-card"
            >

                <div class="menu-card-icon">
                    📋
                </div>

                <h3>
                    STR
                </h3>

                <p>
                    Pengelolaan data Surat Tanda
                    Registrasi.
                </p>

            </a>


            <!-- SIP -->

            <a
                href="../sip/index.php"
                class="menu-card"
            >

                <div class="menu-card-icon">
                    📄
                </div>

                <h3>
                    SIP
                </h3>

                <p>
                    Pengelolaan data Surat Izin
                    Praktik.
                </p>

            </a>


            <!-- PENDIDIKAN -->

            <a
                href="../pendidikan/index.php"
                class="menu-card"
            >

                <div class="menu-card-icon">
                    🎓
                </div>

                <h3>
                    Pendidikan
                </h3>

                <p>
                    Data pendidikan, ijazah,
                    dan dokumen akademik.
                </p>

            </a>


            <?php

            /*
            |--------------------------------------------------------------------------
            | MENU ADMIN & PENGURUS
            |--------------------------------------------------------------------------
            */

            if (
                $role === 'ADMIN' ||
                $role === 'PENGURUS'
            ):

            ?>


                <!-- KREDENSIAL -->

                <a
                    href="../kredensial/index.php"
                    class="menu-card"
                >

                    <div class="menu-card-icon">
                        📑
                    </div>

                    <h3>
                        Kredensial
                    </h3>

                    <p>
                        Pengajuan, proses dan
                        dokumen kredensial.
                    </p>

                </a>


                <!-- OPPE -->

                <a
                    href="../oppe/index.php"
                    class="menu-card"
                >

                    <div class="menu-card-icon">
                        📊
                    </div>

                    <h3>
                        OPPE
                    </h3>

                    <p>
                        Penilaian kinerja profesional
                        tenaga kesehatan lainnya.
                    </p>

                </a>


                <!-- ETIK -->

                <a
                    href="../etik/index.php"
                    class="menu-card"
                >

                    <div class="menu-card-icon">
                        ⚖️
                    </div>

                    <h3>
                        Etik Profesi
                    </h3>

                    <p>
                        Pengelolaan kasus dan
                        proses etik profesi.
                    </p>

                </a>


            <?php endif; ?>


            <?php

            /*
            |--------------------------------------------------------------------------
            | MANAJEMEN USER
            | HANYA ADMIN
            |--------------------------------------------------------------------------
            */

            if ($role === 'ADMIN'):

            ?>


                <a
                    href="../user/index.php"
                    class="menu-card"
                >

                    <div class="menu-card-icon">
                        👤
                    </div>

                    <h3>
                        Manajemen User
                    </h3>

                    <p>
                        Mengelola akun pengguna,
                        role, status dan password.
                    </p>

                </a>


            <?php endif; ?>


        </div>


        <!-- FOOTER -->

        <div class="footer">

            Sistem Informasi Tenaga Kesehatan Lainnya
            RSUD Subang

            <br>

            &copy; <?= date('Y'); ?> RSUD Subang

        </div>


    </div>

</div>

</body>

</html>