<?php

session_start();

/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id_user'])) {

    header("Location: ../auth/login.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| CEK ROLE ADMIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'ADMIN'
) {

    die("
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <title>Akses Ditolak</title>

            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f4f7fb;
                    margin: 0;
                    padding: 0;
                }

                .box {
                    width: 420px;
                    max-width: 90%;
                    margin: 120px auto;
                    background: white;
                    padding: 35px;
                    text-align: center;
                    border-radius: 12px;
                    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
                }

                h2 {
                    color: #dc3545;
                    margin-bottom: 10px;
                }

                p {
                    color: #666;
                    line-height: 1.6;
                }

                a {
                    display: inline-block;
                    margin-top: 15px;
                    padding: 10px 18px;
                    background: #0d6efd;
                    color: white;
                    text-decoration: none;
                    border-radius: 7px;
                }

                a:hover {
                    background: #0b5ed7;
                }
            </style>

        </head>

        <body>

            <div class='box'>

                <h2>
                    Akses Ditolak
                </h2>

                <p>
                    Anda tidak memiliki hak akses
                    untuk membuka halaman Manajemen User.
                </p>

                <a href='../dashboard/index.php'>
                    Kembali ke Dashboard
                </a>

            </div>

        </body>
        </html>
    ");

}