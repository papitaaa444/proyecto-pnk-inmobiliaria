<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Denegado - PNK Inmobiliaria</title>
    <style>
        body { background: #F4F4F9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: sans-serif; text-align: center; }
        .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-top: 5px solid #FF0066; max-width: 550px; }
        h1 { color: #FF0066; font-size: 60px; margin: 0; }
        h2 { color: #000; font-weight: 900; margin: 20px 0 10px; letter-spacing: -1px; }
        p { color: #666; font-weight: 600; line-height: 1.6; margin-bottom: 25px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚫</h1>
        <h2>ACCESO DENEGADO</h2>
        <p>PERMISOS INSUFICIENTES PARA INGRESAR A PNK INMOBILIARIA.<br>COMUNÍCATE CON UN ADMINISTRADOR.</p>
        <p style="font-size: 13px; color: #999; text-transform: none;">Redirigiendo a la página principal...</p>
    </div>
    <script>
        // Redirección forzada a la raíz del proyecto
        setTimeout(() => {
            window.location.href = "index.html";
        }, 4000);
    </script>
</body>
</html>