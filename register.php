<?php
require_once __DIR__ . "/config.php";

function clean(string $s): string { return trim($s); }
function is_valid_email(string $email): bool { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }

function pass_strong(string $p): bool {
  // min 8, mayúscula, minúscula, número y símbolo
  return strlen($p) >= 8
    && preg_match('/[A-Z]/', $p)
    && preg_match('/[a-z]/', $p)
    && preg_match('/[0-9]/', $p)
    && preg_match('/[^A-Za-z0-9]/', $p);
}

function only_digits(string $s): bool { return preg_match('/^\d+$/', $s) === 1; }

$errors = [];
$ok = "";

// valores para re-llenar el form
$old = [
  "nombre" => "", "email" => "", "rol" => "",
  "especialidad" => "", "cedula_profesional" => "", "telefono_doctor" => "",
  "cedula" => "", "fecha_nacimiento" => "", "telefono_paciente" => "", "direccion" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  foreach ($old as $k => $_) {
    $old[$k] = clean($_POST[$k] ?? "");
  }

  // Normaliza email
  $old["email"] = strtolower($old["email"]);

  $password = $_POST["password"] ?? "";

  // Validaciones base
  if ($old["nombre"] === "") $errors["nombre"] = "El nombre es obligatorio.";
  if ($old["email"] === "" || !is_valid_email($old["email"])) $errors["email"] = "Ingresa un correo válido.";
  if ($password === "" || !pass_strong($password)) {
    $errors["password"] = "Contraseña débil: mínimo 8 caracteres, mayús, minús, número y símbolo.";
  }

  if (!in_array($old["rol"], ["doctor","paciente"], true)) {
    $errors["rol"] = "Selecciona un rol válido.";
  }

  // Validaciones por rol
  if ($old["rol"] === "doctor") {
    if ($old["especialidad"] === "") $errors["especialidad"] = "La especialidad es obligatoria.";
    if ($old["cedula_profesional"] === "") $errors["cedula_profesional"] = "La cédula profesional es obligatoria.";
    if ($old["telefono_doctor"] !== "" && (!only_digits($old["telefono_doctor"]) || strlen($old["telefono_doctor"]) !== 10)) {
      $errors["telefono_doctor"] = "Teléfono inválido (10 dígitos numéricos).";
    }
  }

  if ($old["rol"] === "paciente") {
    if ($old["cedula"] === "" || !only_digits($old["cedula"]) || strlen($old["cedula"]) !== 10) {
      $errors["cedula"] = "Cédula inválida (10 dígitos numéricos).";
    }
    if ($old["fecha_nacimiento"] === "") $errors["fecha_nacimiento"] = "La fecha de nacimiento es obligatoria.";
    if ($old["telefono_paciente"] === "" || !only_digits($old["telefono_paciente"]) || strlen($old["telefono_paciente"]) !== 10) {
      $errors["telefono_paciente"] = "Teléfono inválido (10 dígitos numéricos).";
    }
    if ($old["direccion"] === "") $errors["direccion"] = "La dirección es obligatoria.";
  }

  // Si todo OK, registrar
  if (!$errors) {
    // email único
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
    $stmt->execute([$old["email"]]);
    if ($stmt->fetch()) {
      $errors["email"] = "Ese correo ya está registrado.";
    } else {
      try {
        $pdo->beginTransaction();

        // ✅ SHA-256 (directo, 64 chars)
        $hash = hash('sha256', $password);

        $insU = $pdo->prepare("INSERT INTO usuarios (nombre,email,password_hash,rol,activo) VALUES (?,?,?,?,1)");
        $insU->execute([$old["nombre"], $old["email"], $hash, $old["rol"]]);
        $usuarioId = (int)$pdo->lastInsertId();

        if ($old["rol"] === "doctor") {
          $insD = $pdo->prepare("INSERT INTO doctores (usuario_id, especialidad, cedula_profesional, telefono) VALUES (?,?,?,?)");
          $insD->execute([$usuarioId, $old["especialidad"], $old["cedula_profesional"], $old["telefono_doctor"]]);
        } else {
          $insP = $pdo->prepare("INSERT INTO pacientes (usuario_id, cedula, fecha_nacimiento, telefono, direccion) VALUES (?,?,?,?,?)");
          $insP->execute([$usuarioId, $old["cedula"], $old["fecha_nacimiento"], $old["telefono_paciente"], $old["direccion"]]);
        }

        $pdo->commit();
        $ok = "✅ Usuario registrado correctamente. Ya puedes iniciar sesión.";
        foreach ($old as $k => $_) $old[$k] = "";
      } catch (Throwable $e) {
        $pdo->rollBack();
        $errors["general"] = "Error al registrar. Revisa los datos e intenta de nuevo.";
      }
    }
  }
}

