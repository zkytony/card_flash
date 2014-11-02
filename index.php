<?php
ob_start(); // turn on output buffer
extract($_POST);
session_start();
if (isset($_POST['submit']))
{
  //User submitted
  $db_hostname='xxxx';
  $db_database='xxxx';
  $db_username='xxxx';
  $db_password='xxxx';

  $con=mysqli_connect($db_hostname, $db_username, $db_password, $db_database);

  if (!$con) die ("Unable to connect to MySQL: " . mysqli_error($con));
  
  // connected to mysql
  $tablename='users';

  // attention, you must use ` to quote names
  $query="CREATE TABLE IF NOT EXISTS `$tablename` (
          `userid` VARCHAR(32) UNIQUE NOT NULL,
          `username` VARCHAR(128) NOT NULL,
          `password` VARCHAR(128) NOT NULL,
          `register_time` DATE NOT NULL,
          PRIMARY KEY(`userid`),
          INDEX(`username`(10))) ENGINE MyISAM;";
  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_errors($con));               
  }
  
  $username=$_POST['username'];
  $password=$_POST['password'];

  $query="SELECT * FROM users WHERE username='$username' AND password='$password'";
  $result=mysqli_query($con, $query);
  if(!result) die("Database access failed: " . mysql_error());
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
    header("location:home.php");
  } else {
    $_SESSION['loggedIn']=false;
    echo "<h1>Wrong Username/password</h1>";
  }
  ob_end_flush(); //Flush (send) the output buffer and turn off output buffering
}
?>
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
      <meta char-set='utf-8'>
      <link rel='stylesheet' type='text/css' href='main.css'>
    </head>
<?php
}

// block of HTML for form
function form_login()
{
?>
  <div class="form-login">
    <h3>Login</h3>
    <form name="login "action="<?php echo $_SERVER['PHP_SELF'];?>" name="login" method="post">
      <p><label for="username">Username: </label><input class='input-field' name="username" id="username" title="Username" type="text" maxLength="10"></p>
      <p><label for="password">Password: </label><input class="input-field"name="password" id="password" title="Password" type="password" maxLength="15"></p>
      <p><input name="submit" id="submit" type="submit" value="Log in"/>
    </form>
  </div>
<?php
}
?>
