<?php
session_start();
if ($_SESSION['loggedIn']) // if already logged in
{
  header("Location:home.php");
}

require_once "database.php";

if (isset($_POST['submit']))
{
  $db=dbinfo();

  $con=mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);

  if (!$con) die ("Unable to connect to MySQL: " . mysqli_error($con)); // connected to mysql
  
  $tablename='users';
  $username=$_POST['username'];
  $password=$_POST['password'];
  
  $query="SELECT `username` FROM `$tablename`;";
  $result=mysqli_query($con, $query);
  if (!$result) die ("Cannot select from $tablename " . mysqli_error());
  
  $exists=false;
  while ($row=mysqli_fetch_assoc($result))
  {
    if ($row['username'] == $username)
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
    var_dump($userid);
    var_dump($username);
    $query="INSERT INTO `$tablename` (`userid`,`username`,`password`,`register_time`) VALUES ('$userid','$username','$password', NOW());";
    if (!mysqli_query($con, $query))
    {
      die ("Unable to register for $username " . mysqli_error());
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