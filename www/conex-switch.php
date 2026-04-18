<?php
ini_set('display_errors', 0);
error_reporting(0);

$appEnv = strtolower((string) getenv('APP_ENV'));
$localEnvs = array('local', 'xampp', 'dev');

$localFile = __DIR__ . '/conex.local.php';
$prodFile  = __DIR__ . '/conex.php';

// Cargar conex.local.php solo si: APP_ENV es local Y el archivo existe físicamente
if (in_array($appEnv, $localEnvs, true) && file_exists($localFile)) {
    require_once $localFile;
} else {
    require_once $prodFile;
}
