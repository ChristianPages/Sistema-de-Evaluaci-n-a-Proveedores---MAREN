<?php
/**
 * Procesador: Habilitar/Bloquear Edición de Proveedores
 * Energy Drilling - Sistema de Gestión
 */

session_start();

// 1. SEGURIDAD: Solo el administrador puede ejecutar esta acción
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. CONFIGURACIÓN DE CONEXIÓN
require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();

// 3. CAPTURA DE PARÁMETROS
// 'id' es el ID del usuario (proveedor) y 'estado' es 1 (abrir) o 0 (cerrar)
$id_usuario = isset($_GET['id']) ? intval($_GET['id']) : null;
$estado     = isset($_GET['estado']) ? intval($_GET['estado']) : 0;

if ($id_usuario) {
    // 4. ACTUALIZACIÓN EN LA BASE DE DATOS
    // Cambiamos el switch 'puede_editar' en la tabla usuarios
    $sql = "UPDATE usuarios SET puede_editar = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $estado, $id_usuario);

    if ($stmt->execute()) {
        // Éxito: Regresamos al dashboard con un mensaje de confirmación
        header("Location: dashboard.php?status=permiso_actualizado");
    } else {
        // Error de ejecución
        echo "Error al actualizar el permiso: " . $conn->error;
    }
    $stmt->close();
} else {
    // Si no se recibió un ID válido
    header("Location: dashboard.php?status=error_id");
}

$conn->close();
exit();