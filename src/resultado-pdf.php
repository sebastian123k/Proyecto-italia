<?php
// src/resultado-pdf.php

require '../php/conection.php';
require '../vendor/autoload.php'; // Ajusta si no usas Composer

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID no proporcionado.";
    exit;
}

$sql = "SELECT 
          t.manzana, 
          t.cuadro, 
          t.fila, 
          t.numero, 
          t.cordenadas, 
          d.apellidoPaterno, 
          d.apellidoMaterno, 
          d.nombre, 
          d.fechaNacimiento, 
          d.fechaDefuncion, 
          d.Restos,
          d.imagen
        FROM difuntos d
        JOIN tumbas t ON d.idTumba = t.id
        WHERE d.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows === 0) {
    echo "No se encontró el registro.";
    exit;
}
$fila = $resultado->fetch_assoc();
$stmt->close();
$conexion->close();

$host   = $_SERVER['HTTP_HOST'];
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $host . '/italia'; // Cambia si la carpeta es diferente

$logoUrl   = $baseUrl . '/img/logo.png';
$avatarUrl = $baseUrl . '/img/avatar.png';

$photoPath = '';
if (!empty($fila['imagen'])) {
    $candidate = __DIR__ . '/../photos/' . $fila['imagen'];
    if (file_exists($candidate)) {
        $photoPath = $baseUrl . '/photos/' . rawurlencode($fila['imagen']);
    }
}

// 4) Capturar HTML con buffer
ob_start();
include 'plantilla_pdf.php'; // Asegúrate que el nombre del archivo del código 3 es este
$html = ob_get_clean();

// 5) Crear PDF con Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true); // Habilitar imágenes externas
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Descargar el archivo
$dompdf->stream('ficha-difunto.pdf', ['Attachment' => true]);
