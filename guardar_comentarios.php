<?php
/**
 * WebApp: Procesar Comentarios del Supervisor - Energy Drilling & Marine
 * Objetivo: Actualizar la columna 'comentarios' en la tabla de evaluaciones.
 */
session_start();

// 1. SEGURIDAD: Validar que el usuario tenga sesión activa y sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. CONEXIÓN A LA BASE DE DATOS
require_once 'config/db.php';

$database = new Database();
$conn = $database->connect();


// 3. PROCESAR INFORMACIÓN DEL FORMULARIO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitizar el ID de la evaluación y el texto para evitar inyecciones SQL
    $id_eval = isset($_POST['eval_id']) ? intval($_POST['eval_id']) : 0;
    $comentarios = isset($_POST['comentarios']) ? $conn->real_escape_string(trim($_POST['comentarios'])) : '';

    if ($id_eval > 0) {
        // Ejecutar la actualización en la columna exacta indicada en tu phpMyAdmin
        $sql = "UPDATE evaluaciones SET comentarios = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $comentarios, $id_eval);
        
        if ($stmt->execute()) {
            // Regresar al expediente con una alerta de éxito
            header("Location: detalle_proveedor.php?id=" . $id_eval . "&msg=comentario_guardado");
            exit();
        } else {
            echo "Error al guardar las observaciones: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "ID de evaluación no válido.";
    }
}

$conn->close();
?>