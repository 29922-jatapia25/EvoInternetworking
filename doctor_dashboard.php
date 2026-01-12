<?php
require_once __DIR__ . "/config.php";

if (empty($_SESSION["user_id"]) || ($_SESSION["rol"] ?? "") !== "doctor") {
  header("Location: login.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT u.nombre, u.email, d.especialidad, d.cedula_profesional, d.telefono
  FROM usuarios u
  JOIN doctores d ON d.usuario_id = u.id
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
  <title>Panel Doctor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card rounded-4 shadow-sm">
    <div class="card-body p-4">
      <h1 class="h4 fw-bold">Panel del Doctor</h1>
      <p class="text-muted mb-4">Bienvenido, <?= htmlspecialchars($perfil["nombre"] ?? $_SESSION["nombre"]) ?></p>

      <ul class="list-group mb-4">
        <li class="list-group-item"><b>Especialidad:</b> <?= htmlspecialchars($perfil["especialidad"] ?? "—") ?></li>
        <li class="list-group-item"><b>Cédula profesional:</b> <?= htmlspecialchars($perfil["cedula_profesional"] ?? "—") ?></li>
        <li class="list-group-item"><b>Correo:</b> <?= htmlspecialchars($perfil["email"] ?? "—") ?></li>
      </ul>

      <a class="btn btn-danger" href="logout.php">Cerrar sesión</a>
    </div>
  </div>
</div>
</body>
</html>
