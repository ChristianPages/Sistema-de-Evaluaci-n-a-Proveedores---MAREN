<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $empresa = $conn->real_escape_string(trim($_POST['empresa']));
    $usuario = $conn->real_escape_string(trim($_POST['usuario']));
    $password_raw = $_POST['password'];
    
    // Encriptamos la contraseña
    $password_hash = password_hash($password_raw, PASSWORD_BCRYPT);
    
    // DEFINIMOS EL ROL EXPLÍCITAMENTE
    $rol = 'proveedor'; 
    $puede_editar = 1; // Permiso para su primera evaluación

    // Insertar con todos los campos necesarios
    $sql = "INSERT INTO usuarios (nombre_usuario, password_hash, rol, puede_editar, empresa_nombre) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // "sssis" -> string, string, string, int, string
    $stmt->bind_param("sssis", $usuario, $password_hash, $rol, $puede_editar, $empresa);

    if ($stmt->execute()) {
        $id_nuevo = $conn->insert_id;

        // Crear carpeta física usando el ID del nuevo usuario
        $ruta_carpeta = "uploads/proveedor_" . $id_nuevo;
        if (!is_dir($ruta_carpeta)) {
            mkdir($ruta_carpeta, 0755, true);
        }

        $mensaje = "<div class='alert alert-success'>Registro exitoso. Ahora puedes iniciar sesión.</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error al registrar: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Proveedores | EDM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background: #333645; display: flex; align-items: center; height: 100vh;">
    <div class="container">
        <div class="card mx-auto shadow-lg" style="max-width: 450px; border-top: 5px solid #004a99;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="Logo EDM.png" style="max-height: 60px;">
                    <h4 class="fw-bold mt-2">Nuevo Proveedor</h4>
                </div>

                <?php echo $mensaje; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre de la Empresa</label>
                        <input type="text" name="empresa" class="form-control" required placeholder="Ej: Maren Marine Energy">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email: </label>
                        <input type="text" name="usuario" class="form-control" required placeholder="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contraseña</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">CREAR CUENTA</button>
                    <div class="text-center mt-3">
                        <a href="login.php" class="small text-muted text-decoration-none">¿Ya tienes cuenta? Ingresa aquí</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>