<?php
ob_start(); // turn on output buffer
extract($_POST);
session_start();
if ($_SESSION['loggedIn']) // if already logged in
{
    header("Location:home.php");
}

require_once "database.php";

$con=connect();
init_tables($con); // make sure all tables are there

if (isset($_POST['submit']))
{
  // User submitted

  $tablename='users';
  
  $username=$_POST['username'];
  $password=$_POST['password'];

  $restrict_str="WHERE username='$username' AND password='$password'";
  $result=select_from("users", "`userid`", $restrict_str, $con);

  $success=false;
  while($rows=mysqli_fetch_assoc($result)) // fetch the row; $row need not to be used
  {
    $success=true;
    break;
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

// block of HTML for form
function form_login()
{
?>
  <div class="form-login">
    <h3>Login</h3>
    <form name="login " action="<?php echo $_SERVER['PHP_SELF'];?>"  method="post">
      <p><label for="username">Username: </label><input class='input-field' name="username" id="username" title="Username" type="text" maxLength="10"></p>
      <p><label for="password">Password: </label><input class="input-field"name="password" id="password" title="Password" type="password" maxLength="15"></p>
      <p><input name="submit" id="submit" type="submit" value="Log in"/>
        <a href="sign_up.php">Sign up</a></p>
    </form>
  </div>
<?php
}
?>
