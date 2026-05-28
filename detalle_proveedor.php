<?php
/**
 * WebApp: Informe Técnico de Evaluación (Detalle Proveedor)
 * Estilo: EDMS Corporate Premium - Coherencia SGI
 */
session_start();

// 1. SEGURIDAD: Solo el administrador/supervisor puede auditar
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. CONEXIÓN A LA BASE DE DATOS
require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

// 3. CAPTURA Y VALIDACIÓN DEL ID
$id_url = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consulta maestra: Trae evaluación, nombre de empresa y el correo (nombre_usuario)
$sql = "SELECT e.*, u.empresa_nombre, u.nombre_usuario as correo_registro 
        FROM evaluaciones e 
        INNER JOIN usuarios u ON e.usuario_id = u.id 
        WHERE e.id = $id_url OR e.usuario_id = $id_url 
        ORDER BY e.id DESC LIMIT 1";

$res = $conn->query($sql);
$data = $res->fetch_assoc();

if (!$data) {
    die("<h2 style='text-align:center;margin-top:50px;font-family:sans-serif;'>Error SGI: No se encontró el expediente de evaluación para el ID #$id_url</h2>");
}

$respuestas = json_decode($data['respuestas_json'], true) ?? [];

// 4. DICCIONARIO OFICIAL DE PREGUNTAS SGI (29 Criterios)
$preguntas_texto = [
    'p1_1' => '1.1 ¿Cuenta con un sistema de gestión de seguridad y salud en el trabajo?',
    'p1_2' => '1.2 ¿Tiene identificación de peligros y evaluación de riesgos para sus actividades?',
    'p1_3' => '1.3 ¿Capacita regularmente a su personal en seguridad industrial y prevención de accidentes?',
    'p1_4' => '1.4 ¿Registra e investiga incidentes y accidentes laborales?',
    'p1_5' => '1.5 ¿Exige el uso de EPP y supervisa su cumplimiento?',
    'p2_1' => '2.1 ¿Cuenta con una política ambiental o de sostenibilidad?',
    'p2_2' => '2.2 ¿Identifica y controla los impactos ambientales de sus operaciones?',
    'p2_3' => '2.3 ¿Gestiona adecuadamente residuos peligrosos y no peligrosos?',
    'p2_4' => '2.4 ¿Cumple con la normatividad ambiental aplicable?',
    'p2_5' => '2.5 ¿Promueve prácticas de uso eficiente de energía, agua y materiales?',
    'p3_1' => '3.1 ¿Cuenta con un sistema de gestión de calidad formal?',
    'p3_2' => '3.2 ¿Define estándares de calidad para sus servicios?',
    'p3_3' => '3.3 ¿Realiza controles de calidad y seguimiento al desempeño?',
    'p3_4' => '3.4 ¿Atiende y gestiona quejas o no conformidades del cliente?',
    'p4_1' => '4.1 ¿La empresa cuenta con una Política formal de respeto a los Derechos Humanos?',
    'p4_2' => '4.2 ¿Cumple con la legislación laboral vigente?',
    'p4_3' => '4.3 ¿Prohíbe trabajo infantil, forzado o bajo coacción?',
    'p4_4' => '4.4 ¿Garantiza igualdad de oportunidades y no discriminación?',
    'p4_5' => '4.5 ¿Respeta la libertad de asociación y negociación colectiva?',
    'p5_1' => '5.1 ¿Tiene criterios de responsabilidad social para sus propios proveedores o subcontratistas?',
    'p5_2' => '5.2 ¿Incorpora criterios ambientales, sociales y legales en sus procesos de compra?',
    'p5_3' => '5.3 ¿Promueve conductas éticas y combate la corrupción?',
    'p5_4' => '5.4 ¿Cuenta con un código de ética o conducta?',
    'p6_1' => '6.1 ¿Cumple con todas las leyes y regulaciones aplicables al sector petrolero?',
    'p6_2' => '6.2 ¿Está al corriente en obligaciones fiscales, laborales y de seguridad social?',
    'p6_3' => '6.3 ¿Cuenta con los permisos, registros y seguros requeridos para operar?',
    'p7_1' => '7.1 ¿Cumple con los tiempos de respuesta y entrega acordados?',
    'p7_2' => '7.2 ¿Responde oportunamente ante emergencias, incidentes o cambios operativos?',
    'p7_3' => '7.3 ¿Mantiene comunicación clara y efectiva con el cliente?'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente | <?php echo htmlspecialchars($data['empresa_nombre']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .report-card { 
            background: white; 
            max-width: 1100px; 
            margin: 20px auto; 
            border: none; 
            border-top: 5px solid #c00000; 
            border-radius: 10px;
            overflow: hidden;
        }
        .header-bg { background-color: #ffffff; border-bottom: 2px solid #eee; padding: 30px; }
        .score-display {
            background: #001f3f;
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .table thead { background-color: #001f3f; color: white; }
        .status-badge { font-size: 0.8rem; padding: 5px 12px; border-radius: 20px; font-weight: bold; }
        @media print { 
            .no-print { display: none !important; } 
            body { background: white; }
            .report-card { margin: 0; border: none; box-shadow: none; max-width: 100%; }
        }
        @media (max-width: 768px) {
            .header-bg { text-align: center; }
            .header-bg img { margin-bottom: 15px; }
            .score-display { margin-top: 20px; }
        }
    </style>
</head>
<body>

<div class="container no-print mt-4 d-flex justify-content-between">
    <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-4">
        <i class="bi bi-arrow-left me-2"></i> Dashboard
    </a>
    <button onclick="window.print()" class="btn btn-danger rounded-pill px-4 shadow">
        <i class="bi bi-printer me-2"></i> Imprimir Expediente
    </button>
</div>

<div class="report-card shadow-lg mb-5">
    <div class="header-bg">
        <div class="row align-items-center">
            <div class="col-md-3">
                <img src="Logo EDM.png" height="75" alt="Logo EDM">
            </div>
            <div class="col-md-9 text-md-end text-center mt-3 mt-md-0">
                <h3 class="fw-bold mb-0" style="color: #001f3f;">INFORME DE EVALUACIÓN</h3>
                <p class="text-muted small mb-0">Cuestionario de Evaluación a Proveedores | EDM-FOR-SGI-112 | Rev.00</p>
                <span class="badge bg-secondary">Revisión 00</span>
            </div>
        </div>
    </div>

    <div class="p-4 p-md-5">
        <div class="row mb-5">
            <div class="col-md-7 border-start border-4 border-danger ps-4">
                <h2 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($data['empresa_nombre']); ?></h2>
                <div class="row g-2">
                    <div class="col-12"><i class="bi bi-person-badge me-2 text-danger"></i><strong>Representante:</strong> <?php echo htmlspecialchars($data['representante'] ?: 'No especificado'); ?></div>
                    <div class="col-12"><i class="bi bi-envelope-at me-2 text-danger"></i><strong>Correo Registro:</strong> <?php echo htmlspecialchars($data['correo_registro']); ?></div>
                    <div class="col-12"><i class="bi bi-tools me-2 text-danger"></i><strong>Servicio/Giro:</strong> <?php echo htmlspecialchars($data['servicio'] ?: 'No especificado'); ?></div>
                    <div class="col-12"><i class="bi bi-calendar3 me-2 text-danger"></i><strong>Fecha Evaluación:</strong> <?php echo date('d/m/Y', strtotime($data['fecha'])); ?></div>
                </div>
            </div>
            
            <div class="col-md-5 mt-4 mt-md-0">
                <div class="score-display">
                    <small class="text-uppercase fw-bold opacity-75">Nivel de Cumplimiento</small>
                    <h1 class="display-3 fw-bold my-1"><?php echo number_format($data['puntaje'], 1); ?>%</h1>
                    <?php 
                        $nivel = strtoupper($data['nivel']);
                        $clase = ($nivel == 'ALTO') ? 'bg-success' : (($nivel == 'MEDIO') ? 'bg-warning text-dark' : 'bg-danger');
                    ?>
                    <div class="status-badge <?php echo $clase; ?> d-inline-block px-4 py-2 mt-2">
                        RESULTADO: <?php echo $nivel; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead>
                    <tr class="text-center">
                        <th width="80">ID</th>
                        <th class="text-start">Criterio de Evaluación Técnica</th>
                        <th width="120">Respuesta</th>
                        <th width="150" class="no-print">Evidencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($respuestas as $key => $valor): ?>
                    <tr>
                        <td class="text-center fw-bold bg-light text-muted small"><?php echo strtoupper($key); ?></td>
                        <td class="small pe-3"><?php echo $preguntas_texto[$key] ?? "Evaluación de campo: $key"; ?></td>
                        <td class="text-center">
                            <span class="fw-bold <?php echo (in_array($valor, ['Si', 'Certificado', 'Si, Certificado', 'Si, documentada', 'Si, documentado'])) ? 'text-success' : 'text-danger'; ?>">
                                <?php echo htmlspecialchars($valor); ?>
                            </span>
                        </td>
                        <td class="text-center no-print">
                            <?php 
                            $dir = "uploads/proveedor_" . $data['usuario_id'] . "/";
                            $archivos = glob($dir . $key . "_*.pdf"); 
                            if (empty($archivos) && file_exists($dir . $key . ".pdf")) { $archivos = [$dir . $key . ".pdf"]; }

                            if (!empty($archivos)) {
                                echo '<a href="'.$archivos[0].'" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                      </a>';
                            } else {
                                echo '<span class="text-muted x-small">No disponible</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div> <div class="mt-5 no-print border-top pt-4">
            <div class="card border-0 bg-light p-4 shadow-sm" style="border-left: 5px solid #c00000;">
                <h5 class="fw-bold" style="color: #001f3f;">
                    <i class="bi bi-chat-left-dots-fill me-2 text-danger"></i>Retroalimentación del Supervisor
                </h5>
                <p class="text-muted small">
                    Escriba aquí las observaciones o motivos de rechazo. Estos comentarios serán visibles para el proveedor en su panel de control.
                </p>
                
                <form action="guardar_comentarios.php" method="POST">
                    <input type="hidden" name="eval_id" value="<?php echo $data['id']; ?>">
                    
                    <div class="mb-3">
                        <textarea 
                            name="comentarios" 
                            class="form-control" 
                            rows="4" 
                            placeholder="Ej. El archivo PDF de la sección 1.1 no corresponde a una certificación vigente. Favor de actualizar."
                            style="border-radius: 8px;"
                        ><?php echo htmlspecialchars($data['comentarios'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background-color: #001f3f; border: none;">
                            <i class="bi bi-send-check me-2"></i> Guardar Observaciones
                        </button>
                    </div>
                </form>
            </div>
        </div>        
        
        <div class="mt-5 pt-4 border-top d-none d-print-block">
            <div class="row text-center">
                <div class="col-6">
                    <div style="border-bottom: 1px solid #000; width: 200px; margin: 0 auto 10px;"></div>
                    <p class="small fw-bold">Revisado por SGI</p>
                </div>
                <div class="col-6">
                    <div style="border-bottom: 1px solid #000; width: 200px; margin: 0 auto 10px;"></div>
                    <p class="small fw-bold">Firma del Proveedor</p>
                </div>
            </div>
        </div>

    </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>