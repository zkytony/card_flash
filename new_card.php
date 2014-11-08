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
  $db=dbinfo();

  $con=mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);

  if (!$con) die ("Unable to connect to MySQL ");

  $tablename='cards';
  $select_query="SELECT `cardid`, FROM `$tablename`;";
  if (!$result=mysqli_query($con, $select_query)) 
    die ("Error in selecting from $tablename " . mysqli_error($con));
  $num_rows=$result->num_rows;
  $cardid='card' . $num_rows;
  $title=$_POST['card_title'];
  $sub=$_POST['card_sub'];
  $content=$_POST['card_content'];
  $userid=$_SESSION['userid'];

  // get current deck id
  $tablename='users';
  $select_query="SELECT `current_deckid` FROM `$tablename` 
                 WHERE `userid`='$userid';";
  if (!$result=mysqli_query($con, $select_query)) 
    die ("Error in selecting from $tablename " . mysqli_error($con));
  $deckid=$result['current_deckid'];
  
  $insert_query="INSERT INTO `$tablename` (`cardid`,`title`,
                     `sub`,`content`,`userid`,`deckid`,`create_time`) 
                     VALUES ('$cardid','$title','$sub','$content',
                     '$userid','$deckid','NOW()');";

  if (!mysqli_query($con, $insert_query))
    die ("Unable to insert into $tablename" . mysqli_error($con));

  // succeeded
  $_SESSION['new_card']=true;
  header("location:home.php");
  // end of main script
}
?>
<html>
  <head>
    <title>New Card-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/home.css">
    <link rel="stylesheet" type="text/css" href="./css/card.css">
    <link rel="stylesheet" type="text/css" href="./css/m_quill.css">
    <script src="http://code.jquery.com/jquery-2.1.0.min.js"></script>
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
function card_form()
{
?>
  <div class="card-div">
    <form name="card_form" id="card_form" "action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
      <?php 
      card_front();
      card_back();
      ?>
      <textarea class="hidden" id="hidden_input" name="hidden_input" style="display:none"></textarea>
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