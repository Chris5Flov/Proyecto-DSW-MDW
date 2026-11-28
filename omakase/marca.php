<?php
// Uso (desde la carpeta omakase):  php marca.php   — luego se puede borrar.
// Aplica la marca OMAKASE (favicon, título, encabezado y pie) a todos los .html de public/.
$cambios = [
    '<link rel="icon" href="data:,">' => '<link rel="icon" type="image/png" href="img/logo.png">',
    '— Sistema de Pedidos</title>' => '— OMAKASE</title>',
    '<title>Sistema de Pedidos — Inicio</title>' => '<title>OMAKASE — Inicio</title>',
    '<h1 class="encabezado__titulo">Sistema de Pedidos</h1>' => '<h1 class="encabezado__titulo">Restaurante OMAKASE</h1>',
    'Sistema de Pedidos de Restaurante · ' => 'OMAKASE · ',
];

// En Windows, glob() trata la barra invertida como escape: se normaliza la ruta a barras normales.
$archivos = glob(str_replace('\\', '/', __DIR__) . '/public/*.html');
echo count($archivos) . " archivos encontrados\n";

foreach ($archivos as $ruta) {
    $texto = file_get_contents($ruta);
    $total = 0;
    foreach ($cambios as $buscar => $poner) {
        $texto = str_replace($buscar, $poner, $texto, $n);
        $total += $n;
    }
    file_put_contents($ruta, $texto);
    echo basename($ruta) . ": $total cambios\n";
}