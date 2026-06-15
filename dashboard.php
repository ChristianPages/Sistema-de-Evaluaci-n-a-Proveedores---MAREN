<?php
/**
 * Dashboard Administrativo - Energy Drilling & Marine
 * Diseño: Premium Responsivo v3.0
 */
/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
*/
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();
$db="";
$host="";
// Asegurar que la conexión use UTF-8 para evitar problemas con eñes o acentos
$conn->set_charset("utf8mb4");

if (isset($_SESSION['usuario_id'])) {
    $uid = (int)$_SESSION['usuario_id'];
    $conn->query("UPDATE usuarios SET ultima_actividad = NOW() WHERE id = $uid");
}

// 1. Conteo de usuarios activos
$sql_count = "SELECT COUNT(*) as total FROM usuarios WHERE ultima_actividad > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
$res_count = $conn->query($sql_count);
$online_count = $res_count ? $res_count->fetch_assoc()['total'] : 0;

// 2. Conteos para las tarjetas informativas
$total_prov  = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'proveedor'")->fetch_assoc()['total'];
$total_eval  = $conn->query("SELECT COUNT(*) as total FROM evaluaciones")->fetch_assoc()['total'];
$nivel_alto  = $conn->query("SELECT COUNT(*) as total FROM evaluaciones WHERE nivel = 'ALTO'")->fetch_assoc()['total'];
$nivel_medio = $conn->query("SELECT COUNT(*) as total FROM evaluaciones WHERE nivel = 'MEDIO'")->fetch_assoc()['total'];
$nivel_bajo  = $conn->query("SELECT COUNT(*) as total FROM evaluaciones WHERE nivel = 'BAJO'")->fetch_assoc()['total'];

// 3. Consulta principal optimizada y limpia para la tabla
$sql = "SELECT u.id as user_id, u.empresa_nombre, u.puede_editar, 
               e.id as eval_id, e.puntaje, e.nivel, e.fecha
        FROM usuarios u 
        LEFT JOIN evaluaciones e ON e.id = (
            SELECT MAX(id) 
            FROM evaluaciones 
            WHERE usuario_id = u.id
        )
        WHERE u.rol = 'proveedor' 
        ORDER BY e.fecha DESC, u.empresa_nombre ASC";

$resultado = $conn->query($sql);

