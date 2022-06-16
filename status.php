<?php
require_once('vendor/autoload.php');

ini_set('html_errors', false);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$password = $_SERVER['PHP_AUTH_PW'];

if ( $password == null ) {
  header("WWW-Authenticate: Basic");
  http_response_code(401);
  exit;
}
if ( $password != $_ENV['STATUS_PWD'] ) {
  http_response_code(401);
  exit;
}

header('Content-type: text/plain');

#
# Check database
#
$db = parse_url($_ENV["DATABASE_URL"]);
$conn = new mysqli($db['host'], $db['user'], $db['pass']);

if ($conn->connect_error) {
  echo "MySQL connect failed\n";
  http_response_code(503);
} else {
  echo "MySQL OK\n";
}

#
# Check Memcached
#
$memcached = new Memcached();
$memcached->addServer("localhost", 11211);
if ( $memcached->getStats() ) {
  echo "Memcached OK\n";
} else {
  echo "Memcached connect failed\n";
  http_response_code(503);
}

#
# Check Elasticsearch
#
if ( file_get_contents("http://localhost:9200/_cluster/health") ) {
  echo "Elasticsearch OK\n";
} else {
  echo "Elasticsearch connect failed\n";
  http_response_code(503);
}
