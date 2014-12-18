<?php
session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}
require_once "view/template.php";
require_once "view/deck_edit_view.php";
require_once "database.php";
require_once "functions.php";

if (isset($_POST['submit-deck']))
{
  //User submitted
  $con=connect();
  
  // mysql::num_rows: return number of rows in a query result
  // select to get number of rows
  $result=select_from('decks',"`deckid`","",$con);

  $num_rows=$result->num_rows;
  $deckid='deck' . $num_rows;
  $deckid=ensure_unique_id($deckid, "decks", "deckid", $con);
  $title=mysqli_entities_fix_string($con, $_POST['title']);
  $userid=$_SESSION['userid']; // you must use individual variables to store them

  // insert user's new deck to table; !Values should be single quote. Columns dont have quote
  $columns="`deckid`,`title`,`userid`,`create_time`,`deleted`";
  $values="'$deckid','$title','$userid',NOW(), '0'";
  insert_into('decks', $columns, $values, $con);
  
  // tags and categories (category is the name of the field in the form)
  $category_str=mysqli_entities_fix_string($con, $_POST['category']);
  $tags=split_to_tags($category_str);

  $result=select_from('tags', '*', "", $con);

  $num_rows=$result->num_rows;

  for ($i=0; $i<sizeof($tags); $i++)
  {
    $rid = "tag" . $num_rows;
    $rid = ensure_unique_id($rid, "tags", "rid", $con);
    $num_rows++;
    
    // insert this tag-deckid relationship to the table
    $columns="`rid`, `tag`, `deckid`, `deleted`";
    $values="'$rid', '$tags[$i]', '$deckid', '0'";
    insert_into('tags', $columns, $values, $con);
  }

  // last thing - update current_deckid
  $tablename='users';
  update_table('users', array("`deckid`"), array("'$deckid'"), 
               "WHERE `userid`='$userid'", $con);

  // everything done
  $_SESSION['new_deck']=true;
  header("location:home.php");
} // end of main script
?>
<!DOCTYPE html>
<html>
  <head>
    <title>New Deck-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/home.css">
    <link rel="stylesheet" type="text/css" href="./css/deck.css">
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
  $tags=preg_split("/[\s,]+/",$str);
  return $tags;
}
?>