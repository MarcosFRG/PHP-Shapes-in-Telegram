# PHP-Shapes-in-Telegram
Este proyecto aún está en desarrollo, pueden haber errores.

**Este proyecto usa [Shapes, Inc. API](https://github.com/shapesinc/shapes-api) para funcionar.**

**Versiones de PHP:**
- 7.4 (mínima)
- 8.3 (recomendada, más rápida)

## Extensiones PHP necesarias:
- cURL
- DOM
- JSON
- OpenSSL
- BCMath
- PDFParser (viene incluído)
- OpenSSL

##### (Hostings como x10Hosting ya tienen todas instaladas)

## Para que esto te funcione debes:
1. Entrar a [Shapes, Inc. - Developer](https://shapes.inc/developer) y crear una API Key de tipo **Application** (3ra opción), copiar el **API Key** (el app-id no es necesario).
3. Crear un bot en Telegram (con [**@BotFather**](https://t.me/BotFather)).
4. Configurar los archivos '**config.php**' y '**TuBot/bot.php**'.
5. Subir los archivos a tu servidor HTTP (con PHP y **SSL válido**).
6. Entrar al archivo '**index.php**' en tu navegador (**https://ejemplo.com/index.php**), añadir el token de tu bot de Telegram (el que te da [**@BotFather**](https://t.me/BotFather)) y añadir la URL al archivo del bot en tu servidor HTTP (**https://ejemplo.com/TuBot/bot.php**).

## ... ¡y listo! Ya deberías tener tu shape bot funcionando sin problemas.
Entra al archivo '**commands.php**' en tu navegador para añadir/editar/borrar/aplicar los comandos de tus shape bots de Telegram.

## También puedes:
- Renombrar la carpeta '**TuBot/**'.
- Copiar la carpeta '**TuBot/**' con otro nombre para hacer otra shape, sin tener que subir todos los archivos de nuevo a otro servidor.
- Cambiar los mensajes de inicio/error/activado/desactivado de la shape (puedes usar las etiquetas "**{shape}**" y "**{user}**").

## Qué NO debes hacer:
- Renombrar el archivo '**bot.php**', ya que '**commands.php**' no funcionaría bien.

### El shape bot recibe (por ahora):
- [X] Comandos
- [X] Mensajes de texto
- [X] Imágenes
- [X] Audio/voz
- [X] Documentos pequeños
- [X] Procesar PDF (solo texto)
- [ ] Stickers
- [ ] Encuestas

### El shape bot puede/tiene:
- [X] Responder a preguntas de usuarios.
- [X] Responder a menciones (solo si el bot es administrador, aunque no tenga permisos).
- [X] Comandos de juegos.
- [ ] Comandos de moderación.
- [X] Free-will (no terminado).

### También puedes (por ahora):
- [X] Registrar tus propias Shape Keys.
- [ ] Registrar tu cuenta de Shapes.

## Solución de problemas
- **Error 500**: Verifica que las extensiones PHP estén activadas.
- **Webhook no funciona**: Asegúrate de que tu servidor tenga SSL válido.

Si tienes preguntas o problemas, puedes unirte a mi grupo de Shapes de **Telegram** ([MFRG_Shapes_chat](https://t.me/MFRG_Shapes)) o [escribirme directamente](https://t.me/MarcosFRGames).
¡Prueba mis shapes [aquí](https://t.me/MFRG_Shapes)!

[Shapes, Inc.](https://shapes.inc)
