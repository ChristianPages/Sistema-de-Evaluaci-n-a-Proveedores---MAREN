<?php
/**
 * WebApp: Cuestionario SGI - Energy Drilling & Marine
 * Estilo: Maren Energy Corporate Edition (Blanco, #12aa85, Azul Profundo)
 * Versión: Completa (29 Preguntas / 7 Secciones) con Persistencia de Avance
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. SEGURIDAD Y CONEXIÓN
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'proveedor') {
    header("Location: login.php"); exit();
}

require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

$uid = $_SESSION['usuario_id'];

// Obtener datos base de la empresa
$user_info = $conn->query("SELECT empresa_nombre FROM usuarios WHERE id = $uid")->fetch_assoc();
$empresa_fija = $user_info['empresa_nombre'];

// Cargar la última evaluación (por si existe un borrador guardado)
$prev_eval = $conn->query("SELECT * FROM evaluaciones WHERE usuario_id = $uid ORDER BY id DESC LIMIT 1")->fetch_assoc();
$respuestas_guardadas = $prev_eval ? json_decode($prev_eval['respuestas_json'], true) : [];

// 2. PROCESAMIENTO DE ENVÍO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dir = "uploads/proveedor_" . $uid . "/";
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }

    // Detectar acción del usuario (guardar avance o enviar definitivo)
    $accion = isset($_POST['accion']) ? $_POST['accion'] : 'guardar';

    // IDs de preguntas que requieren evidencia PDF
    $preguntas_con_pdf = ['p1_1','p1_2','p1_3','p1_5','p2_2','p2_3','p2_4','p2_5','p3_1','p3_2','p3_3','p4_1','p4_3','p5_4','p7_2'];
    foreach ($preguntas_con_pdf as $id_f) {
        if (isset($_FILES["file_$id_f"]) && $_FILES["file_$id_f"]['error'] == 0) {
            $viejos = glob($dir . $id_f . "*.pdf");
            foreach($viejos as $v) { @unlink($v); }
            move_uploaded_file($_FILES["file_$id_f"]['tmp_name'], $dir . $id_f . ".pdf");
        }
    }

    $respuestas_form = []; $si = 0; $total = 0;
    foreach ($_POST as $key => $val) {
        if (strpos($key, 'p') === 0) {
            $respuestas_form[$key] = $val; $total++;
            if (in_array($val, ["Si", "Certificado", "Si, Certificado", "Si, documentada", "Si, documentado"])) $si++;
        }
    }

    $json = json_encode($respuestas_form, JSON_UNESCAPED_UNICODE);
    // SOLUCIÓN: Fijar el denominador al total real de preguntas del SGI (29 criterios)
    $total_preguntas_sgi = 29; 
    $porcentaje = ($si / $total_preguntas_sgi) * 100;
    
    $nivel = ($porcentaje >= 80) ? "ALTO" : (($porcentaje >= 60) ? "MEDIO" : "BAJO");

    // Evaluar si corresponde hacer un UPDATE (Borrador existente) o un INSERT (Nuevo total)
    if ($prev_eval && isset($prev_eval['id'])) {
        $id_eval_antigua = $prev_eval['id'];
        $stmt = $conn->prepare("UPDATE evaluaciones SET empresa = ?, servicio = ?, fecha = ?, puntaje = ?, nivel = ?, representante = ?, email_contacto = ?, respuestas_json = ?, comentarios = NULL WHERE id = ?");
        $stmt->bind_param("sssdssssi", $empresa_fija, $_POST['servicio'], $fecha_act, $porcentaje, $nivel, $_POST['representante'], $_POST['email_contacto'], $json, $id_eval_antigua);
    } else {
        $stmt = $conn->prepare("INSERT INTO evaluaciones (usuario_id, empresa, servicio, fecha, puntaje, nivel, representante, email_contacto, departamento, respuestas_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'SGI', ?)");
        $stmt->bind_param("isssdssss", $uid, $empresa_fija, $_POST['servicio'], $fecha_act, $porcentaje, $nivel, $_POST['representante'], $_POST['email_contacto'], $json);
    }
    
    if($stmt->execute()){
        if ($accion === 'enviar') {
            // Congelar acceso del proveedor para revisión del SGI
            $conn->query("UPDATE usuarios SET puede_editar = 0 WHERE id = $uid");
            header("Location: perfil_proveedor.php?msg=enviado"); 
        } else {
            // Retornar al perfil manteniendo el formulario abierto para edición
            header("Location: perfil_proveedor.php?msg=progreso_guardado"); 
        }
        exit();
    }
}

// 3. FUNCIÓN RENDERIZADO DE TARJETAS PREMIUM (Con lectura de memoria interna)
function renderQuestion($id, $pregunta, $opciones, $archivo_req = false) {
    global $respuestas_guardadas, $uid;
    $valor_previo = $respuestas_guardadas[$id] ?? '';
    
    // Verificar si ya existe físicamente el archivo soporte en el servidor
    $dir = "uploads/proveedor_" . $uid . "/";
    $busqueda = glob($dir . $id . "*.pdf");
    $archivo_existe = !empty($busqueda);
    $nombre_archivo = $archivo_existe ? basename($busqueda[0]) : '';
?>
    <div class="card-question mb-4 shadow-sm">
        <div class="question-title"><?php echo $pregunta; ?></div>
        
        <div class="options-container">
            <?php foreach ($opciones as $opt): ?>
                <input type="radio" class="btn-check" name="<?php echo $id; ?>" id="<?php echo $id.$opt; ?>" value="<?php echo $opt; ?>" required <?php echo ($valor_previo === $opt) ? 'checked' : ''; ?>>
                <label class="btn btn-pill" for="<?php echo $id.$opt; ?>"><?php echo $opt; ?></label>
            <?php endforeach; ?>
        </div>

        <?php if ($archivo_req): ?>
            <div class="upload-wrapper">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <label class="custom-file-upload shadow-sm mb-0">
                        <input type="file" name="file_<?php echo $id; ?>" class="d-none" accept=".pdf" onchange="updateFileInfo(this)">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i> Subir Soporte
                    </label>
                    <span class="file-name-display <?php echo $archivo_existe ? 'text-status-ok' : 'text-status-none'; ?>">
                        <?php if ($archivo_existe): ?>
                            <i class="bi bi-check-circle-fill"></i> Cargado: <?php echo $nombre_archivo; ?>
                        <?php else: ?>
                            <i class="bi bi-file-earmark-pdf"></i> Evidencia requerida (PDF)
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php } ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI | Energy Drilling & Marine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --edm-blue: #12a885; 
            --edm-red: #1C0573;  
            --edm-gray: #f2f2f2; 
            --edm-white: #ffffff;
        }

        body { background-color: var(--edm-gray); font-family: 'Segoe UI', system-ui, sans-serif; color: #333; }
        .navbar-edm { background: var(--edm-blue); border-bottom: 4px solid var(--edm-red); padding: 1.2rem; }
        .hero-card { background: var(--edm-white); border-radius: 15px; border: none; }
        
        .section-header { 
            background: var(--edm-blue); 
            color: white; 
            padding: 12px 25px; 
            border-radius: 8px; 
            margin: 45px 0 20px; 
            border-right: 10px solid var(--edm-red); 
            font-weight: 700; 
            text-transform: uppercase; 
            font-size: 0.8rem; 
            letter-spacing: 1px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card-question { background: var(--edm-white); border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.8rem; transition: 0.3s; }
        .card-question:hover { border-color: var(--edm-blue); transform: translateY(-2px); }
        .question-title { font-size: 0.95rem; color: var(--edm-blue); font-weight: 700; margin-bottom: 1.2rem; line-height: 1.4; }

        .options-container { display: flex; flex-wrap: wrap; gap: 10px; }
        .btn-check + .btn-pill { 
            background-color: #f8f9fa; border: 1px solid #dee2e6; color: #495057; 
            border-radius: 50px; padding: 0.5rem 1.4rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; 
        }
        .btn-check:checked + .btn-pill { 
            background-color: var(--edm-blue); border-color: var(--edm-blue); color: white; 
            box-shadow: 0 4px 8px rgba(0, 31, 63, 0.2); 
        }

        .upload-wrapper { margin-top: 1.2rem; padding-top: 1.2rem; border-top: 1px solid #f1f5f9; }
        .custom-file-upload { 
            display: inline-flex; align-items: center; padding: 8px 16px; 
            background: #f1f5f9; color: #475569; border-radius: 8px; font-size: 0.8rem; 
            font-weight: 700; cursor: pointer; transition: 0.2s; 
        }
        .custom-file-upload:hover { background: #e2e8f0; color: var(--edm-blue); }
        .file-name-display { font-size: 0.75rem; margin-left: 10px; font-weight: 600; }
        .text-status-none { color: #94a3b8; }
        .text-status-ok { color: #10b981; }
    </style>
</head>
<body>

<nav class="navbar-edm text-center shadow mb-5" style="background-color:#f2f2f2">
    <img src="Logo EDM.png" height="80" alt="Logo EDM">
</nav>

<div class="container pb-5" style="max-width: 900px;">
    
    <form method="POST" enctype="multipart/form-data">

        <div class="card hero-card p-5 shadow-sm mb-5">
            <h3 class="fw-bold text-center mb-1" style="color: var(--edm-blue);">CUESTIONARIO DE PROVEEDORES</h3>
            <p class="text-center text-muted small mb-4">EDM-FOR-SGI-112 | Rev.00</p>
            <hr>
            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label class="small fw-bold text-muted">Empresa Registrada</label>
                    <input type="text" class="form-control border-0 bg-light" value="<?php echo htmlspecialchars($empresa_fija); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-muted">Representante Autorizado</label>
                    <input type="text" name="representante" class="form-control border-0 bg-light" placeholder="Nombre completo" value="<?php echo htmlspecialchars($prev_eval['representante'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold text-muted">Email Corporativo</label>
                    <input type="email" name="email_contacto" class="form-control border-0 bg-light" placeholder="email@empresa.com" value="<?php echo htmlspecialchars($prev_eval['email_contacto'] ?? ''); ?>" required>
                </div>
                <div class="col-12">
                    <label class="small fw-bold text-muted">Servicio o Especialidad</label>
                    <input type="text" name="servicio" class="form-control border-0 bg-light" placeholder="Ej: Mantenimiento Industrial" value="<?php echo htmlspecialchars($prev_eval['servicio'] ?? ''); ?>" required>
                </div>
            </div>
        </div>

        <div class="section-header">1. Seguridad y Salud en el Trabajo</div>
        <?php 
            renderQuestion('p1_1', '1.1 ¿Cuenta con un sistema de gestión de seguridad y salud en el trabajo?', ['Si, Certificado', 'Implementando', 'No'], true);
            renderQuestion('p1_2', '1.2 ¿Tiene identificación de peligros y evaluación de riesgos (IPER)?', ['Si', 'Parcial', 'No'], true);
            renderQuestion('p1_3', '1.3 ¿Capacita regularmente a su personal en seguridad industrial?', ['Si', 'Ocasional', 'No'], true);
            renderQuestion('p1_4', '1.4 ¿Registra e investiga incidentes y accidentes laborales?', ['Si', 'No']);
            renderQuestion('p1_5', '1.5 ¿Exige el uso de EPP y supervisa su cumplimiento?', ['Si', 'Parcial', 'No'], true);
        ?>

        <div class="section-header">2. Medio Ambiente</div>
        <?php 
            renderQuestion('p2_1', '2.1 ¿Cuenta con una política ambiental o de sostenibilidad?', ['Si', 'En desarrollo', 'No']);
            renderQuestion('p2_2', '2.2 ¿Identifica y controla los impactos ambientales de sus operaciones?', ['Si', 'Parcial', 'No'], true);
            renderQuestion('p2_3', '2.3 ¿Gestiona adecuadamente residuos peligrosos y no peligrosos?', ['Si', 'Parcial', 'No'], true);
            renderQuestion('p2_4', '2.4 ¿Cumple con la normatividad ambiental aplicable?', ['Si', 'No'], true);
            renderQuestion('p2_5', '2.5 ¿Promueve prácticas de uso eficiente de energía, agua y materiales?', ['Si', 'Parcial', 'No'], true);
        ?>

        <div class="section-header">3. Calidad del Servicio</div>
        <?php 
            renderQuestion('p3_1', '3.1 ¿Cuenta con un sistema de gestión de calidad formal (ISO 9001)?', ['Certificado', 'Implementando', 'No'], true);
            renderQuestion('p3_2', '3.2 ¿Define estándares de calidad para los servicios que ofrece?', ['Si', 'No'], true);
            renderQuestion('p3_3', '3.3 ¿Realiza controles de calidad y seguimiento al desempeño?', ['Si', 'Ocasional', 'No'], true);
            renderQuestion('p3_4', '3.4 ¿Atiende y gestiona quejas o no conformidades del cliente?', ['Si', 'No']);
        ?>

        <div class="section-header">4. Derechos Humanos y Laborales</div>
        <?php 
            renderQuestion('p4_1', '4.1 ¿Cuenta con una política formal de respeto a los Derechos Humanos?', ['Si, documentada', 'Parcial', 'No'], true);
            renderQuestion('p4_2', '4.2 ¿Cumple con la legislación laboral vigente?', ['Si', 'Parcial', 'No']);
            renderQuestion('p4_3', '4.3 ¿Prohíbe expresamente el trabajo infantil, forzado o bajo coacción?', ['Si, documentado', 'Parcial', 'No'], true);
            renderQuestion('p4_4', '4.4 ¿Garantiza igualdad de oportunidades?', ['Si', 'Parcial', 'No']);
            renderQuestion('p4_5', '4.5 ¿Respeta la libertad de asociación?', ['Si', 'No', 'No aplica']);
        ?>

        <div class="section-header">5. Responsabilidad Social</div>
        <?php 
            renderQuestion('p5_1', '5.1 ¿Tiene criterios de responsabilidad social para seleccionar proveedores?', ['Si', 'Parcial', 'No']);
            renderQuestion('p5_2', '5.2 ¿Incorpora criterios ambientales y sociales en sus procesos?', ['Si', 'Parcial', 'No']);
            renderQuestion('p5_3', '5.3 ¿Promueve conductas éticas y combate la corrupción?', ['Si', 'No']);
            renderQuestion('p5_4', '5.4 ¿Cuenta con un código de ética o conducta?', ['Si', 'No'], true);
        ?>

        <div class="section-header">6. Legalidad</div>
        <?php 
            renderQuestion('p6_1', '6.1 ¿Cumple con todas las leyes aplicables al sector petrolero?', ['Si', 'No']);
            renderQuestion('p6_2', '6.2 ¿Está al corriente en obligaciones fiscales y de IMSS?', ['Si', 'No']);
            renderQuestion('p6_3', '6.3 ¿Cuenta con los permisos y seguros requeridos para operar?', ['Si', 'Parcial', 'No']);
        ?>

        <div class="section-header">7. Capacidad de Respuesta</div>
        <?php 
            renderQuestion('p7_1', '7.1 ¿Cumple con los tiempos de entrega acordados?', ['Si', 'A veces', 'No']);
            renderQuestion('p7_2', '7.2 ¿Responde oportunamente ante emergencias o incidentes?', ['Si', 'Parcial', 'No'], true);
            renderQuestion('p7_3', '7.3 ¿Mantiene comunicación clara y efectiva con el cliente?', ['Si', 'No']);
        ?>

        <div class="row g-3 mt-5 pb-5 no-print">
            <div class="col-md-6">
                <button type="submit" name="accion" value="guardar" formnovalidate class="btn btn-lg w-100 fw-bold py-3 rounded-pill shadow-sm" style="border: 2px solid var(--edm-blue); color: var(--edm-blue); background: transparent;">
                    <i class="bi bi-floppy-fill me-2"></i> GUARDAR AVANCE (SEGUIR DESPUÉS)
                </button>
            </div>
            <div class="col-md-6">
                <button type="submit" name="accion" value="enviar" class="btn btn-lg w-100 fw-bold py-3 rounded-pill text-white shadow-lg" style="background-color: var(--edm-red); border: none;" onclick="return confirm('¿Está seguro de enviar el cuestionario para revisión final? Una vez enviado, su expediente quedará congelado en el sistema.')">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> ENVIAR PARA REVISIÓN FINAL
                </button>
            </div>
            <p class="text-center text-muted small mt-3">Toda la información proporcionada será validada por el personal de Energy Drilling.</p>
        </div>

    </form>
</div>

<script>
    function updateFileInfo(input) {
        const display = input.parentElement.nextElementSibling;
        if (input.files.length > 0) {
            display.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${input.files[0].name}`;
            display.classList.remove('text-status-none');
            display.classList.add('text-status-ok');
        } else {
            display.innerHTML = `<i class="bi bi-file-earmark-pdf"></i> Evidencia requerida`;
            display.classList.remove('text-status-ok');
            display.classList.add('text-status-none');
        }
    }
</script>

</body>
</html>