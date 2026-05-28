<?php
 // Reportar todos los errores de PHP
error_reporting(E_ALL);

// Forzar la visualización de errores en pantalla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $conn->real_escape_string($_POST['nombre_usuario']);
    $pass = $_POST['password'];
    $rol  = $_POST['rol'];

    // Encriptar contraseña según tu columna password_hash
    $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, password_hash, rol) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user, $hashed_pass, $rol);

    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success'>Usuario registrado. <a href='login.php'>Loguéate aquí</a></div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error: El nombre de usuario ya existe.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | EDM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #c00000; height: 100vh; display: flex; align-items: center; }
        .card { border-top: 5px solid #004a99; border-radius: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow mx-auto p-4" style="max-width: 400px;">
        <div class="text-center mb-3">
            <img src="images/Logo EDM.png" style="max-height: 60px;">
            <h5 class="mt-2 fw-bold">Crear Cuenta</h5>
        </div>
        <?php echo $msg; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="small fw-bold">Nombre de Usuario</label>
                <input type="text" name="nombre_usuario" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="small fw-bold">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="small fw-bold">Rol</label>
                <select name="rol" class="form-select">
                    <option value="usuario">Usuario</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">REGISTRAR</button>
        </form>
    </div>
</div>
</body>
</html>