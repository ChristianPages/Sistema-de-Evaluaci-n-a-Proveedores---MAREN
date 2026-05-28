<?php
/**
 * WebApp: Perfil del Proveedor - Energy Drilling & Marine
 * Estilo: EDMS Corporate Premium
 * Características: Diferenciación de ingreso, redirección dinámica y retroalimentación integrada.
 */
session_start();

// 1. SEGURIDAD: Solo proveedores autorizados
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'provider' && $_SESSION['rol'] !== 'proveedor') {
    // Nota: Ajusta 'proveedor' según el string exacto que guardes en tu BD
    if ($_SESSION['rol'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit();
}
// 2. CONEXION
require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();


$uid = $_SESSION['usuario_id'];

// Actualizar última actividad del proveedor actual
$conn->query("UPDATE usuarios SET ultima_actividad = NOW() WHERE id = $uid");

// 3. CARGAR INFORMACIÓN DEL USUARIO Y SU ÚLTIMA EVALUACIÓN
$user_query = $conn->query("SELECT * FROM usuarios WHERE id = $uid");
$user_data = $user_query->fetch_assoc();

$eval_query = $conn->query("SELECT * FROM evaluaciones WHERE usuario_id = $uid ORDER BY fecha DESC, id DESC LIMIT 1");
$eval_data = $eval_query->fetch_assoc();

// 4. LÓGICA DE DIFERENCIACIÓN (¿Nuevo o Registro Existente?)
$es_nuevo = !$eval_data; 
$estado_texto = $es_nuevo ? "Nuevo Ingreso" : "Registro Existente";
$badge_color = $es_nuevo ? "bg-info text-dark" : "bg-primary-subtle text-primary";

$nivel_clase = "";
if ($eval_data) {
    $nivel = strtoupper($eval_data['nivel'] ?? '');
    $nivel_clase = ($nivel == 'ALTO') ? 'bg-success' : (($nivel == 'MEDIO') ? 'bg-warning text-dark' : 'bg-danger');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Proveedor | Energy Drilling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --edm-blue: #001f3f;
            --edm-red: #12aa84;
            --edm-gray: #f8f9fa;
            --edm-text: #2d3436;
        }

        body { background-color: var(--edm-gray-bg); font-family: 'Segoe UI', sans-serif; color: var(--edm-text); }
        
        .profile-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: -30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-left: 5px solid <?php echo $es_nuevo ? '#0dcaf0' : 'var(--edm-blue)'; ?>;
        }

        .info-card {
            background: white;
            border: none;
            border-radius: 12px;
            transition: 0.3s;
            height: 100%;
        }
        .info-card:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.08); }

        .icon-box {
            width: 50px;
            height: 50px;
            background: var(--edm-gray-bg);
            color: var(--edm-blue);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .btn-action {
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 700;
            transition: 0.3s;
        }
        .btn-edm-blue { background: var(--edm-blue); color: white; border: none; }
        .btn-edm-blue:hover { background: #003366; color: white; }

        .stat-val { font-size: 1.8rem; font-weight: 800; color: var(--edm-blue); }
        .stat-label { font-size: 0.8rem; color: #636e72; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg shadow-sm no-print mb-0" style="background-color: white; border-bottom: 4px solid #12aa84;">
    <div class="container d-flex justify-content-between align-items-center py-2">
        <div class="d-flex align-items-center">
            <a class="navbar-brand py-0" href="#">
                <img src="Logo EDM.png" height="70" class="me-3" alt="Logo EDM">
            </a>
            <div class="border-start ps-3 d-none d-md-block">
                <h4 class="mb-0 fw-bold" style="color: #001f3f;">Perfil Proveedor</h4>
                <small class="text-muted fw-bold" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">
                    Energy Drilling & Marine
                </small>
            </div>
        </div>

        <div class="d-flex align-items-center">
            <div class="me-3 d-none d-lg-block text-end">
                <span class="d-block small text-muted">Bienvenido:</span>
                <span class="text-dark fw-bold" style="font-size: 0.9rem;">
                    <i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($user_data['empresa_nombre']); ?>
                </span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold shadow-sm" style="border: 2px solid #6c757d;">
                <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
            </a>
        </div>
    </div>
</nav>

<div style="height: 50px; background: var(--edm-blue);"></div>

<div class="container mb-5">
    
    <div class="profile-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="status-badge <?php echo $badge_color; ?> mb-2 d-inline-block">
                    <i class="bi <?php echo $es_nuevo ? 'bi-star-fill' : 'bi-check-circle-fill'; ?> me-1"></i> 
                    Proveedor: <?php echo $estado_texto; ?>
                </span>
                <h2 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($user_data['empresa_nombre']); ?></h2>
                <p class="text-muted mb-0">
                    <?php if($es_nuevo): ?>
                        <span class="text-info fw-bold"><i class="bi bi-info-circle me-1"></i> Complete su primera evaluación técnica para iniciar su expediente.</span>
                    <?php else: ?>
                        <i class="bi bi-geo-alt-fill me-1 text-danger"></i> -
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <?php if ($user_data['puede_editar']): ?>
                    <?php 
                        if ($es_nuevo) {
                            $url_destino = "index.php";
                            $icono = "bi-play-fill";
                            $texto_boton = "Iniciar Cuestionario";
                        } else {
                            $url_destino = "editar_cuestionario.php";
                            $icono = "bi-pencil-square";
                            $texto_boton = "Editar Cuestionario";
                        }
                    ?>
                    <a href="<?php echo $url_destino; ?>" class="btn btn-action btn-edm-blue shadow">
                        <i class="bi <?php echo $icono; ?> me-2"></i> <?php echo $texto_boton; ?>
                    </a>
                <?php else: ?>
                    <button class="btn btn-action btn-secondary shadow" disabled title="Bloqueado temporalmente por revisión del SGI">
                        <i class="bi bi-lock-fill me-2"></i> Evaluación en Revisión
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card info-card p-4 shadow-sm">
                <div class="icon-box"><i class="bi bi-person-badge"></i></div>
                <h5 class="fw-bold mb-3">Contacto de Acceso</h5>
                <p class="mb-1 small text-muted">Usuario del Sistema:</p>
                <p class="fw-bold mb-3"><?php echo htmlspecialchars($user_data['nombre_usuario']); ?></p>
                <p class="mb-1 small text-muted">ID Proveedor:</p>
                <p class="fw-bold">#<?php echo str_pad($user_data['id'], 5, "0", STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card info-card p-4 shadow-sm">
                <div class="icon-box" style="color: var(--edm-red);"><i class="bi bi-shield-check"></i></div>
                <h5 class="fw-bold mb-3">Estatus SGI</h5>
                <?php if (!$es_nuevo): ?>
                    <div class="stat-val"><?php echo number_format($eval_data['puntaje'], 1); ?>%</div>
                    <div class="stat-label mb-3">Último Cumplimiento</div>
                    <span class="status-badge <?php echo $nivel_clase; ?> text-white">
                        Cumplimiento: <?php echo $eval_data['nivel']; ?>
                    </span>
                <?php else: ?>
                    <div class="py-3 text-center">
                        <span class="text-muted italic small">Sin registros previos</span>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 25%"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card info-card p-4 shadow-sm">
                <div class="icon-box" style="color: #0984e3;"><i class="bi bi-calendar-event"></i></div>
                <h5 class="fw-bold mb-3">Última Evaluación</h5>
                <p class="mb-1 small text-muted">Fecha de Envío:</p>
                <p class="fw-bold mb-3"><?php echo $eval_data ? date("d/m/Y", strtotime($eval_data['fecha'])) : 'Pendiente'; ?></p>
                <p class="mb-1 small text-muted">Área que revisa:</p>
                <p class="fw-bold">QHSE - Energy Drilling</p>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-5 p-4 rounded-4" style="border-left: 6px solid <?php echo (!empty($eval_data['comentarios'])) ? 'var(--edm-red)' : 'var(--edm-blue)'; ?> !important;">
        <div class="d-flex align-items-start">
            <i class="bi bi-info-circle-fill <?php echo (!empty($eval_data['comentarios'])) ? 'text-danger' : 'text-primary'; ?> me-3 fs-3"></i>
            <div class="w-100">
                <h5 class="fw-bold text-dark"><?php echo (!empty($eval_data['comentarios'])) ? 'Observaciones Técnicas del Supervisor' : 'Estatus de su Trámite'; ?></h5>
                
                <?php if (!empty($eval_data['comentarios'])): ?>
                    <div class="p-3 rounded-3 mt-2" style="background-color: rgba(192, 0, 0, 0.05); border: 1px dashed rgba(192, 0, 0, 0.2);">
                        <p class="mb-0 text-dark fw-semibold" style="white-space: pre-line; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($eval_data['comentarios']); ?>
                        </p>
                    </div>
                    <p class="mt-3 small text-muted mb-0">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> 
                        Para solventar estos puntos, presione el botón <strong>"Editar Cuestionario"</strong> en la parte superior, cargue las nuevas evidencias y envíe de nuevo.
                    </p>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        <?php if($es_nuevo): ?>
                            Su cuenta corporativa ha sido dada de alta exitosamente. Por favor, proceda a realizar el llenado de su informe técnico y cargue los archivos PDF correspondientes haciendo clic en el botón superior.
                        <?php else: ?>
                            Su información y evidencias documentales están siendo validadas por el área QHSE. Si existen observaciones de campo o requisitos pendientes, se desplegarán en este espacio para su corrección.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm p-4 border-0">
                <div class="d-flex align-items-center mb-4">
                    <div class="icon-box me-3 mb-0" style="background: var(--edm-blue); color: white;"><i class="bi bi-bar-chart-line-fill"></i></div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: var(--edm-blue);">Criterios de Evaluación SGI</h5>
                        <p class="text-muted small mb-0">Entienda los rangos establecidos en el estándar EDM-FOR-SGI-112</p>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 border-start border-4 border-success h-100" style="background: #f8fff9;">
                            <div class="d-flex justify-content-between mb-2"><strong>ALTO</strong> <span class="text-success fw-bold">100% - 80%</span></div>
                            <p class="small text-muted mb-0">Cumplimiento idóneo. El contratista es calificado como apto para operaciones.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 border-start border-4 border-warning h-100" style="background: #fffdf5;">
                            <div class="d-flex justify-content-between mb-2"><strong>MEDIO</strong> <span class="text-warning fw-bold">79% - 60%</span></div>
                            <p class="small text-muted mb-0">Cumplimiento condicionado. Requiere plan de acción o actualización documental.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 border-start border-4 border-danger h-100" style="background: #fff8f8;">
                            <div class="d-flex justify-content-between mb-2"><strong>BAJO</strong> <span class="text-danger fw-bold">Menor a 60%</span></div>
                            <p class="small text-muted mb-0">Riesgo crítico. El nivel documental es insuficiente para los estándares corporativos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<footer class="text-center py-4 text-muted small border-top bg-white no-print">
    &copy; <?php echo date("Y"); ?> Maren Marine Energy | Sistema SGI - Todos los derechos reservados.
</footer>

</body>
</html>