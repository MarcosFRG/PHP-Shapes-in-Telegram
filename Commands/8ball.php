<?php
// Frase aleatoria de respuesta
$barr = ["Sí", "No", "Tal vez", "Nope", "Ni lo sueñes", "Probablemente", "Probablemente no", "Nel"];

$pregunta = str_replace("/8ball$bot_mention", "", $command_response);
$command_response = "";

if (empty(trim($pregunta)) || !str_contains($pregunta, "?")) {
    $command_response = "Uso: /8ball".str_replace("_", "\_", $bot_mention)." \[pregunta\]";
} else {
    $respuesta = $barr[array_rand($barr)];
    $command_response = "🔮 *Pregunta:*$pregunta
🎱 *Respuesta:* $respuesta";
}
?>
