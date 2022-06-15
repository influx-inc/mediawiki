<?php

if ( @$_SERVER['HTTP_X_AUTHORIZATION'] != "blarg" ) {
  header('HTTP/1.0 403 Forbidden');
  return;
}

header('Content-type: text/plain');

$memcached = new Memcached();
$memcached->addServer("localhost", 11211);
if ( $memcached->getStats() ) {
  echo "OK Memcached \n";
}

if ( file_get_contents("http://localhost:9200/_cluster/health") ) {
  echo "OK Elasticsearch\n";
}

echo "\n";
echo file_get_contents("/proc/meminfo");
