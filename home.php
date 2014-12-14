<?php
session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}
require_once "view/template.php";
require_once "view/home_view.php";
require_once "database.php";
require_once "quill.php";

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Home-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/home.css">
    <link rel="stylesheet" type="text/css" href="./css/m_quill.css">
    <?php 
    include_jquery();
    include_important_scripts();
    ?>
    <script src="./script/home.js"></script>
  </head>
  <body>
    <?php 
    top_bar();
    deck_list();
    option_panel();
    card_in_deck();
    if ($_SESSION['new_deck'])
    {
      echo "<h3 class='notify'>Added a new deck</h3>";
      $_SESSION['new_deck']=false;
    }
    if ($_SESSION['new_card'])
    {
      echo "<h3 class='notify'>Added a new card</h3>";
      $_SESSION['new_card']=false;
    }
    edit_card_div();
    ?>
    <div class="shade" id="overlay_shade"></div>
  </body>
</html>
