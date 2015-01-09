<?php
require_once "view/template.php";
require_once "view/card_edit_view.php";
require_once "quill.php";
require_once "database.php";
require_once "functions.php";
require_once "modules.php";

session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}

if (isset($_POST['submit-card']))
{
  //User submitted
  $con=connect();

  $title=mysqli_entities_fix_string($con, $_POST['card_title']);
  $sub=mysqli_entities_fix_string($con, $_POST['card_sub']);
  $content=mysqli_entities_fix_string($con, $_POST['card_content']);

  // get current deck id of the user
  $user = $_SESSION['user'];
  $deckid = $user->get_info()['deckid'];
  $user->add_card($title, $sub, $content, $deckid, 0, $con);

  // succeeded
  $_SESSION['new_card']=true;
  
  header("location:home.php");
  // end of main script
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>New Card-<?php $user = $_SESSION['user']; echo $user->get_info()['username']; ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/template.css">
    <link rel="stylesheet" type="text/css" href="./css/card.css">
    <link rel="stylesheet" type="text/css" href="./css/m_quill.css">
    <?php 
    include_fonts();
    include_jquery();
    include_important_scripts();
    ?>
  </head>
  <body>
    <?php 
    require_once "database.php";
    top_bar();
    echo get_current_deck_title();
    ?>
    <div class="wrapper-new-card-main">
    <?php
    card_form(); // from card_edit_view.php
    preview_card(); // from card_edit_view.php
    ?>
    </div>
    <script src="./script/new_card.js"></script>
  </body>
</html>
<?php 
// use userid to get current deckid 
// and then get the title for that deck
function get_current_deck_title()
{
  $con=connect();
  $user=$_SESSION['user'];
  $deckid = $user->get_info()['deckid'];

  $current_deck = new Deck($deckid, $con);
  return $current_deck->get_info()['title'];
}