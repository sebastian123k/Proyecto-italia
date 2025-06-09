<?php
// session-control.php

// 1. Define duración en segundos (ej. 2 horas = 7200s)
$session_timeout = 7200;

// 2. Ajusta parámetros de la cookie antes de session_start()
session_set_cookie_params([
    'lifetime' => $session_timeout,
    'path'     => '/',
    'domain'   => $_SERVER['HTTP_HOST'],
    'secure'   => isset($_SERVER['HTTPS']),  // sólo HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// 3. Caducidad por inactividad
if (
    isset($_SESSION['LAST_ACTIVITY']) &&
    (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)
) {
    // destruir sesión
    session_unset();
    session_destroy();
    // redirigir al login
    header('Location: index.php');
    exit;
}

// 4. Actualizar timestamp de última actividad
$_SESSION['LAST_ACTIVITY'] = time();