<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { exit("No autorizado"); }

$id_proveedor = isset($_GET['id']) ? intval($_GET['id']) : 0;
$folder = "uploads/proveedor_" . $id_proveedor . "/";

if (!is_dir($folder)) {
    die("<script>alert('El proveedor aún no ha cargado archivos.'); window.history.back();</script>");
}

// 1. Configurar el nombre del archivo ZIP
$zipName = "Expediente_Proveedor_" . $id_proveedor . "_" . date('Ymd') . ".zip";
$zip = new ZipArchive();

if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    // 2. Buscar todos los archivos PDF en la carpeta
    $files = glob($folder . "*.pdf");
    
    if (empty($files)) {
        $zip->close();
        @unlink($zipName);
        die("<script>alert('No se encontraron archivos PDF.'); window.history.back();</script>");
    }

    foreach ($files as $file) {
        // Añadir archivo al ZIP con su nombre original
        $zip->addFile($file, basename($file));
    }
    
    $zip->close();

    // 3. Forzar la descarga del archivo ZIP
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zipName);
    header('Content-Length: ' . filesize($zipName));
    readfile($zipName);

    // 4. Limpiar: Borrar el archivo temporal del servidor después de descargar
    unlink($zipName);
    exit;
} else {
    die("Error al crear el archivo comprimido.");
}