/*CÓDIGO DE PRUEBA TEMPORAL (Borrar después)
echo "<div class='alert alert-dark mx-4 mt-3'>";
echo "<strong>Diagnóstico del Sistema:</strong><br>";
echo "• Intentando conectar a la base de datos: <code>$db</code> en el host: <code>$host</code><br>";

// Intentar contar TODOS los usuarios sin filtros
$prueba_total = $conn->query("SELECT COUNT(*) as t FROM usuarios");
if($prueba_total) {
    echo "• Conexión exitosa. Usuarios totales encontrados en la tabla (cualquier rol): <strong>" . $prueba_total->fetch_assoc()['t'] . "</strong><br>";
} else {
    echo "• Error al intentar leer la tabla usuarios: <strong class='text-danger'>" . $conn->error . "</strong><br>";
}
echo "</div>";

if (!$resultado) {
    die("Error en la consulta principal: " . $conn->error);
}
*/
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SGI | Energy Drilling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --edm-blue: #001f3f;
            --edm-red: #12aa84;
            --edm-gray: #f8f9fa;
        }
        body { background-color: var(--edm-gray); font-family: 'Inter', system-ui, sans-serif; color: #334155; }
        
        /* Navbar Moderna */
        .navbar-edm { background: #ffffff; border-bottom: 4px solid var(--edm-red); }
        .online-status { background: #0f172a; color: #fff; font-size: 0.75rem; border: 1px solid rgba(255,255,255,0.1); }
        
        /* Cards de Estadísticas */
        .stat-card { 
            border: none; border-radius: 16px; transition: all 0.3s ease;
            background: #fff; overflow: hidden; position: relative;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .stat-icon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        
        /* Tabla y Contenedores */
        .main-card { border: none; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); background: white; }
        .table thead th { 
            background-color: #f8fafc; color: #64748b; font-size: 0.75rem; 
            text-transform: uppercase; letter-spacing: 0.05em; border-top: none;
            padding: 15px;
        }
        .table tbody td { padding: 15px; border-bottom: 1px solid #f1f5f9; }
        
        .badge-status { 
            padding: 6px 12px; border-radius: 50px; font-weight: 700; 
            font-size: 0.65rem; min-width: 80px; text-transform: uppercase;
        }

        /* Botones de Acción */
        .btn-action { 
            width: 32px; height: 32px; padding: 0; display: inline-flex; 
            align-items: center; justify-content: center; border-radius: 8px;
            transition: 0.2s;
        }

        @media print { .no-print { display: none !important; } .main-card { box-shadow: none; border: 1px solid #ddd; } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-edm no-print mb-4 shadow-sm sticky-top">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center">
            <img src="Logo EDM.png" height="50" class="me-3">
            <div class="d-none d-md-block border-start ps-3">
                <h5 class="mb-0 fw-bold" style="color: var(--edm-blue);">Panel Administrativo</h5>
                <p class="text-muted mb-0 small fw-bold">SGI - CUMPLIMIENTO</p>
            </div>
        </div>

        <div class="ms-auto d-flex align-items-center">
            <div class="online-status rounded-pill px-3 py-1 me-3 d-flex align-items-center">
                <span class="spinner-grow spinner-grow-sm text-success me-2" style="width: 8px; height: 8px;"></span>
                <span class="fw-bold"><?php echo $online_count; ?> Activo<?php echo $online_count != 1 ? 's' : ''; ?></span>
            </div>
            
            <div class="dropdown">
                <a href="#" class="text-decoration-none d-flex align-items-center text-dark dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="text-end me-2 d-none d-lg-block">
                        <small class="d-block text-muted" style="font-size: 0.7rem;">ADMINISTRADOR</small>
                        <span class="fw-bold small"><?php echo $_SESSION['usuario']; ?></span>
                    </div>
                    <i class="bi bi-person-circle fs-3 text-secondary"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-3">
                    <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <div class="row g-4 mb-5 no-print">
        <div class="col-md-3">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small fw-bold mb-1">PROVEEDORES</p>
                        <h2 class="fw-extrabold mb-0"><?php echo $total_prov; ?></h2>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-buildings-fill fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small fw-bold mb-1">Nivel Alto</p>
                        <h2 class="fw-extrabold mb-0"><?php echo $nivel_alto; ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-clipboard-check-fill fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small fw-bold mb-1">Nivel Medio</p>
                        <h2 class="fw-extrabold mb-0 text-warning"><?php echo $nivel_medio; ?></h2>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-warning"><i class="bi bi-shield-fill-exclamation fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small fw-bold mb-1">Crticos</p>
                        <h2 class="fw-extrabold mb-0 text-danger"><?php echo $nivel_bajo; ?></h2>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-shield-fill-exclamation fs-4"></i></div>
                </div>
            </div>
        </div>
    </div>
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card main-card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-info-circle-fill text-primary me-2 fs-5"></i>
                    <h6 class="fw-bold mb-0" style="color: #001f3f;">Criterios de Evaluación</h6>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(25, 135, 84, 0.05); border: 1px solid rgba(25, 135, 84, 0.1);">
                            <div class="badge bg-success rounded-pill me-3 px-3">ALTO</div>
                            <div>
                                <span class="d-block fw-bold text-success">100% - 80%</span>
                                <small class="text-muted" style="font-size: 0.75rem;">Nivel Alto. Proveedor Aprobado.</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(255, 193, 7, 0.05); border: 1px solid rgba(255, 193, 7, 0.1);">
                            <div class="badge bg-warning text-dark rounded-pill me-3 px-3">MEDIO</div>
                            <div>
                                <span class="d-block fw-bold text-warning">79% - 60%</span>
                                <small class="text-muted" style="font-size: 0.75rem;">Nivel Medio. Aprobado con acciones de mejora</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(220, 53, 69, 0.05); border: 1px solid rgba(220, 53, 69, 0.1);">
                            <div class="badge bg-danger rounded-pill me-3 px-3">BAJO</div>
                            <div>
                                <span class="d-block fw-bold text-danger">Menor a 60%</span>
                                <small class="text-muted" style="font-size: 0.75rem;">Nivel Crítico / No Apto</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <div class="card main-card mb-5">
        <div class="card-header bg-transparent border-0 p-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="bi bi-table me-2 text-danger"></i>Control Maestro de Proveedores</h5>
            <button onclick="window.print()" class="btn btn-dark btn-sm rounded-pill px-3 no-print">
                <i class="bi bi-printer me-2"></i>Generar Reporte
            </button>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="mx-4 alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Operación finalizada con éxito.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Empresa / Registro</th>
                        <th class="text-center">Última Eval.</th>
                        <th class="text-center">Score</th>
                        <th class="text-center">Nivel</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center no-print">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-2 rounded-3 me-3 text-secondary d-none d-sm-block">
                                    <i class="bi bi-building fs-5"></i>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block"><?php echo htmlspecialchars($row['empresa_nombre']); ?></span>
                                    <span class="text-muted" style="font-size: 0.7rem;">ID: #<?php echo $row['user_id']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center small">
                            <span class="text-muted"><?php echo $row['fecha'] ? date('d M, Y', strtotime($row['fecha'])) : 'Pendiente'; ?></span>
                        </td>
                        <td class="text-center fw-bold text-primary">
                            <?php echo $row['puntaje'] ? number_format($row['puntaje'], 1) . '%' : '--'; ?>
                        </td>
                        <td class="text-center">
                            <?php 
                            $n = $row['nivel'] ?? 'N/A';
                            $c = ($n == 'ALTO') ? 'bg-success' : (($n == 'MEDIO') ? 'bg-warning text-dark' : ($n == 'BAJO' ? 'bg-danger' : 'bg-secondary'));
                            ?>
                            <span class="badge badge-status <?php echo $c; ?> shadow-sm"><?php echo $n; ?></span>
                        </td>
                        <td class="text-center">
                            <?php if($row['puede_editar']): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill" style="font-size: 0.65rem;">
                                    <i class="bi bi-unlock-fill me-1"></i> ABIERTO
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill" style="font-size: 0.65rem;">
                                    <i class="bi bi-lock-fill me-1"></i> BLOQUEADO
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center no-print">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="detalle_proveedor.php?id=<?php echo $row['user_id']; ?>" class="btn-action btn btn-outline-danger" title="Reporte PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <a href="descargar_expediente.php?id=<?php echo $row['user_id']; ?>" class="btn-action btn btn-outline-primary" title="Bajar ZIP">
                                    <i class="bi bi-cloud-arrow-down"></i>
                                </a>
                                <a href="actualizar_permiso.php?id=<?php echo $row['user_id']; ?>&estado=<?php echo $row['puede_editar']?0:1; ?>" class="btn-action btn <?php echo $row['puede_editar']?'btn-outline-warning':'btn-warning'; ?>" title="Habilitar/Cerrar">
                                    <i class="bi <?php echo $row['puede_editar']?'bi-lock':'bi-unlock'; ?>"></i>
                                </a>
                                <a href="eliminar_proveedor.php?id=<?php echo $row['user_id']; ?>" class="btn-action btn btn-outline-dark" onclick="return confirm('¿Eliminar definitivamente?')" title="Borrar">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-0 p-4 text-center">
            <small class="text-muted italic">Última actualización: <?php echo date('H:i:s'); ?> - Maren Energy | HSEQ</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>