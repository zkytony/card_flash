<?php
session_start();
if ($_SESSION['loggedIn']) // if already logged in
{
  header("Location:home.php");
}

require_once "database.php";
require_once "functions.php";
require_once "modules.php";

if (isset($_POST['submit']))
{
  $con=connect();
  
  $username=mysqli_entities_fix_string($con, $_POST['username']);
  $password=mysqli_entities_fix_string($con, $_POST['password']);

  $success = User::register($username, $password, $con);
  if ($success) {
    // registered;
    $_SESSION['loggedIn']=true;
    $_SESSION['user'] = User::sign_in($username, $password, $con);
    $_SESSION['username']=$username;
    $_SESSION['password']=$password;
    $_SESSION['userid']=$userid;
    header("location:home.php");
  } else {
    echo "<h3>Username already exist</h3>";
  }
}
?>
<?php 
function form_signup()
{
?>
  <div class="form-signup">
    <h3>Sign up</h3>
    <form name="signup" action="<?php echo $_SERVER['PHP_SELF'];?>"  method="post">
      <p><label for="username">Username: </label><input pattern="[a-zA-Z0-9]{3,}" required title="3 characters minimum, no special characters" class='input-field' name="username" id="username" title="Username" type="text" maxLength="10"></p>
      <p><label for="password">Password: </label><input pattern=".{3,}" required title="3 characters minimum" class="input-field" name="password" id="password" title="Password" type="password" maxLength="15"></p>
      <p><input name="submit" id="submit-signup" type="submit" value="sign up"/></p>
    <a href="index.php">Back</a>
    </form>
  </div>
<?php
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>sign up</title>
    <link rel="stylesheet" type="text/css" href="./css/sign_up.css">
  </head>
  <body>
    <?php 
    form_signup();
    ?>
  </body>
</html>