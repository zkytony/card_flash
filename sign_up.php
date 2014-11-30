<?php
session_start();
if ($_SESSION['loggedIn']) // if already logged in
{
  header("Location:home.php");
}

require_once "database.php";
require_once "functions.php";

if (isset($_POST['submit']))
{
  $con=connect();
  
  $username=mysqli_entities_fix_string($con, $_POST['username']);
  $password=mysqli_entities_fix_string($con, $_POST['password']);

  // if currently the username exists AND it is activate, then
  // sign up fails; Otherwise it succeeds; when there is already
  // the username but is not activate, change the password to the
  // current one used for sign up
  
  $column="`username`, `activate`";
  $result=select_from('users', $column, "",  $con);

  $available=true;
  $change_password=false;
  while ($row=mysqli_fetch_assoc($result))
  {
    if ($row['username'] == $username && $row['activate'] == true)
    {
      $available=false;
      break;
    } else if ($row['username'] == $username 
               && $row['activate'] == false) {
      $available=true;
      $change_password=true;
      break;
    }
  }

  if (!$available)
  {
    echo "<h2>Username already exists</h2>";
  } else {
    if (!$change_password) {
      $userid=substr($username, 0, 3) . $result->num_rows; // result has been obtained previously
      // ensure uniqueness
      $userid=ensure_unique_id($userid, "users", "userid", $con); 

      $columns="`userid`,`username`,`password`,`register_time`,`activate`";
      $values="'$userid','$username','$password', NOW(), '1'";
      insert_into('users', $columns, $values, $con); // insert into 'users'
    } else {
      $columns="`password`, `activate`, `register_time`";
      $values="'$password', '1', NOW()";
      update_table('users', $columns, $values, "", $con);
    }

    // registered;
    $_SESSION['loggedIn']=true;
    $_SESSION['username']=$username;
    $_SESSION['password']=$password;
    $_SESSION['userid']=$userid;
    header("location:home.php");
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