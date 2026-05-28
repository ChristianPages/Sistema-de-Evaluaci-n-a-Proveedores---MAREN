<?php
/**
 * Login Responsivo EDMS
 * Sin cambios en la funcionalidad original
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. CONEXION
require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

$error="";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $conn->real_escape_string(trim($_POST['usuario']));
    $password = $_POST['password'];

    $sql = "SELECT id, nombre_usuario, password_hash, rol, puede_editar, empresa_nombre FROM usuarios WHERE nombre_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['usuario']    = $row['nombre_usuario'];
            $_SESSION['rol']        = $row['rol'];
            $_SESSION['empresa']    = $row['empresa_nombre'];

            if ($row['rol'] === 'admin') { header("Location: dashboard.php"); } 
            else if ($row['rol'] === 'proveedor') { header("Location: perfil_proveedor.php"); }
            exit();
        } else { $error = "Contraseña incorrecta."; }
    } else { $error = "El usuario no existe."; }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema | Maren Energy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --edm-blue: #001f3f;
            --edm-red: #12aa84;
        }

        body { 
            background: linear-gradient(135deg, var(--edm-blue) 0%, #12aa84 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px; /* Margen para pantallas muy pequeñas */
        }

        .login-card { 
            border-radius: 20px; 
            border: none;
            border-top: 6px solid var(--edm-red); 
            width: 100%; 
            max-width: 420px; /* Tamaño máximo en PC */
            background: white;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        /* Ajustes específicos para móviles */
        @media (max-width: 576px) {
            .login-card {
                padding: 1.5rem !important; /* Menos padding en móviles */
                border-radius: 15px;
            }
            .navbar-brand img {
                max-height: 60px !important;
            }
            h4 { font-size: 1.25rem; }
        }

        .input-group-text {
            background-color: #f8f9fa;
            color: var(--edm-blue);
            border-radius: 10px 0 0 10px;
        }

        .form-control {
            border-radius: 0 10px 10px 0;
            padding: 12px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--edm-blue);
        }

        .btn-primary { 
            background-color: var(--edm-blue); 
            border: none; 
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-primary:hover { 
            background-color: var(--edm-red); 
            transform: translateY(-2px);
        }

        .register-link {
            color: var(--edm-red);
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="card login-card p-4 p-md-5">
    <div class="text-center mb-4">
        <img src="Logo EDM.png" style="max-height: 80px; width: auto;" alt="EDM Logo">
        <div class="mt-4">
            <h4 class="fw-bold mb-1" style="color: var(--edm-blue);">Sistema de Evaluación a Proveedores</h4>
            <p class="text-muted small">Inicia Sesión como Proveedor o Administrador</p>
        </div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger d-flex align-items-center small py-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-4">
            <label class="form-label small fw-bold text-muted">CORREO ELECTRONICO</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                <input type="text" name="usuario" class="form-control" required placeholder="Correo Electronico">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold text-muted">CONTRASEÑA</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-4 shadow-sm">
            INICIAR SESIÓN
        </button>
        
        <div class="text-center border-top pt-4">
            <p class="mb-0 text-muted small">¿Nuevo en Energy Drilling?</p>
            <a href="registro_proveedor.php" class="register-link small">
                Regístrate como Nuevo Proveedor
            </a>
        </div>
    </form>
</div>

</body>
</html>