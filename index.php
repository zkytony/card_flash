<?php
ob_start(); // turn on output buffer
extract($_POST);
session_start();
if ($_SESSION['loggedIn']) // if already logged in
{
    header("Location:home.php");
}

require_once "view/login_view.php";
require_once "database.php";

$con=connect();
init_tables($con); // make sure all tables are there

if (isset($_POST['submit']))
{
  // User submitted

  $tablename='users';
  
  $username=mysqli_entities_fix_string($con, $_POST['username']);
  $password=mysqli_entities_fix_string($con, $_POST['password']);

  $restrict_str="WHERE username='$username' AND password='$password'";
  $result=select_from("users", "`userid`, `activate`", $restrict_str, $con);

  $success=false;
  while($rows=mysqli_fetch_assoc($result)) // fetch the row; $row need not to be used
  {
    if ($rows['activate']) 
    {
      $success=true;
    } else {
      $success=false;
    }
    break; // only can be 1 match
  }
  if ($success)
  {
    $_SESSION['loggedIn']=true;
    $_SESSION['username']=$username;
    $_SESSION['password']=$password;
    $_SESSION['userid']=$rows['userid']; // this is necessary
    header("location:home.php");
  } else {
    $_SESSION['loggedIn']=false;
    echo "<h1>Wrong Username/password</h1>";
  }
  ob_end_flush(); //Flush (send) the output buffer and turn off output buffering
}
?>
<!DOCTYPE html>
<html>
  <?php 
  top_html();
  ?>
  <body>
    <?php
    form_login();
    ?>
  </body>
</html>

<?php
// Html for header
function top_html()
{
?>
  <head>
    <title>Flash Cards</title>
    <meta charset='utf-8'>
    <link rel='stylesheet' type='text/css' href='./css/main.css'>
    <link rel="shortcut icon" href="favicon.ico">
  </head>
<?php
}
?>