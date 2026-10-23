<?php

echo "<h2>Informasi PHP XAMPP</h2>";

echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Loaded php.ini: " . php_ini_loaded_file() . "<br><br>";

echo "ZIP: ";
echo class_exists('ZipArchive') ? "<b style='color:green'>AKTIF</b>" : "<b style='color:red'>TIDAK AKTIF</b>";

echo "<br><br>";

echo "GD: ";
echo extension_loaded('gd') ? "<b style='color:green'>AKTIF</b>" : "<b style='color:red'>TIDAK AKTIF</b>";