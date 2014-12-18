<?php
session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}
require_once "view/template.php";
require_once "view/card_edit_view.php";
require_once "quill.php";
require_once "database.php";
require_once "functions.php";

if (isset($_POST['submit-card']))
{
  //User submitted
  $con=connect();

  // count number of rows
  $column="`cardid`";
  $result=select_from("cards", $column, "", $con);
  $num_rows=$result->num_rows;

  $cardid='card' . $num_rows;
  $cardid=ensure_unique_id($cardid, "cards", "cardid", $con);

  $title=mysqli_entities_fix_string($con, $_POST['card_title']);
  $sub=mysqli_entities_fix_string($con, $_POST['card_sub']);
  $content=mysqli_entities_fix_string($con, $_POST['card_content']);
  $userid=$_SESSION['userid'];

  // get current deck id
  $column="`deckid`";
  $restrict_str="WHERE `userid`='$userid'";
  $result=select_from("users", $column, $restrict_str, $con);
  $deckid="";
  while ($row=mysqli_fetch_assoc($result)) 
  {
    $deckid=$row['deckid'];
  }
  // insert the card into table
  $columns="`cardid`,`title`,`sub`,`content`,";
  $columns.="`userid`,`deckid`,`create_time`,`deleted`";
  $values="'$cardid','$title','$sub','$content',";
  $values.="'$userid','$deckid',NOW(), '0'";
  insert_into("cards", $columns, $values, $con);

  // succeeded
  $_SESSION['new_card']=true;
  // because I encounter the issue that deckid is empty after card is submitted,
  update_table("users", array("`deckid`"), array("'$deckid'"), 
               "WHERE `userid`='$userid'", $con);
  
  header("location:home.php");
  // end of main script
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>New Card-<?php echo $_SESSION['username'] ?></title>
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
    card_form();
    preview_card();
    ?>
    <script src="./script/new_card.js"></script>
  </body>
</html>
<?php 
// use userid to get current deckid 
// and then get the title for that deck
function get_current_deck_title()
{
  $con=connect();
  $tablename='users';
  $userid=$_SESSION['userid'];

  $column="`deckid`";
  $restrict_str="WHERE `userid`='$userid'";
  $result=select_from($tablename, $column, $restrict_str, $con);

  $deckid="";
  while ($row=mysqli_fetch_assoc($result))
  {
    $deckid=$row['deckid'];
    break;
  }
  // get the name of deck
  $tablename='decks';
  $column="`title`";
  $restrict_str="WHERE `deckid`='$deckid'";
  $result = select_from($tablename, $column, $restrict_str, $con);
  
  $deck_title="";
  while ($row=mysqli_fetch_assoc($result))
  {
    $deck_title=$row['title'];
    break;
  }

  return $deck_title;
  // end of method get_deck_title
}