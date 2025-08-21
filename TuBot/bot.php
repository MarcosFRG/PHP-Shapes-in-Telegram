<?php
// Tu webhook de Telegram debe dirijirse a este archivo.
$TELEGRAM_TOKEN = '123:BOTLOL';
$SHAPES_API_KEY = 'TU_KEY_DE_SHAPESINC';
$SHAPE_USERNAME = 'tu-shape'; // Ej: lilly-suzuki
$SHAPE_NAME = 'Nombre de tu shape';
$BOT_USERNAME = 'Tu_bot'; // Ej: "Lilly_Suzuki_shape_bot" (SIN EL @)

// Las siguientes 4 variables son opcionales.
$START_MSG = "Hola.";
$ERROR_MSG = "? 💀";
$ACTIVATE_MSG = "Activad@";
$DEACTIVATE_MSG = "Desactivad@";

// Tu bot responderá siempre cuando exista, mínimo, una de estas palabras (variable opcional)
$Favorite_words = ["Palabra 1", "Palabra 2", "Palabra 3"];

// Formato "palabra" => [reacciones...] (opcional)
$Reactions = [
"KK" => ["👀", "🔥", "💩"],
"Good" => ["👍", "💯", "🔥", "🆒"],
"Kionda" => ["👌", "👻", "🗿", "🆒"]
];

// Activar comandos (opcional)
$SHAPE_COMMAND_DICE = true;
$SHAPE_COMMAND_8BALL = true;

// Información de tus comandos personalizados.
$EXTRA_HELP = "/comando - función.";

require "../bots.php";
?>