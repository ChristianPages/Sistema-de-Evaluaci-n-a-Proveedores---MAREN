<?php
/**
 * Reporte Oficial de Evaluación de Proveedores - Energy Drilling
 * Formato: EDM-FOR-SGI-112
 */
require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM evaluaciones WHERE id = $id";
$result = $conn->query($sql);
$data = $result->fetch_assoc();

if (!$data) { die("Error: Evaluación no encontrada."); }

$respuestas = json_decode($data['respuestas_json'], true);

// Diccionario Completo de las 29 Preguntas
$preguntas_full = [
    "1. SEGURIDAD Y SALUD EN EL TRABAJO" => [
        'p1_1' => '1.1 ¿Cuenta con un sistema de gestión de seguridad y salud en el trabajo?',
        'p1_2' => '1.2 ¿Tiene identificación de peligros y evaluación de riesgos?',
        'p1_3' => '1.3 ¿Capacita regularmente a su personal en seguridad industrial?',
        'p1_4' => '1.4 ¿Registra e investiga incidentes y accidentes laborales?',
        'p1_5' => '1.5 ¿Exige el uso de EPP y supervisa su cumplimiento?'
    ],
    "2. MEDIO AMBIENTE Y SOSTENIBILIDAD" => [
        'p2_1' => '2.1 ¿Cuenta con una política ambiental o de sostenibilidad?',
        'p2_2' => '2.2 ¿Identifica y controla los impactos ambientales?',
        'p2_3' => '2.3 ¿Gestiona adecuadamente residuos peligrosos y no peligrosos?',
        'p2_4' => '2.4 ¿Cumple con la normatividad ambiental aplicable?',
        'p2_5' => '2.5 ¿Promueve prácticas de uso eficiente de recursos?'
    ],
    "3. CALIDAD DEL SERVICIO" => [
        'p3_1' => '3.1 ¿Cuenta con un sistema de gestión de calidad formal?',
        'p3_2' => '3.2 ¿Define estándares de calidad para los servicios?',
        'p3_3' => '3.3 ¿Realiza controles de calidad y seguimiento?',
        'p3_4' => '3.4 ¿Atiende y gestiona quejas o no conformidades?'
    ],
    "4. DERECHOS HUMANOS Y ESTÁNDARES LABORALES" => [
        'p4_1' => '4.1 ¿Cuenta con política de respeto a derechos humanos?',
        'p4_2' => '4.2 ¿Cumple con la legislación laboral vigente?',
        'p4_3' => '4.3 ¿Prohíbe el trabajo infantil o forzado?',
        'p4_4' => '4.4 ¿Garantiza igualdad y no discriminación?',
        'p4_5' => '4.5 ¿Respeta la libertad de asociación?'
    ],
    "5. RESPONSABILIDAD SOCIAL" => [
        'p5_1' => '5.1 ¿Tiene criterios de RS para seleccionar proveedores?',
        'p5_2' => '5.2 ¿Incorpora criterios ASG en sus compras?',
        'p5_3' => '5.3 ¿Promueve conductas éticas y combate corrupción?',
        'p5_4' => '5.4 ¿Cuenta con un código de ética o conducta?'
    ],
    "6. LEGALIDAD" => [
        'p6_1' => '6.1 ¿Cumple con regulaciones del sector petrolero?',
        'p6_2' => '6.2 ¿Está al corriente en obligaciones fiscales y SS?',
        'p6_3' => '6.3 ¿Cuenta con permisos y seguros requeridos?'
    ],
    "7. CAPACIDAD DE RESPUESTA" => [
        'p7_1' => '7.1 ¿Cumple con tiempos de respuesta y entrega?',
        'p7_2' => '7.2 ¿Responde ante emergencias o cambios operativos?',
        'p7_3' => '7.3 ¿Mantiene comunicación efectiva con el cliente?'
    ]
];

