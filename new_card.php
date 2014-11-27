<?php
session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}
require_once "template.php";
require_once "quill.php";
require_once "database.php";

if (isset($_POST['submit-card']))
{
  //User submitted
  $con=connect();

  // count number of rows
  $column="`cardid`";
  $result=select_from("cards", $column, "", $con);
  $num_rows=$result->num_rows;

  $cardid='card' . $num_rows;
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
  $columns.="`userid`,`deckid`,`create_time`";
  $values="'$cardid','$title','$sub','$content',";
  $values.="'$userid','$deckid',NOW()";
  insert_into("cards", $columns, $values, $con);

  // succeeded
  $_SESSION['new_card']=true;
  header("location:home.php");
  // end of main script
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>New Card-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/home.css">
    <link rel="stylesheet" type="text/css" href="./css/card.css">
    <link rel="stylesheet" type="text/css" href="./css/m_quill.css">
    <?php 
    include_jquery();
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
  $restrict_str="WHERE `userid`='" . $userid . "';";
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
  $restrict_str="WHERE `deckid`='" . $deckid . "';";
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

function card_form()
{
?>
  <div class="card-div">
    <form name="card_form" id="card_form" "action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
      <?php 
      card_front();
      card_back();
      ?>
      <textarea class="hidden" id="hidden_input" name="card_content" style="display:none"></textarea>
      <input type="submit" value="Done" id="submit" name="submit-card" onsubmit="onsubmit()" class="submit-card"/>
    </form>
  </div>
<?php
}

function card_front()
{
?>
  <div class="card-frame" id="card_front_edit">
    Title: <input class="card-field" id="card_title" type="text" name="card_title"  />
    Subdescription: <input class="card-field card-sub" id="card_sub" type="text" name="card_sub" />
  </div>
<?php
}

function card_back()
{
?>
  <div class="card-frame" id="card_back_edit">
    <?php 
    build_editor(); // from quill.php
    ?>
  </div>
<?php
}

function preview_card()
{
?>
  <div class="preview-card">
    <h2>Preview</h2>
    <div class="card-frame" id="card_front_preview">
      <h4>Front</h4>
    </div>
    <div class="card-frame" id="card_back_preview">
      <h4>Back</h4>
    </div>
  </div>
<?php
}
?>