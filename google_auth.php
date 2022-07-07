<?php
#
#  This is called by the onSignMethod (see LocalSettings.php)
#
require_once 'vendor/autoload.php';

if ( !isset($_POST['credential']) ) {
  exit;
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$token = $_POST['credential'];
#
# Verify the ID token returned by Google
#
$client  = new Google_Client(['client_id' => $_ENV['GOOGLE_LOGIN_APP_ID']]);
$payload = $client->verifyIdToken($token);
$email   = $payload["email"];

if ( !$payload ) {
  header("HTTP/1.1 401 Unauthorized");
  exit;
}

//
// Issue a cookie with the username, time, and a signature (salted hash).
//
// This cookie is then validated in LocalSettings.php and used to sign in the user.
//
$time       = time();
$signature  = sha1($email . $time . $_ENV["AUTH_TOKEN_SALT"]);
$token      = join(":", array($email, $time, $signature));
$token      = base64_encode($token);

$options = array();

if ( $_ENV["ENVIRONMENT"] == "production" ) {
  $options = array(
    'expires'  => time() + 3 * 3600,
    'domain'   => 'wiki.influx.com',
    'secure'   => true,
    'samesite' => 'Strict'
  );
}

setcookie('google_auth_token', $token, $options);

$redirectTo = "/wiki";

if ( isset($_POST["request_path"]) ) {
  $redirectTo = $_POST["request_path"];
}

header("Location: $redirectTo");