$puntaje = round($data['puntaje'], 1);
$color = ($data['nivel'] == 'ALTO') ? '#198754' : (($data['nivel'] == 'MEDIO') ? '#ffc107' : '#dc3545');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte_EDM_<?php echo htmlspecialchars($data['empresa']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.3; }
        .header-table { width: 100%; border-bottom: 3px solid #004a99; margin-bottom: 15px; padding-bottom: 5px; }
        .title { color: #004a99; font-size: 16px; font-weight: bold; text-transform: uppercase; }
        
        .section-title { background: #002d5c; color: white; padding: 4px 10px; font-weight: bold; margin-top: 10px; font-size: 11px; }
        
        /* Datos Generales */
        .datos-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .datos-table td { border: 1px solid #ddd; padding: 5px; }
        .label { background: #f4f4f4; font-weight: bold; width: 20%; }

        /* Gráfica */
        .chart-container { text-align: center; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .bar-bg { background: #eee; border-radius: 10px; width: 80%; height: 12px; margin: 5px auto; overflow: hidden; border: 1px solid #ccc; }
        .bar-fill { background: <?php echo $color; ?>; width: <?php echo $puntaje; ?>%; height: 100%; }
        .score-text { font-size: 20px; font-weight: bold; color: <?php echo $color; ?>; margin-bottom: 0; }

        /* Preguntas */
        .table-res { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table-res td { border-bottom: 1px solid #eee; padding: 3px 8px; }
        .ans-text { font-weight: bold; color: #004a99; width: 120px; text-align: right; }

        /* Firmas */
        .signature-table { width: 100%; margin-top: 40px; text-align: center; }
        .sig-line { border-top: 1px solid #000; width: 80%; margin: 40px auto 5px; }

        .btn-print { background: #004a99; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; float: right; }
        @media print { .btn-print { display: none; } }
		
		.bar-bg { 
			background: #eee !important; /* Añadimos !important */
			border-radius: 10px; 
			width: 80%; 
			height: 12px; 
			margin: 5px auto; 
			overflow: hidden; 
			border: 1px solid #ccc;
			/* ESTA ES LA LÍNEA CLAVE PARA QUE SE IMPRIMA EL COLOR */
			-webkit-print-color-adjust: exact; 
			print-color-adjust: exact;
		}

		.bar-fill { 
			background: <?php echo $color; ?> !important; 
			width: <?php echo $puntaje; ?>%; 
			height: 100%;
			/* TAMBIÉN AQUÍ */
			-webkit-print-color-adjust: exact; 
			print-color-adjust: exact;
		}
    </style>
</head>
<body>

<button class="btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>

<table class="header-table">
    <tr>
        <td class="title">Energy Drilling Marine Services</td>
        <td style="text-align: right;">
            <strong>EDM-FOR-SGI-112</strong><br>
            Evaluación de Proveedores | SSPA & Calidad
        </td>
    </tr>
</table>

<div class="section-title">DATOS GENERALES DEL PROVEEDOR</div>
<table class="datos-table">
    <tr>
        <td class="label">Empresa:</td><td><?php echo htmlspecialchars($data['empresa']); ?></td>
        <td class="label">Fecha:</td><td><?php echo date("d/m/Y", strtotime($data['fecha'])); ?></td>
    </tr>
    <tr>
        <td class="label">Representante:</td><td><?php echo htmlspecialchars($data['representante']); ?></td>
        <td class="label">Servicio:</td><td><?php echo htmlspecialchars($data['servicio']); ?></td>
    </tr>
    <tr>
        <td class="label">Contacto:</td><td><?php echo htmlspecialchars($data['email_contacto']); ?></td>
        <td class="label">Evaluador:</td><td><?php echo htmlspecialchars($data['departamento']); ?></td>
    </tr>
</table>

<div class="chart-container">
    <p class="score-text"><?php echo $puntaje; ?>%</p>
    <div style="font-weight: bold;">NIVEL: <?php echo $data['nivel']; ?></div>
    <div class="bar-bg"><div class="bar-fill"></div></div>
</div>

<div class="section-title">RESULTADOS DEL CUESTIONARIO</div>
<?php foreach ($preguntas_full as $seccion => $preguntas): ?>
    <div style="background: #f9f9f9; padding: 3px 8px; font-weight: bold; border-left: 3px solid #004a99; margin-top: 5px;">
        <?php echo $seccion; ?>
    </div>
    <table class="table-res">
        <?php foreach ($preguntas as $key => $texto): 
            $valor = isset($respuestas[$key]) ? $respuestas[$key] : 'No respondido';
        ?>
            <tr>
                <td><?php echo $texto; ?></td>
                <td class="ans-text"><?php echo $valor; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endforeach; ?>

<div class="section-title">EVIDENCIAS Y OBSERVACIONES</div>
<div style="border: 1px solid #ddd; padding: 10px; margin-top: 5px;">
    <strong>Notas del Evaluador:</strong><br>
    <?php echo !empty($data['comentarios']) ? nl2br(htmlspecialchars($data['comentarios'])) : "Sin observaciones."; ?>
    
    <div style="margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 10px;">
        <strong>Documentos Adjuntos:</strong><br>
        <?php 
        $slug = preg_replace("/[^A-Za-z0-9]/", "_", $data['empresa']);
        $files = glob("uploads/" . $slug . "_*.pdf");
        if (empty($files)) echo "No se adjuntaron evidencias.";
        else {
            foreach ($files as $f) echo "<div style='font-size: 9px;'><i class='bi bi-file-pdf'></i> " . basename($f) . "</div>";
        }
        ?>
    </div>
</div>

<table class="signature-table">
    <tr>
        <td style="width: 45%;">
            <div class="sig-line"></div>
            <strong>Firma de Quien Revisa</strong><br>
            Responsable SGI / Compras
        </td>
        <td style="width: 10%;"></td>
        <td style="width: 45%;">
            <div class="sig-line"></div>
            <strong>Representante del Proveedor</strong><br>
            Aceptación de Resultados
        </td>
    </tr>
</table>

</body>
</html>