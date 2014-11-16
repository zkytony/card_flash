<?php
session_start();
if ($_SESSION['loggedIn']) // if already logged in
{
  header("Location:home.php");
}

require_once "database.php";

if (isset($_POST['submit']))
{
  $con=connect();
  
  $tablename='users';
  $username=mysqli_entities_fix_string($con, $_POST['username']);
  $password=mysqli_entities_fix_string($con, $_POST['password']);
  
  $column="`username`";
  $result=select_from($tablename, $column, "",  $con);

  $exists=false;
  while ($row=mysqli_fetch_assoc($result))
  {
    if (htmlspecialchars_decode($row['username']) == $username)
    {
      $exists=true;
      break;
    }
  }
  if ($exists)
  {
    echo "<h2>Username already exists</h2>";
  } else {
    $userid=substr($username, 0, 3) . $result->num_rows;
    $columns="`userid`,`username`,`password`,`register_time`";
    $values="'$userid','$username','$password', NOW()";
    insert_into($tablename, $columns, $values); // insert into 'users'

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
      <p><label for="username">Username: </label><input pattern=".{3,}" required title="3 characters minimum" class='input-field' name="username" id="username" title="Username" type="text" maxLength="10"></p>
      <p><label for="password">Password: </label><input pattern=".{3,}" required title="3 characters minimum" class="input-field" name="password" id="password" title="Password" type="password" maxLength="15"></p>
      <p><input name="submit" id="submit-signup" type="submit" value="sign up"/></p>
    <a href="index.php">Back</a>
    </form>
  </div>
<?php
}
?>
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