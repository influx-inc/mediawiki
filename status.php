<?php
require_once('vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

if ( @$_SERVER['HTTP_X_AUTHORIZATION'] != $_ENV["STATUS_TOKEN"] ) {
  header('HTTP/1.0 403 Forbidden');
  return;
}

header('Content-type: text/plain');

$memcached = new Memcached();
$memcached->addServer("localhost", 11211);
if ( $memcached->getStats() ) {
  echo "Memcached OK\n";
}

if ( file_get_contents("http://localhost:9200/_cluster/health") ) {
  echo "Elasticsearch OK\n";
}


function getSystemMemInfo()
{
    $data = explode("\n", file_get_contents("/proc/meminfo"));
    $meminfo = array();
    foreach ($data as $line) {
        @list($key, $val) = explode(":", $line);
        $meminfo[$key] = trim($val);
    }
    return $meminfo;
}

$info = getSystemMemInfo();

$available = intval(intval($info['MemAvailable']) / 1024);

$status = ( $available > 200 ) ? "OK" : "warning";

echo "System mem $status ($available MB available)";
echo "\n";
