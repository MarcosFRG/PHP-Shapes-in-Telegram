# PHP-Shapes-in-Telegram
Este proyecto aún está en desarrollo, pueden haber errores.

**Este proyecto usa [Shapes, Inc. API](https://github.com/shapesinc/shapes-api) para funcionar.**

**Versión de PHP mínima: 7.4+**

**Versión de PHP recomendada: 8.0+**

## Extensiones PHP necesarias:
- cURL
- DOM
- JSON
- OpenSSL
- BCMath

## Para que esto te funcione debes:
1. Entrar a [Shapes, Inc. - Developer](https://shapes.inc/developer) y crear una API Key de tipo **Application** (3ra opción), copiar el **API Key** (el app-id no es necesario).
2. Crear un bot en Telegram (con [**@BotFather**](https://t.me/BotFather)).
3. Configurar los archivos '**config.php**' y '**TuBot/bot.php**'.
4. Subir los archivos a tu servidor HTTP (con PHP y **SSL válido**).
5. Entrar al archivo '**index.php**' en tu navegador (**https://ejemplo.com/index.php**), añadir el token de tu bot Telegram (el que te da [**@BotFather**](https://t.me/BotFather)) y añadir la URL al archivo del bot en tu servidor HTTP (**https://ejemplo.com/TuBot/bot.php**).

## ... ¡y listo! Ya deberías tener tu shape bot funcionando sin problemas.
Entra al archivo '**commands.php**' en tu navegador para añadir/editar/borrar/aplicar los comandos de tus shape bots de Telegram.

## También puedes:
- Renombrar la carpeta '**TuBot/**'.
- Copiar la carpeta '**TuBot/**' con otro nombre para hacer otra shape, sin tener que subir todos los archivos de nuevo a otro servidor.

## Qué NO debes hacer:
- Renombrar el archivo '**bot.php**', ya que '**commands.php**' no funcionaría bien.

### El shape bot recibe:
- [X] Mensajes de texto
- [X] Imágenes
- [ ] Audio/voz
- [ ] Stickers
- [ ] Encuestas

Si tienes preguntas o problemas, puedes unirte a mi grupo de Shapes de **Telegram** ([MFRG_Shapes_chat](https://t.me/MFRG_Shapes)) o [escribirme directamente](https://t.me/MarcosFRGames).
¡Prueba mis shapes [aquí](https://t.me/MFRG_Shapes)!

[Shapes, Inc.](https://shapes.inc)
