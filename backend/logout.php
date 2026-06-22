<?php
session_start();
session_unset();
session_destroy();
// Redirigir a la pantalla principal (IP de AWS o index.html)
header("Location: /index.html");
exit();