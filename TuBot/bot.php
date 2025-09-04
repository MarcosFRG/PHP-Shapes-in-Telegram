<?php
// Tu webhook de Telegram debe dirijirse a este archivo.
$TELEGRAM_TOKEN = '123:BOTLOL';
$SHAPES_API_KEY = 'TU_KEY_DE_SHAPESINC';
$SHAPE_USERNAME = 'tu-shape'; // Ej: lilly-suzuki
$BOT_USERNAME = 'Tu_bot'; // Ej: "Lilly_Suzuki_shape_bot" (SIN EL @)

// Mensajes opcionales de activado/desactivado en grupos.
$ACTIVATE_MSG = "Activad@";
$DEACTIVATE_MSG = "Desactivad@";

// Formato "palabra" => [reacciones...] (opcional)
$Reactions = [
"KK" => ["👀", "🔥", "💩"],
"Good" => ["👍", "💯", "🔥", "🆒"],
"Kionda" => ["👌", "👻", "🗿", "🆒"]
];

// Activar comandos (opcional)
$SHAPE_MODERATION = true;
$SHAPE_COMMAND_DICE = true;
$SHAPE_COMMAND_8BALL = true;

// Información de tus comandos personalizados.
$EXTRA_HELP = "/comando - función.";

require "../bots.php";
?>