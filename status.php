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

echo "\n";

$meminfo = file_get_contents("/proc/meminfo");

if ( $meminfo ) {
  $lines = explode("\n", $meminfo);
  echo join("\n", array_slice($lines, 0, 3));
}

echo "\n";
