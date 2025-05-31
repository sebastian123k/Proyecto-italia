<?php
// getDifuntos.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require 'conection.php';

$manzana = $_GET['manzana'] ?? '';
$cuadro  = $_GET['cuadro']  ?? '';
$fila    = $_GET['fila']    ?? '';
$numero  = $_GET['numero']  ?? '';

$sql = "
    SELECT 
        d.id               AS id,
        t.id               AS idTumba,
        t.manzana          AS manzana,
        t.cuadro           AS cuadro,
        t.fila             AS fila,
        t.numero           AS numero,
        t.cordenadas       AS cordenadas,
        d.nombre           AS nombre,
        d.apellidoPaterno  AS apellidoPaterno,
        d.apellidoMaterno  AS apellidoMaterno
    FROM tumbas t
    JOIN difuntos d ON d.idTumba = t.id
    WHERE 1=1
";
$params = [];
$types  = "";

if ($manzana !== "") {
    $sql       .= " AND t.manzana LIKE ?";
    $params[]   = "%{$manzana}%";
    $types     .= "s";
}
if ($cuadro !== "") {
    $sql       .= " AND t.cuadro LIKE ?";
    $params[]   = "%{$cuadro}%";
    $types     .= "s";
}
if ($fila !== "") {
    $sql       .= " AND t.fila LIKE ?";
    $params[]   = "%{$fila}%";
    $types     .= "s";
}
if ($numero !== "") {
    $sql       .= " AND t.numero LIKE ?";
    $params[]   = "%{$numero}%";
    $types     .= "s";
}

$stmt = $conexion->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$marcadores = [];
while ($row = $result->fetch_assoc()) {
    $marcadores[] = $row;
}

echo json_encode($marcadores, JSON_UNESCAPED_UNICODE);
