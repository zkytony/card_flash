<?php
session_start();
if (!$_SESSION['loggedIn'])
{
    header("location:index.php");
}
require_once "template.php";
?>
<html>
  <head>
    <title>New Card-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="home.css">
    <link rel="stylesheet" type="text/css" href="card.css">
  </head>
  <body>
    <?php 
    top_bar();
    ?>
  </body>
</html>
<?php 
?>
