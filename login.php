<?php
require_once __DIR__ . "/config.php";

if (!empty($_SESSION["user_id"])) {
  if (($_SESSION["rol"] ?? "") === "doctor") {
    header("Location: doctor_dashboard.php"); exit;
  } elseif (($_SESSION["rol"] ?? "") === "paciente") {
    header("Location: paciente_dashboard.php"); exit;
  } else {
    header("Location: admin_dashboard.php"); exit;
  }
}

$error = "";
$email_val = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $email = strtolower($email);
  $pass  = $_POST["password"] ?? "";

  $email_val = $email;

  if ($email === "" || $pass === "") {
    $error = "Completa correo y contraseña.";
  } else {
    $stmt = $pdo->prepare("SELECT id,nombre,email,password_hash,rol,activo FROM usuarios WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // ✅ SHA-256 (64)
    if (!$user || (int)$user["activo"] !== 1 || hash('sha256', $pass) !== $user["password_hash"]) {
      $error = "Credenciales incorrectas.";
    } else {
      $_SESSION["user_id"] = (int)$user["id"];
      $_SESSION["nombre"]  = $user["nombre"];
      $_SESSION["rol"]     = $user["rol"];

      if ($user["rol"] === "doctor") {
        header("Location: doctor_dashboard.php");
      } elseif ($user["rol"] === "paciente") {
        header("Location: paciente_dashboard.php");
      } else {
        header("Location: admin_dashboard.php");
      }
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | Evosalud</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm rounded-4">
          <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-3">Ingreso</h1>

            <?php if ($error !== ""): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Correo</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email_val) ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <button class="btn btn-primary w-100" type="submit">Ingresar</button>
              <a class="btn btn-outline-secondary w-100 mt-2" href="register.php">Crear cuenta</a>
              <a class="btn btn-link w-100 mt-2" href="index.html">Volver</a>
            </form>

            <div class="text-muted small mt-3">
              Demo doctor: doctor@evosalud.com / Doctor123* <br>
              Demo paciente: paciente@evosalud.com / Paciente123* <br>
              (Usuarios reseteados: 12345678)
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