function field_err(array $errors, string $k): string { return $errors[$k] ?? ""; }
function is_invalid(array $errors, string $k): string { return isset($errors[$k]) ? "is-invalid" : ""; }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro | Evosalud</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm rounded-4">
        <div class="card-body p-4">
          <h1 class="h4 fw-bold mb-3">Registro de usuario</h1>

          <?php if (isset($errors["general"])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors["general"]) ?></div>
          <?php endif; ?>

          <?php if ($ok !== ""): ?>
            <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
          <?php endif; ?>

          <form method="POST" class="needs-validation" novalidate id="formRegistro">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nombre *</label>
                <input class="form-control <?= is_invalid($errors,"nombre") ?>" name="nombre" value="<?= htmlspecialchars($old["nombre"]) ?>" required>
                <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"nombre") ?: "Campo obligatorio.") ?></div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Correo *</label>
                <input type="email" class="form-control <?= is_invalid($errors,"email") ?>" name="email" value="<?= htmlspecialchars($old["email"]) ?>" required>
                <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"email") ?: "Correo inválido.") ?></div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Contraseña *</label>
                <input type="password" class="form-control <?= is_invalid($errors,"password") ?>" name="password" required
                       minlength="8"
                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}">
                <div class="form-text">
                  Mín. 8 caracteres, mayúscula, minúscula, número y símbolo.
                </div>
                <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"password") ?: "Contraseña inválida.") ?></div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Rol *</label>
                <select class="form-select <?= is_invalid($errors,"rol") ?>" name="rol" id="rol" required>
                  <option value="">Seleccione...</option>
                  <option value="doctor" <?= $old["rol"]==="doctor" ? "selected" : "" ?>>Doctor</option>
                  <option value="paciente" <?= $old["rol"]==="paciente" ? "selected" : "" ?>>Paciente</option>
                </select>
                <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"rol") ?: "Selecciona un rol.") ?></div>
              </div>
            </div>

            <!-- DOCTOR -->
            <div id="camposDoctor" class="mt-4 d-none">
              <h2 class="h6 fw-bold">Datos del Doctor</h2>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Especialidad *</label>
                  <input class="form-control <?= is_invalid($errors,"especialidad") ?>" name="especialidad"
                         value="<?= htmlspecialchars($old["especialidad"]) ?>">
                  <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"especialidad") ?: "Campo obligatorio.") ?></div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Cédula profesional *</label>
                  <input class="form-control <?= is_invalid($errors,"cedula_profesional") ?>" name="cedula_profesional"
                         value="<?= htmlspecialchars($old["cedula_profesional"]) ?>">
                  <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"cedula_profesional") ?: "Campo obligatorio.") ?></div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Teléfono (10 dígitos)</label>
                  <input class="form-control <?= is_invalid($errors,"telefono_doctor") ?>" name="telefono_doctor"
                         value="<?= htmlspecialchars($old["telefono_doctor"]) ?>" inputmode="numeric" pattern="\d{10}">
                  <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"telefono_doctor") ?: "Teléfono inválido.") ?></div>
                </div>
              </div>
            </div>

            <!-- PACIENTE -->
            <div id="camposPaciente" class="mt-4 d-none">
              <h2 class="h6 fw-bold">Datos del Paciente</h2>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Cédula *</label>
                  <input class="form-control <?= is_invalid($errors,"cedula") ?>" name="cedula"
                         value="<?= htmlspecialchars($old["cedula"]) ?>" inputmode="numeric" pattern="\d{10}" required>
                  <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"cedula") ?: "Cédula inválida (10 dígitos).") ?></div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Fecha de nacimiento *</label>
                  <input type="date" class="form-control <?= is_invalid($errors,"fecha_nacimiento") ?>" name="fecha_nacimiento"
                         value="<?= htmlspecialchars($old["fecha_nacimiento"]) ?>" required>
                  <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"fecha_nacimiento") ?: "Campo obligatorio.") ?></div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Teléfono *</label>
                  <input class="form-control <?= is_invalid($errors,"telefono_paciente") ?>" name="telefono_paciente"
                         value="<?= htmlspecialchars($old["telefono_paciente"]) ?>" inputmode="numeric" pattern="\d{10}" required>
                  <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"telefono_paciente") ?: "Teléfono inválido (10 dígitos).") ?></div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Dirección *</label>
                  <input class="form-control <?= is_invalid($errors,"direccion") ?>" name="direccion"
                         value="<?= htmlspecialchars($old["direccion"]) ?>" required>
                  <div class="invalid-feedback"><?= htmlspecialchars(field_err($errors,"direccion") ?: "Campo obligatorio.") ?></div>
                </div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <button class="btn btn-primary" type="submit">Registrar</button>
              <a class="btn btn-outline-secondary" href="login.php">Ir a Login</a>
              <a class="btn btn-link" href="index.html">Volver a la web</a>
            </div>

          </form>

          <script>
            const rol = document.getElementById('rol');
            const doc = document.getElementById('camposDoctor');
            const pac = document.getElementById('camposPaciente');

            function refreshRole() {
              doc.classList.add('d-none');
              pac.classList.add('d-none');

              // quitar required para que no bloquee
              doc.querySelectorAll('input').forEach(i => i.required = false);
              pac.querySelectorAll('input').forEach(i => i.required = false);

              if (rol.value === 'doctor') {
                doc.classList.remove('d-none');
                doc.querySelector('input[name="especialidad"]').required = true;
                doc.querySelector('input[name="cedula_profesional"]').required = true;
              }
              if (rol.value === 'paciente') {
                pac.classList.remove('d-none');
                pac.querySelector('input[name="cedula"]').required = true;
                pac.querySelector('input[name="fecha_nacimiento"]').required = true;
                pac.querySelector('input[name="telefono_paciente"]').required = true;
                pac.querySelector('input[name="direccion"]').required = true;
              }
            }

            // Bootstrap validation UI
            (() => {
              const form = document.getElementById('formRegistro');
              form.addEventListener('submit', (event) => {
                refreshRole();
                if (!form.checkValidity()) {
                  event.preventDefault();
                  event.stopPropagation();
                }
                form.classList.add('was-validated');
              }, false);
            })();

            rol.addEventListener('change', refreshRole);
            refreshRole();
          </script>

        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
