<?php
/**
 * WebApp: Actualización Integral SGI - Energy Drilling & Marine
 * Versión: 29 Preguntas / 7 Secciones con Guardado de Avance Parcial
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

// 2. CONEXION
require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

$uid = $_SESSION['usuario_id'];

if ($conn->connect_error) { die("Error de conexión"); }

// 1. CARGAR DATOS PREVIOS
$user_info = $conn->query("SELECT empresa_nombre FROM usuarios WHERE id = $uid")->fetch_assoc();
$prev_eval = $conn->query("SELECT * FROM evaluaciones WHERE usuario_id = $uid ORDER BY id DESC LIMIT 1")->fetch_assoc();

if (!$prev_eval) { header("Location: index.php"); exit(); }

$resp_viejas = json_decode($prev_eval['respuestas_json'], true) ?? [];
$empresa_fija = $user_info['empresa_nombre'];

// 2. PROCESAR ACTUALIZACIÓN (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dir = "uploads/proveedor_" . $uid . "/";
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }

    $accion = isset($_POST['accion']) ? $_POST['accion'] : 'guardar';

    // IDs de preguntas con soporte PDF
    $preguntas_pdf = ['p1_1','p1_2','p1_3','p1_5','p2_2','p2_3','p2_4','p2_5','p3_1','p3_2','p3_3','p4_1','p4_3','p5_4','p7_2'];
    
    foreach ($preguntas_pdf as $id_f) {
        if (isset($_FILES["file_$id_f"]) && $_FILES["file_$id_f"]['error'] == 0) {
            $viejos = glob($dir . $id_f . "*.pdf");
            foreach($viejos as $v) { @unlink($v); }
            move_uploaded_file($_FILES["file_$id_f"]['tmp_name'], $dir . $id_f . ".pdf");
        }
    }

    $nuevas_resp = []; $si = 0;
    foreach ($_POST as $key => $val) {
        if (strpos($key, 'p') === 0) {
            $nuevas_resp[$key] = $val;
            if (in_array($val, ["Si", "Certificado", "Si, Certificado", "Si, documentada", "Si, documentado"])) $si++;
        }
    }

    $json = json_encode($nuevas_resp, JSON_UNESCAPED_UNICODE);
    
    $total_preguntas_sgi = 29; 
    $porcentaje = ($si / $total_preguntas_sgi) * 100;
    $nivel = ($porcentaje >= 80) ? "ALTO" : (($porcentaje >= 60) ? "MEDIO" : "BAJO");
    $fecha = date('Y-m-d');

    $id_eval_antigua = $prev_eval['id'];
    $comentarios_db = ($accion === 'enviar') ? null : $prev_eval['comentarios'];

    $stmt = $conn->prepare("UPDATE evaluaciones SET empresa = ?, servicio = ?, fecha = ?, puntaje = ?, nivel = ?, representante = ?, email_contacto = ?, respuestas_json = ?, comentarios = ? WHERE id = ?");
    $stmt->bind_param("sssdsssssi", $empresa_fija, $_POST['servicio'], $fecha, $porcentaje, $nivel, $_POST['representante'], $_POST['email_contacto'], $json, $comentarios_db, $id_eval_antigua);
    
    if($stmt->execute()){
        if ($accion === 'enviar') {
            $conn->query("UPDATE usuarios SET puede_editar = 0 WHERE id = $uid");
            header("Location: perfil_proveedor.php?msg=enviado"); 
        } else {
            header("Location: perfil_proveedor.php?msg=progreso_guardado"); 
        }
        exit();
    } else {
        die("Error crítico al ejecutar la actualización SGI: " . $stmt->error);
    }
}

// 3. FUNCIÓN RENDERIZADO
function renderEdit($id, $pregunta, $opciones, $resp_viejas) {
    $uid_s = $_SESSION['usuario_id'];
    $valor = $resp_viejas[$id] ?? '';
    $dir = "uploads/proveedor_" . $uid_s . "/";
    $busqueda = glob($dir . $id . "*.pdf");
    $archivo = (!empty($busqueda)) ? basename($busqueda[0]) : null;
    $arch_req = in_array($id, ['p1_1','p1_2','p1_3','p1_5','p2_2','p2_3','p2_4','p2_5','p3_1','p3_2','p3_3','p4_1','p4_3','p5_4','p7_2']);
?>
    <div class="card-question mb-4 shadow-sm border-0">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3 text-dark"><?php echo $pregunta; ?></h6>
            <div class="options-container mb-3">
                <?php foreach ($opciones as $o): ?>
                    <input type="radio" class="btn-check" name="<?php echo $id; ?>" id="<?php echo $id.$o; ?>" value="<?php echo $o; ?>" required <?php echo ($valor == $o) ? 'checked' : ''; ?>>
                    <label class="btn btn-pill" for="<?php echo $id.$o; ?>"><?php echo $o; ?></label>
                <?php endforeach; ?>
            </div>
            <?php if ($arch_req): ?>
                <div class="upload-area p-2 rounded-3 border" style="background: #f8fafc; border: 1px dashed #cbd5e1 !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="btn btn-sm btn-dark px-3 rounded-pill mb-0">
                            <i class="bi bi-file-earmark-arrow-up"></i> Cambiar PDF
                            <input type="file" name="file_<?php echo $id; ?>" class="d-none" accept=".pdf" onchange="updateLabel(this)">
                        </label>
                        <span class="small fw-bold <?php echo $archivo ? 'text-success' : 'text-muted'; ?>">
                            <?php echo $archivo ? "<i class='bi bi-check-circle-fill'></i> $archivo" : "<i class='bi bi-x-circle'></i> Sin archivo"; ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php } ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar SGI | EDMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --edm-blue: #230094; --edm-red: #12AA85; --edm-bg: #f1f5f9; -- edm-top:#eeedf0; }
        body { background: var(--edm-bg); font-family: 'Segoe UI', sans-serif; }
        .navbar-edm { background: var(--edm-top); border-bottom: 4px solid var(--edm-red); padding: 1rem; }
        .section-header { background: var(--edm-blue); color: white; padding: 10px 20px; border-radius: 8px; margin: 40px 0 20px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-right: 6px solid var(--edm-red); }
        .card-question { background: white; border-radius: 12px; }
        .btn-check + .btn-pill { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 50px; padding: 0.4rem 1.2rem; font-size: 0.8rem; font-weight: 600; }
        .btn-check:checked + .btn-pill { background: var(--edm-blue); color: white; border-color: var(--edm-blue); }
        .header-box { background: white; border-radius: 15px; border-left: 6px solid var(--edm-red); }
    </style>
</head>
<body>

<nav class="navbar-edm text-center shadow mb-5"><img src="Logo EDM.png" height="50"></nav>

<div class="container pb-5" style="max-width: 850px;">
    
    <form method="POST" enctype="multipart/form-data" id="formSGI" novalidate>

        <div class="card header-box p-4 shadow-sm mb-5">
            <h4 class="fw-bold text-dark">ACTUALIZACIÓN DE CUESTIONARIO SGI</h4>
            <div class="row g-3 mt-2">
                <div class="col-md-6"><label class="small fw-bold text-muted">Representante:</label><input type="text" name="representante" id="rep_input" class="form-control" value="<?php echo htmlspecialchars($prev_eval['representante'] ?? ''); ?>" required></div>
                <div class="col-md-6"><label class="small fw-bold text-muted">Email:</label><input type="email" name="email_contacto" id="email_input" class="form-control" value="<?php echo htmlspecialchars($prev_eval['email_contacto'] ?? ''); ?>" required></div>
                <div class="col-12"><label class="small fw-bold text-muted">Servicio:</label><input type="text" name="servicio" id="ser_input" class="form-control" value="<?php echo htmlspecialchars($prev_eval['servicio'] ?? ''); ?>" required></div>
            </div>
        </div>

        <div class="section-header">1. Seguridad y Salud en el Trabajo</div>
        <?php 
            renderEdit('p1_1', '1.1 ¿Sistema de gestión de seguridad?', ['Si, Certificado', 'Implementando', 'No'], $resp_viejas);
            renderEdit('p1_2', '1.2 ¿Identificación de peligros (IPER)?', ['Si', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p1_3', '1.3 ¿Capacitación en prevención?', ['Si', 'Ocasional', 'No'], $resp_viejas);
            renderEdit('p1_4', '1.4 ¿Investigación de accidentes?', ['Si', 'No'], $resp_viejas);
            renderEdit('p1_5', '1.5 ¿Supervisión de uso de EPP?', ['Si', 'Parcial', 'No'], $resp_viejas);
        ?>

        <div class="section-header">2. Medio Ambiente</div>
        <?php 
            renderEdit('p2_1', '2.1 ¿Política ambiental?', ['Si', 'En desarrollo', 'No'], $resp_viejas);
            renderEdit('p2_2', '2.2 ¿Control de impactos ambientales?', ['Si', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p2_3', '2.3 ¿Gestión de residuos peligrosos?', ['Si', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p2_4', '2.4 ¿Cumplimiento normativo ambiental?', ['Si', 'No'], $resp_viejas);
            renderEdit('p2_5', '2.5 ¿Uso eficiente de recursos?', ['Si', 'Parcial', 'No'], $resp_viejas);
        ?>

        <div class="section-header">3. Calidad del Servicio</div>
        <?php 
            renderEdit('p3_1', '3.1 ¿Gestión de calidad (ISO 9001)?', ['Certificado', 'Implementando', 'No'], $resp_viejas);
            renderEdit('p3_2', '3.2 ¿Están definidos los estándares de calidad?', ['Si', 'No'], $resp_viejas);
            renderEdit('p3_3', '3.3 ¿Controles de calidad regulares?', ['Si', 'Ocasional', 'No'], $resp_viejas);
            renderEdit('p3_4', '3.4 ¿Gestión de quejas de clientes?', ['Si', 'No'], $resp_viejas);
        ?>

        <div class="section-header">4. Derechos Humanos y Laborales</div>
        <?php 
            renderEdit('p4_1', '4.1 ¿Política de Derechos Humanos?', ['Si, documentada', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p4_2', '4.2 ¿Cumple con leyes laborales?', ['Si', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p4_3', '4.3 ¿Prohíbe el trabajo infantil?', ['Si, documentado', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p4_4', '4.4 ¿Igualdad de oportunidades?', ['Si', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p4_5', '4.5 ¿Libertad de asociación?', ['Si', 'No', 'No aplica'], $resp_viejas);
        ?>

        <div class="section-header">5. Responsabilidad Social</div>
        <?php 
            renderEdit('p5_1', '5.1 ¿Criterios sociales para subcontratos?', ['Si', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p5_2', '5.2 ¿Criterios ambientales en compras?', ['Si', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p5_3', '5.3 ¿Promueve conductas éticas y combate la corrupción?', ['Si', 'No'], $resp_viejas); 
            renderEdit('p5_4', '5.4 ¿Código de ética o conducta?', ['Si', 'No'], $resp_viejas);
        ?>

        <div class="section-header">6. Legalidad</div>
        <?php 
            renderEdit('p6_1', '6.1 ¿Cumple leyes sector petrolero?', ['Si', 'No'], $resp_viejas);
            renderEdit('p6_2', '6.2 ¿Está al corriente en obligaciones fiscales y de IMSS?', ['Si', 'No'], $resp_viejas); 
            renderEdit('p6_3', '6.3 ¿Seguros y permisos vigentes?', ['Si', 'Parcial', 'No'], $resp_viejas);
        ?>

        <div class="section-header">7. Capacidad de Respuesta</div>
        <?php 
            renderEdit('p7_1', '7.1 ¿Cumple tiempos de entrega?', ['Si', 'A veces', 'No'], $resp_viejas);
            renderEdit('p7_2', '7.2 ¿Respuesta ante emergencias?', ['Si', 'Parcial', 'No'], $resp_viejas);
            renderEdit('p7_3', '7.3 ¿Comunicación clara con el cliente?', ['Si', 'No'], $resp_viejas);
        ?>

        <div class="row g-3 mt-5 pb-5 no-print">
            <div class="col-md-6">
                <button type="submit" name="accion" value="guardar" class="btn btn-lg w-100 fw-bold py-3 rounded-pill shadow-sm" style="border: 2px solid var(--edm-blue); color: var(--edm-blue); background: transparent;">
                    <i class="bi bi-floppy-fill me-2"></i> GUARDAR AVANCE (SEGUIR DESPUÉS)
                </button>
            </div>
            <div class="col-md-6">
                <button type="submit" name="accion" value="enviar" onclick="return validarFormularioSGI(event)" class="btn btn-lg w-100 fw-bold py-3 rounded-pill text-white shadow-lg" style="background-color: var(--edm-red); border: none;">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> ENVIAR PARA REVISIÓN FINAL
                </button>
            </div>
            <p class="text-center text-muted small mt-3">Toda la información proporcionada será validada por el personal de Energy Drilling.</p>
        </div>

    </form>
</div>

<script>
    function updateLabel(input) {
        const span = input.closest('.upload-area').querySelector('.small');
        if (input.files.length > 0) {
            span.innerHTML = `<i class="bi bi-file-earmark-plus-fill"></i> Nuevo: ${input.files[0].name}`;
            span.className = "small fw-bold text-primary";
        }
    }

    // Validador integral JS antes de congelar datos
    function validarFormularioSGI(event) {
        if(!document.getElementById('rep_input').value || !document.getElementById('email_input').value || !document.getElementById('ser_input').value){
            alert("Por favor, complete los campos de texto superiores antes de enviar.");
            return false;
        }

        const preguntas = [
            'p1_1','p1_2','p1_3','p1_4','p1_5',
            'p2_1','p2_2','p2_3','p2_4','p2_5',
            'p3_1','p3_2','p3_3','p3_4',
            'p4_1','p4_2','p4_3','p4_4','p4_5',
            'p5_1','p5_2','p5_3','p5_4',
            'p6_1','p6_2','p6_3',
            'p7_1','p7_2','p7_3'
        ];
        
        let vacias = [];
        preguntas.forEach(function(id) {
            const inputs = document.getElementsByName(id);
            let marcada = false;
            for(let i=0; i<inputs.length; i++) {
                if(inputs[i].checked) { marcada = true; break; }
            }
            if(!marcada) {
                let formatoPregunta = id.replace('p', '').replace('_', '.');
                vacias.push(formatoPregunta);
            }
        });

        if (vacias.length > 0) {
            event.preventDefault();
            alert("No se puede enviar el expediente. Faltan responder las siguientes preguntas obligatorias: " + vacias.join(', '));
            return false;
        }

        return confirm('¿Está seguro de enviar el cuestionario para revisión final? Una vez enviado, su expediente quedará congelado en el sistema.');
    }
</script>

</body>
</html>