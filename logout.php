<?php
require_once "models.php";
require_once "database.php";

session_start();
// set loggedIn to false;
unset($_SESSION['loggedIn']);

$user = $_SESSION['user'];
$con = connect();
$user->logout($con); // log out the user

$_SESSION = array();
session_destroy();
header("location:index.php");
?>
<!DOCTYPE html>
<html>
  <script>window.location = "home.php"</script>
</html>