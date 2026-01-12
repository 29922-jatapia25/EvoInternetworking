<?php
declare(strict_types=1);
session_start();

$DB_HOST = "localhost";
$DB_NAME = "evosalud_db";
$DB_USER = "root";
$DB_PASS = "123";

try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (Throwable $e) {
  http_response_code(500);
  die("Error de conexión a la BD.");
}
