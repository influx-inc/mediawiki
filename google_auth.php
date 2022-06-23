<?php
#
#  This is called by the onSignMethod (see LocalSettings.php)
#
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

#
# Verify the ID token returned by Google
#
$client  = new Google_Client(['client_id' => $_ENV['GOOGLE_LOGIN_APP_ID']]);
$payload = $client->verifyIdToken($_POST['token']);
$email   = $payload['email'];

//
// Issue a cookie with the username, time, and a signature (salted hash).
//
// This cookie is then validated in LocalSettings used to sign in the user.
//
$time       = time();
$signature  = sha1($email . $time . $_ENV["AUTH_TOKEN_SALT"]);
$token      = join(":", array($email, $time, $signature));
$expiry     = 3 * 3600;

if ( $payload )
{
  setcookie('google_auth_token', $token, time() + $expiry, "/");
}
else
{
  header("HTTP/1.1 401 Unauthorized");
  exit;
}
