<?php
session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}
require_once "template.php";
require_once "database.php";
if (isset($_POST['submit-deck']))
{
  //User submitted
  $db=dbinfo();

  $con=mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);

  if (!$con) die ("Unable to connect to MySQL ");

  $tablename='decks';
  
  // mysql::num_rows: return number of rows in a query result
  // select to get number of rows
  $select_query="SELECT `deckid` FROM `$tablename`;";
  if (!$result=mysqli_query($con, $select_query)) 
    die ("Error in selecting from $tablename " . mysqli_error($con));

  $num_rows=$result->num_rows;
  $deckid='deck' . $_SESSION['username'] . $num_rows;
  $title=$_POST['title'];
  $userid=$_SESSION['userid']; // you must use individual variables to store them

  // insert user's new deck to table; !Values should be single quote. Columns dont have quote
  $insert_query="INSERT INTO $tablename (deckid, title, userid, create_time) VALUES ('$deckid','$title','$userid','NOW()');";
  if (!mysqli_query($con, $insert_query))
  {
    die ("Error in inserting into $tablename " . mysqli_error($con));
  }
  
  // tags and categories
  $category_str=$_POST['category'];
  $tags=split_to_tags($category_str);

  $tablename='tags';
  $select_query="SELECT * FROM `$tablename`;";
  if (!$result=mysqli_query($con, $select_query))
    die ("Error in selecting from $tablename " . mysqli_error($con));

  $num_rows=$result->num_rows;

  for ($i=0; $i<sizeof($tags); $i++)
  {
    $rid = "RE" . $num_rows;
    $num_rows++;
    
    // insert this tag-deckid relationship to the table
    $insert_query="INSERT INTO `$tablename` (`rid`, `tag`, `deckid`)
                       VALUES ('$rid', '$tags[$i]', '$deckid');";
    if (!mysqli_query($con, $insert_query))
      die ("Unable to insert into $tablename " . mysqli_error($con));
  }

  // last thing - update current_deckid
  $tablename='users'
  $update_query="UPDATE `$tablename` SET `current_deckid`='$deckid' WHERE `userid`='$userid';";
  if (!mysqli_query($con, $update_query))
    die ("Unable to update $tablename " . mysqli_error($con));

  // everything done
  $_SESSION['new_deck']=true;
  header("location:home.php");
} // end of main script
?>
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

function deck_form()
{
?>
  <div class="deck-form-div">
    <form name="deck_form" action="<?php echo $_SERVER['PHP_SELF'];?>"  method="post">
      Title:<input class="deck-field" type="text" name="title" id="title"/>
      Category:<input class="deck-field" type="text" name="category" id="category"/>
      <h5>Categories you have used: </h5>
      <input type="submit" name="submit-deck" id="submit-deck" value="Submit" />
    </form>
  </div>
<?php
}
?>

