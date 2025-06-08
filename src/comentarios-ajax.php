<?php
session_start();
require '../php/conection.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$accion = $_POST['accion'] ?? '';
$id = intval($_POST['id'] ?? 0);

if ($accion === 'eliminar') {
    $stmt = $conexion->prepare("DELETE FROM comentarios WHERE id = ? AND idUsuario = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    echo $stmt->affected_rows > 0 ? 'ok' : 'error';
    $stmt->close();
} elseif ($accion === 'editar') {
    $texto = trim($_POST['texto'] ?? '');
    if ($texto === '') {
        echo 'error';
        exit;
    }
    $stmt = $conexion->prepare("UPDATE comentarios SET texto = ? WHERE id = ? AND idUsuario = ?");
    $stmt->bind_param("sii", $texto, $id, $usuario_id);
    $stmt->execute();
    echo $stmt->affected_rows > 0 ? 'ok' : 'error';
    $stmt->close();
}

$conexion->close();
?>
