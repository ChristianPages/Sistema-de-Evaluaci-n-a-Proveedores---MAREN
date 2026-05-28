<?php
/**
 * Lógica de Eliminación Segura - Energy Drilling
 */
session_start();

// SEGURIDAD: Solo el admin puede borrar
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado.");
}

require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

// Capturamos el ID del proveedor a eliminar
$id_eliminar = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_eliminar > 0) {
    
    // 1. ELIMINAR ARCHIVOS FÍSICOS (Evidencia PDF)
    $ruta_carpeta = "uploads/proveedor_" . $id_eliminar;

    //Verificación:
    if (is_dir($ruta_carpeta)) {

        // Obtener todos los archivos
        $files = glob($ruta_carpeta . '/*'); 

        // eliminar los archivos
        foreach($files as $file){
            if(is_file($file)) unlink($file); // Borrar cada PDF
        }
        rmdir($ruta_carpeta); // Borrar la carpeta vacía
    }

    // 2. ELIMINAR REGISTROS EN BASE DE DATOS
    // Borramos primero las evaluaciones (por la llave foránea)
    $conn->query("DELETE FROM evaluaciones WHERE usuario_id = $id_eliminar");
    
    // Borramos al usuario
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_eliminar);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=eliminado_ok");
    } else {
        echo "Error al eliminar: " . $conn->error;
    }
} else {
    header("Location: dashboard.php?msg=error_id");
}
exit();