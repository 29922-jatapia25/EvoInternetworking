<?php
require_once __DIR__ . "/config.php";

if (empty($_SESSION["user_id"]) || ($_SESSION["rol"] ?? "") !== "paciente") {
  header("Location: login.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT u.nombre, u.email, p.cedula, p.fecha_nacimiento, p.telefono, p.direccion
  FROM usuarios u
  JOIN pacientes p ON p.usuario_id = u.id
  WHERE u.id = ?
  LIMIT 1
");
$stmt->execute([$_SESSION["user_id"]]);
$perfil = $stmt->fetch();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel Paciente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card rounded-4 shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 fw-bold">Panel del Paciente</h1>
      <p class="text-muted mb-4">Hola, <?= htmlspecialchars($perfil["nombre"] ?? $_SESSION["nombre"]) ?></p>

      <ul class="list-group mb-4">
        <li class="list-group-item"><b>Cédula:</b> <?= htmlspecialchars($perfil["cedula"] ?? "—") ?></li>
        <li class="list-group-item"><b>Fecha nac.:</b> <?= htmlspecialchars($perfil["fecha_nacimiento"] ?? "—") ?></li>
        <li class="list-group-item"><b>Dirección:</b> <?= htmlspecialchars($perfil["direccion"] ?? "—") ?></li>
      </ul>

      <a class="btn btn-danger" href="logout.php">Cerrar sesión</a>
    </div>
  </div>
</div>
</body>
</html>
