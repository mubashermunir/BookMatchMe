<?php
/**
 * @author Sachin Khosla - @realin
 * Desc - Goodreads redirects to this file after successful authentication.
 *
 * modified by @YourNextRead to integrate with goodreads
 */
 
define('ROOT_PATH', $_SERVER["DOCUMENT_ROOT"]);

//require_once('GoodreadsAPI.php');
require_once(ROOT_PATH.'BookMatchMe/application/libraries/Goodreads/GoodreadsAPI.php');

session_start();

if($_SESSION['oauth_token'] !== $_REQUEST['oauth_token'])
{
  //token expired get a new one. You can clear session over here and redirect user to the login link
  die('token expired get a new one');
}


$obj = new GoodreadsApi(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

$access_token = $obj->getAccessToken($_REQUEST['oauth_verifier']);
$_SESSION['access_token'] = $access_token;

//print_r($access_token);

unset ($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] ,$obj);

$obj = new GoodreadsApi(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

$content = $obj->doGet('http://www.goodreads.com/api/auth_user');

$xml=simplexml_load_string($content) or die("Error: Cannot create object");

$userId = (string) $xml-> user['id'] ;

$_SESSION['goodreads_userId'] = $userId;

unset ($obj);

?>