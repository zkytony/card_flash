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

if ($_POST['submit-card'])
{
  $con=connect();

  // user confirms edition to a card
  $title=mysqli_entities_fix_string($con, $_POST['card_title']);
  $sub=mysqli_entities_fix_string($con, $_POST['card_sub']);
  $content=mysqli_entities_fix_string($con, $_POST['card_content']);
  $cardid=$_POST['card_id'];
  
  update_table("cards", array("`title`","`sub`","`content`"),
               array("'$title'","'$sub'","'$content'"), 
               "WHERE `cardid` = '$cardid'", $con);
}

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Home-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/home.css">
    <link rel="stylesheet" type="text/css" href="./css/m_quill.css">
    <link rel="stylesheet" type="text/css" href="./css/template.css">
    <?php 
    include_fonts(); // from template.php
    include_jquery();
    include_important_scripts(); // from template.php
    include_js_plugin(); // jquery transit
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
    flip_card_div();
    ?>
    <div class="shade" id="overlay_shade"></div>
  </body>
</html>
