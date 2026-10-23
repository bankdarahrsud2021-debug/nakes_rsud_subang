<?php
session_start();

/*
|--------------------------------------------------------------------------
| Jika sudah login, arahkan ke dashboard
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['id_user'])) {
    header("Location: ../dashboard/index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Pesan error dari proses_login.php
|--------------------------------------------------------------------------
*/

$error = '';

if (isset($_GET['error'])) {
    $error = trim($_GET['error']);
}

/*
|--------------------------------------------------------------------------
| Pesan logout
|--------------------------------------------------------------------------
*/

$logout = isset($_GET['logout']) && $_GET['logout'] === 'berhasil';

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Login | Sistem Informasi Tenaga Kesehatan Lainnya
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            font-family:
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eef5ff 0%,
                    #f8fbff 50%,
                    #eaf7f3 100%
                );

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #263238;
        }

        /*
        |--------------------------------------------------------------------------
        | BACKGROUND
        |--------------------------------------------------------------------------
        */

        .background {
            position: fixed;
            inset: 0;

            overflow: hidden;

            pointer-events: none;
        }

        .circle {
            position: absolute;

            border-radius: 50%;

            opacity: .35;

            filter: blur(1px);
        }

        .circle-one {
            width: 380px;
            height: 380px;

            background: #cfe2ff;

            top: -170px;
            left: -130px;
        }

        .circle-two {
            width: 330px;
            height: 330px;

            background: #d1e7dd;

            bottom: -150px;
            right: -100px;
        }

        /*
        |--------------------------------------------------------------------------
        | LOGIN WRAPPER
        |--------------------------------------------------------------------------
        */

        .login-wrapper {

            width: 100%;

            max-width: 1050px;

            margin: 25px;

            display: grid;

            grid-template-columns:
                1fr 1fr;

            background: rgba(255,255,255,.96);

            border-radius: 22px;

            overflow: hidden;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,.12);

            position: relative;

            z-index: 2;
        }

        /*
        |--------------------------------------------------------------------------
        | LEFT PANEL
        |--------------------------------------------------------------------------
        */

        .left-panel {

            background:
                linear-gradient(
                    145deg,
                    #0d6efd,
                    #0b5ed7 55%,
                    #0a58ca
                );

            color: white;

            padding: 55px 45px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            position: relative;

            overflow: hidden;
        }

        .left-panel::before {

            content: "";

            position: absolute;

            width: 260px;
            height: 260px;

            border-radius: 50%;

            background: rgba(255,255,255,.07);

            right: -100px;
            top: -90px;
        }

        .left-panel::after {

            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            border-radius: 50%;

            background: rgba(255,255,255,.06);

            left: -80px;
            bottom: -60px;
        }

        .brand {

            position: relative;

            z-index: 2;

            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | LOGO
        |--------------------------------------------------------------------------
        */

        .logo {

            width: 105px;
            height: 105px;

            margin: 0 auto 25px;

            border-radius: 50%;

            background: white;

            display: flex;

            align-items: center;

            justify-content: center;

            box-shadow:
                0 10px 30px
                rgba(0,0,0,.18);

            overflow: hidden;
        }

        .logo img {

            width: 82px;
            height: 82px;

            object-fit: contain;
        }

        .logo-placeholder {

            width: 82px;
            height: 82px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f1f5f9;

            color: #0d6efd;

            font-weight: bold;

            font-size: 13px;

            text-align: center;

            line-height: 1.2;
        }

        .brand h1 {

            margin: 0;

            font-size: 26px;

            line-height: 1.3;

            font-weight: 700;
        }

        .brand h2 {

            margin: 10px 0 0;

            font-size: 18px;

            font-weight: 400;

            opacity: .92;
        }

        .brand-description {

            margin: 25px auto 0;

            max-width: 390px;

            line-height: 1.7;

            font-size: 14px;

            opacity: .86;
        }

        /*
        |--------------------------------------------------------------------------
        | FEATURES
        |--------------------------------------------------------------------------
        */

        .features {

            margin-top: 35px;

            display: grid;

            gap: 13px;

            position: relative;

            z-index: 2;
        }

        .feature {

            display: flex;

            align-items: center;

            gap: 12px;

            font-size: 13px;

            background: rgba(255,255,255,.08);

            padding: 11px 14px;

            border-radius: 10px;
        }

        .feature-icon {

            width: 30px;
            height: 30px;

            border-radius: 8px;

            background: rgba(255,255,255,.15);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;

            flex-shrink: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | RIGHT PANEL
        |--------------------------------------------------------------------------
        */

        .right-panel {

            padding: 55px 50px;

            display: flex;

            align-items: center;

            justify-content: center;
        }

        .login-box {

            width: 100%;

            max-width: 380px;
        }

        .login-header {

            margin-bottom: 30px;
        }

        .login-header h3 {

            margin: 0 0 8px;

            font-size: 27px;

            color: #1f2937;
        }

        .login-header p {

            margin: 0;

            font-size: 14px;

            color: #6b7280;

            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .alert {

            padding: 12px 14px;

            border-radius: 9px;

            margin-bottom: 20px;

            font-size: 13px;

            line-height: 1.5;
        }

        .alert-error {

            background: #f8d7da;

            color: #842029;

            border: 1px solid #f1aeb5;
        }

        .alert-success {

            background: #d1e7dd;

            color: #0f5132;

            border: 1px solid #a3cfbb;
        }

        /*
        |--------------------------------------------------------------------------
        | FORM
        |--------------------------------------------------------------------------
        */

        .form-group {

            margin-bottom: 20px;
        }

        .form-label {

            display: block;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 8px;

            color: #374151;
        }

        .input-wrapper {

            position: relative;
        }

        .input-icon {

            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #9ca3af;

            font-size: 17px;

            pointer-events: none;
        }

        .form-control {

            width: 100%;

            height: 48px;

            padding:
                0 45px 0 44px;

            border:
                1px solid #d1d5db;

            border-radius: 9px;

            outline: none;

            font-size: 14px;

            color: #1f2937;

            background: #fff;

            transition:
                border-color .2s,
                box-shadow .2s;
        }

        .form-control:focus {

            border-color: #0d6efd;

            box-shadow:
                0 0 0 3px
                rgba(13,110,253,.12);
        }

        .form-control::placeholder {

            color: #9ca3af;
        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD TOGGLE
        |--------------------------------------------------------------------------
        */

        .toggle-password {

            position: absolute;

            right: 12px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            cursor: pointer;

            color: #6b7280;

            font-size: 16px;

            padding: 5px;
        }

        .toggle-password:hover {

            color: #0d6efd;
        }

        /*
        |--------------------------------------------------------------------------
        | OPTIONS
        |--------------------------------------------------------------------------
        */

        .form-options {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 22px;

            font-size: 12px;
        }

        .remember {

            display: flex;

            align-items: center;

            gap: 7px;

            color: #6b7280;

            cursor: pointer;
        }

        .remember input {

            width: 15px;
            height: 15px;

            accent-color: #0d6efd;
        }

        /*
        |--------------------------------------------------------------------------
        | LOGIN BUTTON
        |--------------------------------------------------------------------------
        */

        .btn-login {

            width: 100%;

            height: 49px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #0d6efd,
                    #0b5ed7
                );

            color: white;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            box-shadow:
                0 7px 18px
                rgba(13,110,253,.22);

            transition:
                transform .15s,
                box-shadow .15s;
        }

        .btn-login:hover {

            transform: translateY(-1px);

            box-shadow:
                0 10px 24px
                rgba(13,110,253,.28);
        }

        .btn-login:active {

            transform: translateY(0);
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .login-footer {

            text-align: center;

            margin-top: 28px;

            padding-top: 20px;

            border-top:
                1px solid #e5e7eb;

            color: #9ca3af;

            font-size: 11px;

            line-height: 1.6;
        }

        .login-footer strong {

            color: #6b7280;
        }

        /*
        |--------------------------------------------------------------------------
        | LOADING
        |--------------------------------------------------------------------------
        */

        .loading {

            display: none;

            margin-left: 7px;
        }

        .btn-login.loading-active {

            opacity: .85;

            cursor: wait;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media(max-width: 850px) {

            .login-wrapper {

                max-width: 520px;

                grid-template-columns: 1fr;
            }

            .left-panel {

                padding: 35px 30px;
            }

            .brand h1 {

                font-size: 23px;
            }

            .brand h2 {

                font-size: 16px;
            }

            .brand-description {

                display: none;
            }

            .features {

                display: none;
            }

            .right-panel {

                padding: 40px 30px;
            }
        }

        @media(max-width: 480px) {

            body {

                align-items: flex-start;

                padding-top: 20px;
            }

            .login-wrapper {

                margin: 12px;

                border-radius: 16px;
            }

            .left-panel {

                padding: 30px 20px;
            }

            .logo {

                width: 85px;
                height: 85px;
            }

            .logo img {

                width: 68px;
                height: 68px;
            }

            .logo-placeholder {

                width: 68px;
                height: 68px;
            }

            .brand h1 {

                font-size: 20px;
            }

            .right-panel {

                padding: 32px 22px;
            }

            .login-header h3 {

                font-size: 23px;
            }
        }

    </style>

</head>

<body>

<div class="background">

    <div class="circle circle-one"></div>

    <div class="circle circle-two"></div>

</div>


<div class="login-wrapper">

    <!-- =====================================================
         PANEL KIRI
    ====================================================== -->

    <div class="left-panel">

        <div class="brand">

            <div class="logo">

                <?php

                /*
                |--------------------------------------------------------------------------
                | LOGO RSUD
                |--------------------------------------------------------------------------
                |
                | Jika nanti mempunyai file logo, simpan misalnya:
                |
                | C:\xampp\htdocs\nakes_rsud_subang\assets\logo_rsud.png
                |
                | lalu aktifkan <img> di bawah.
                |
                */

                $logo_path =
                    "../assets/logo_rsud.png";

                if (file_exists(
                    __DIR__ . "/../assets/logo_rsud.png"
                )):

                ?>

                    <img
                        src="<?= $logo_path; ?>"
                        alt="Logo RSUD Subang"
                    >

                <?php else: ?>

                    <div class="logo-placeholder">

                        RSUD<br>
                        SUBANG

                    </div>

                <?php endif; ?>

            </div>


            <h1>
                RSUD SUBANG
            </h1>

            <h2>
                Sistem Informasi Tenaga Kesehatan Lainnya
            </h2>


            <p class="brand-description">

                Sistem informasi untuk pengelolaan data,
                dokumen, kredensial, evaluasi profesional,
                dan administrasi tenaga kesehatan lainnya
                di lingkungan RSUD Subang.

            </p>

        </div>


        <div class="features">

            <div class="feature">

                <div class="feature-icon">
                    ✓
                </div>

                <div>
                    Pengelolaan Data Tenaga Kesehatan
                </div>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    ✓
                </div>

                <div>
                    Monitoring STR dan SIP
                </div>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    ✓
                </div>

                <div>
                    Kredensial dan Evaluasi Profesional
                </div>

            </div>


            <div class="feature">

                <div class="feature-icon">
                    ✓
                </div>

                <div>
                    OPPE Tenaga Kesehatan Lainnya
                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PANEL KANAN
    ====================================================== -->

    <div class="right-panel">

        <div class="login-box">

            <div class="login-header">

                <h3>
                    Selamat Datang
                </h3>

                <p>
                    Silakan masuk menggunakan akun Anda
                    untuk melanjutkan ke sistem.
                </p>

            </div>


            <!-- =================================================
                 PESAN ERROR
            ================================================== -->

            <?php if ($error !== ''): ?>

                <div class="alert alert-error">

                    ⚠️

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 PESAN LOGOUT
            ================================================== -->

            <?php if ($logout): ?>

                <div class="alert alert-success">

                    ✓ Anda telah berhasil keluar dari sistem.

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM LOGIN
            ================================================== -->

            <form
                id="loginForm"
                method="POST"
                action="proses_login.php"
                autocomplete="off"
            >

                <!-- USERNAME -->

                <div class="form-group">

                    <label
                        class="form-label"
                        for="username"
                    >
                        Username
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            👤
                        </span>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            placeholder="Masukkan username"
                            required
                            autocomplete="username"
                            autofocus
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label
                        class="form-label"
                        for="password"
                    >
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Tampilkan password"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <!-- OPTIONS -->

                <div class="form-options">

                    <label class="remember">

                        <input
                            type="checkbox"
                            id="remember"
                        >

                        Ingat saya

                    </label>

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    class="btn-login"
                    id="loginButton"
                >

                    Masuk ke Sistem

                    <span
                        class="loading"
                        id="loading"
                    >
                        ...
                    </span>

                </button>

            </form>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div class="login-footer">

                <strong>
                    Sistem Informasi Tenaga Kesehatan Lainnya
                </strong>

                <br>

                RSUD Subang

                <br><br>

                © <?= date('Y'); ?>
                RSUD Subang. All Rights Reserved.

            </div>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Tampilkan / Sembunyikan Password
|--------------------------------------------------------------------------
*/

const togglePassword =
    document.getElementById(
        'togglePassword'
    );

const password =
    document.getElementById(
        'password'
    );

togglePassword.addEventListener(
    'click',
    function () {

        if (
            password.type ===
            'password'
        ) {

            password.type =
                'text';

            togglePassword.textContent =
                '🙈';

            togglePassword.setAttribute(
                'aria-label',
                'Sembunyikan password'
            );

        } else {

            password.type =
                'password';

            togglePassword.textContent =
                '👁';

            togglePassword.setAttribute(
                'aria-label',
                'Tampilkan password'
            );
        }

    }
);


/*
|--------------------------------------------------------------------------
| Loading saat login
|--------------------------------------------------------------------------
*/

const loginForm =
    document.getElementById(
        'loginForm'
    );

const loginButton =
    document.getElementById(
        'loginButton'
    );

const loading =
    document.getElementById(
        'loading'
    );

loginForm.addEventListener(
    'submit',
    function () {

        loginButton.classList.add(
            'loading-active'
        );

        loading.style.display =
            'inline';

        loginButton.disabled =
            true;

    }
);

</script>

</body>

</html>