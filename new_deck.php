<?php
require_once "view/template.php";
require_once "view/deck_edit_view.php";
require_once "database.php";
require_once "functions.php";
require_once "modules.php";

session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}

if (isset($_POST['submit-deck']))
{
  //User submitted
  $con=connect();
  
  $title=mysqli_entities_fix_string($con, $_POST['title']);
  
  // tags and categories (category is the name of the field in the form)
  $category_str=mysqli_entities_fix_string($con, $_POST['category']);
  $tags=split_to_tags($category_str);

  $user=$_SESSION['user'];
  $user->add_deck($title, $tags, $con);
  
  // everything done
  $_SESSION['new_deck']=true;
  header("location:home.php");
} // end of main script
?>
<!DOCTYPE html>
<html>
  <head>
    <title>New Deck-<?php $user = $_SESSION['user']; echo $user->get_info()['username']; ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/template.css">
    <link rel="stylesheet" type="text/css" href="./css/deck.css">
    <?php 
    include_fonts();
    ?>
  </head>
  <body>
    <?php 
    top_bar();
    deck_form();
    ?>
  </body>
</html>
<?php 

function split_to_tags($str)
{
  $tags=preg_split("/[,]+/",$str);
  return $tags;
}
?>