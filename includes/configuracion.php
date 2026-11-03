<?php
/**
 * Configuración general del sitio.
 * Los valores de conexión corresponden a una instalación estándar de XAMPP.
 */

declare(strict_types=1);

// Datos de conexión a MySQL.
const BD_SERVIDOR = 'localhost';
const BD_NOMBRE = 'punto_catering';
const BD_USUARIO = 'root';
const BD_CONTRASENA = '';

// Datos generales del sitio.
const NOMBRE_SITIO = 'Punto Catering';

// Estados posibles de un pedido y su etiqueta visible, en orden de avance.
const ESTADOS_PEDIDO = [
    'pendiente' => 'Pendiente',
    'presupuestado' => 'Presupuestado',
    'aprobado' => 'Aprobado',
    'finalizado' => 'Finalizado',
    'rechazado' => 'Rechazado',
    'cancelado' => 'Cancelado',
];

// Ilustraciones disponibles para los menús (archivos en recursos/img/menus).
const ILUSTRACIONES_MENU = [
    'cafe' => 'Café',
    'vianda' => 'Vianda',
    'ensalada' => 'Ensalada',
    'parrilla' => 'Parrilla',
    'copa' => 'Copa',
    'buffet' => 'Buffet',
];

// Se muestran todos los errores en los registros; al visitante nunca se le muestra el detalle técnico.
error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('America/Argentina/Buenos_Aires');
