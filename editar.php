<?php
/**
 * WebApp: Edición Completa de Evaluación - Energy Drilling
 * Formato: EDM-FOR-SGI-112
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1); 

$host = "localhost";
$user = "root";
$pass = "";
$db   = "u699112877_edms_app";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Error de conexión"); }

$id_evaluacion = $_GET['id'] ?? null;
if (!$id_evaluacion) { die("ID no válido."); }

// --- OBTENER DATOS ACTUALES PARA USARLOS EN EL POST O EN EL RENDER ---
$res = $conn->query("SELECT * FROM evaluaciones WHERE id = $id_evaluacion");
$data = $res->fetch_assoc();
if (!$data) { die("Evaluación no encontrada."); }
$resp_previas = json_decode($data['respuestas_json'], true) ?? [];

// 1. PROCESAR ACTUALIZACIÓN
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $empresa = $conn->real_escape_string($_POST['empresa']);
    $representante = $conn->real_escape_string($_POST['representante']);
    $email_contacto = $conn->real_escape_string($_POST['email_contacto']);
    $departamento = $conn->real_escape_string($_POST['departamento']);
    $servicio = $conn->real_escape_string($_POST['servicio']);
    $fecha = $_POST['fecha'];

    $respuestas = [];
    $si_count = 0; $total = 0;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'p') === 0) {
            $respuestas[$key] = $value;
            $total++;
            if (in_array($value, ["Si", "Certificado", "Si, Certificado", "Si, documentada", "Si, documentado"])) $si_count++;
        }
    }

    // --- LÓGICA DE CARPETAS PARA EDICIÓN ---
    $slug_edit = preg_replace("/[^A-Za-z0-9]/", "_", trim($empresa));
    $ruta_destino = "uploads/" . $slug_edit . "/";
    if (!is_dir($ruta_destino)) { mkdir($ruta_destino, 0777, true); }

    $preguntas_con_archivo = ['p1_1','p1_2','p1_3','p1_5','p2_1','p2_2','p2_3','p2_4','p2_5','p3_1','p3_2','p3_3','p4_1','p4_3','p5_4','p7_2'];
    foreach ($preguntas_con_archivo as $pid) {
        if (isset($_FILES["file_$pid"]) && $_FILES["file_$pid"]['error'] == 0) {
            $nuevo_nombre = $pid . "_" . time() . ".pdf";
            move_uploaded_file($_FILES["file_$pid"]['tmp_name'], $ruta_destino . $nuevo_nombre);
        }
    }

    $resp_json = json_encode($respuestas, JSON_UNESCAPED_UNICODE);
    $porcentaje = ($total > 0) ? ($si_count / $total) * 100 : 0;
    $nivel = ($porcentaje >= 80) ? "ALTO" : (($porcentaje >= 60) ? "MEDIO" : "BAJO");

    $sql = "UPDATE evaluaciones SET empresa=?, servicio=?, fecha=?, puntaje=?, nivel=?, representante=?, email_contacto=?, departamento=?, respuestas_json=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdsssssi", $empresa, $servicio, $fecha, $porcentaje, $nivel, $representante, $email_contacto, $departamento, $resp_json, $id_evaluacion);
    
    if($stmt->execute()) { 
        header("Location: dashboard.php?msg=ok"); 
        exit(); 
    }
}

// 2. FUNCIÓN RENDERIZADO PARA EDICIÓN (CORREGIDA)
function renderEdit($id, $pregunta, $opciones, $resp_previas, $archivo = null, $empresa = "") { 
    $valor = $resp_previas[$id] ?? '';
    
    // Normalizar slug de empresa para la ruta
    $slug = preg_replace("/[^A-Za-z0-9]/", "_", trim($empresa));
    $ruta_carpeta = "uploads/" . $slug . "/";
    
    // Buscar archivos que empiecen con el ID de la pregunta (p1_1_, p1_2_, etc)
    $evidencias = glob($ruta_carpeta . $id . "_*.pdf");
?>
    <div class="question-card shadow-sm p-3 mb-3 bg-white rounded border-start border-4 border-warning">
        <p class="fw-bold mb-2 small text-dark"><?php echo $pregunta; ?></p>
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($opciones as $opt): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="<?php echo $id; ?>" value="<?php echo $opt; ?>" <?php echo ($valor == $opt) ? 'checked' : ''; ?> required>
                    <label class="form-check-label small"><?php echo $opt; ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($archivo): ?>
            <div class="mt-3 p-2 rounded-2 border" style="background: #fdfdfd; font-size: 0.85rem;">
                <label class="fw-bold"><i class="bi bi-paperclip"></i> Evidencia Sugerida: (<?php echo $archivo; ?>):</label>
                
                <?php if (!empty($evidencias)): ?>
                    <div class="mb-2 mt-1">
                        <a href="<?php echo $evidencias[0]; ?>" target="_blank" class="btn btn-sm btn-outline-success py-0">
                            <i class="bi bi-file-earmark-check"></i> Ver archivo actual
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-muted small my-1">Sin archivo cargado.</div>
                <?php endif; ?>
                
                <input type="file" name="file_<?php echo $id; ?>" class="form-control form-control-sm" accept=".pdf">
                <small class="text-muted">Suba un archivo nuevo solo si desea reemplazar el anterior.</small>
            </div>
        <?php endif; ?>
    </div>
<?php } ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Evaluación | Energy Drilling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .main-container { max-width: 850px; margin: 30px auto; padding: 0 15px; }
        .section-banner { background: #333645; color: white; padding: 10px 15px; border-radius: 6px; margin: 25px 0 10px; font-weight: bold; font-size: 0.8rem; text-transform: uppercase; }
        .logo-img { max-height: 60px; }
    </style>
</head>
<body>

<div class="container main-container">
    <div class="card p-4 shadow-sm mb-4 border-0 text-center">
        <div class="d-flex justify-content-between align-items-center mb-3">
             <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Panel</a>
             <img src="Logo EDM.png" class="logo-img">
             <div style="width: 80px;"></div>
        </div>
        <h4 class="fw-bold">EDICIÓN DE EVALUACIÓN</h4>
        <p class="text-muted small">ID Registro: #<?php echo $id_evaluacion; ?> | Empresa: <?php echo $data['empresa']; ?></p>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="card p-4 mb-4 border-0 shadow-sm rounded-3">
            <h6 class="fw-bold text-primary mb-3 text-uppercase">Datos Generales</h6>
            <div class="row g-3">
                <div class="col-12"><label class="small fw-bold">Empresa:</label><input type="text" name="empresa" class="form-control" value="<?php echo htmlspecialchars($data['empresa']); ?>" required></div>
                <div class="col-md-6"><label class="small fw-bold">Representante:</label><input type="text" name="representante" class="form-control" value="<?php echo htmlspecialchars($data['representante']); ?>" required></div>
                <div class="col-md-6"><label class="small fw-bold">Email:</label><input type="email" name="email_contacto" class="form-control" value="<?php echo htmlspecialchars($data['email_contacto']); ?>" required></div>
                <div class="col-md-8"><label class="small fw-bold">Servicio:</label><input type="text" name="servicio" class="form-control" value="<?php echo htmlspecialchars($data['servicio']); ?>" required></div>
                <div class="col-md-4"><label class="small fw-bold">Fecha:</label><input type="date" name="fecha" class="form-control" value="<?php echo $data['fecha']; ?>" required></div>
                <input type="hidden" name="departamento" value="<?php echo $data['departamento']; ?>">
            </div>
        </div>

        <div class="section-banner">1. Seguridad y Salud</div>
        <?php 
        renderEdit('p1_1', '1.1 ¿Sistema de gestión de seguridad?', ['Si, Certificado', 'Implementando', 'No'], $resp_previas, 'la política', $data['empresa']);
        renderEdit('p1_2', '1.2 ¿Identificación de peligros?', ['Si', 'Parcial', 'No'], $resp_previas, 'Matriz IPER', $data['empresa']);
        renderEdit('p1_3', '1.3 ¿Capacitación regular?', ['Si', 'Ocasional', 'No'], $resp_previas, 'constancias', $data['empresa']);
        renderEdit('p1_4', '1.4 ¿Registra incidentes?', ['Si', 'No'], $resp_previas);
        renderEdit('p1_5', '1.5 ¿Exige el uso de EPP?', ['Si', 'Parcial', 'No'], $resp_previas, 'vales EPP', $data['empresa']);
        ?>

        <div class="section-banner">2. Medio Ambiente</div>
        <?php 
        renderEdit('p2_1', '2.1 ¿Política ambiental?', ['Si', 'En desarrollo', 'No'], $resp_previas, 'política', $data['empresa']);
        renderEdit('p2_2', '2.2 ¿Controla impactos?', ['Si', 'Parcial', 'No'], $resp_previas, 'Matriz Aspectos', $data['empresa']);
        renderEdit('p2_3', '2.3 ¿Gestiona residuos?', ['Si', 'Parcial', 'No'], $resp_previas, 'procedimiento', $data['empresa']);
        renderEdit('p2_4', '2.4 ¿Cumple normatividad?', ['Si', 'No'], $resp_previas, 'evidencia', $data['empresa']);
        renderEdit('p2_5', '2.5 ¿Uso eficiente de recursos?', ['Si', 'Parcial', 'No'], $resp_previas, 'evidencia', $data['empresa']);
        ?>

        <button type="submit" class="btn btn-warning btn-lg w-100 shadow mt-4 mb-5 fw-bold py-3 rounded-pill">
            ACTUALIZAR REGISTRO Y ARCHIVOS
        </button>
    </form>
</div>

</body>
</html>