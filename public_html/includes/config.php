<?php
// --- Conexión local ---
/*
$host = "localhost";
$user = "root";
$pass = "Viva-mexico1";
$db   = "patch_bolsatrabajo";
*/

// --- Conexión productiva ---
// (mantén comentado hasta subir al servidor)

$host = "localhost";              // o el host que te muestre cPanel
$user = "root";    // el que te cree tu jefe
$pass = 'cb_36dW.a8O15hCx$b';           // la contraseña que te dé
$db   = "patch_bolsatrabajo";      // nombre de la base en phpMyAdmin

// Crear conexión
$conn = new mysqli($host, $user, $pass, $db);


// Verificar conexión
if ($conn->connect_error) {
    // En lugar de imprimir texto, lanzamos excepción
    throw new Exception("Error en la conexión: " . $conn->connect_error);
}

// Opcional: configurar charset
$conn->set_charset("utf8mb4");
?>
