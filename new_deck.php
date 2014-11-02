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
    <title>New Deck-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="home.css">
    <link rel="stylesheet" type="text/css" href="deck.css">
  </head>
  <body>
    <?php 
    top_bar();
    deck_form();
    ?>
  </body>
</html>
<?php 
function deck_form()
{
?>
  <div class="deck-form-div">
    <form name="deck_form" action="" method="post">
      Title:<input type="text" name="title" id="title"/>
      Category:<input type="text" name="category" id="category"/>
      <h5>Categories you have used: </h5>
      <input type="submit" name="submit-deck" id="submit-deck" value="Submit"/>
    </form>
  </div>
<?php
}
?>

