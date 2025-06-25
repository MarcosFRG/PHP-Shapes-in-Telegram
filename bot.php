<?php
// Tu webhook de Telegram debe dirijirse a este archivo
$TELEGRAM_TOKEN = '123:BOTLOL';
$SHAPES_API_KEY = 'TU_KEY_DE_SHAPESINC';
$SHAPE_USERNAME = 'tu-shape'; // Ej: lilly-suzuki
$SHAPE_NAME = 'Nombre de tu shape';
$BOT_USERNAME = 'Tu_bot'; // Ej: "Lilly_Suzuki_shape_bot" (SIN EL @)

// Lilly responderá siempre cuando exista, mínimo, una de estas palabras (variable opcional)
$Favorite_words = ["Lilly", "Suzuki", "tímida"];

// Formato "palabra" => [reacciones...] (opcional)
$Reactions = [
  "KK" => ["👀", "🔥", "💩"],
  "Good" => ["👍", "💯", "🔥", "🆒"],
  "Kionda" => ["🖐︄1�7", "👻", "🗿", "🆒"]
  ];

$SHAPE_COMMAND_DICE = true;
$SHAPE_COMMAND_8BALL = true;

require "../bots.php";
?>