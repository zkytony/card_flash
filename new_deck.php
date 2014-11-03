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
  // create the table for decks if not exists
  // relates to users table
  $query="CREATE TABLE IF NOT EXISTS `$tablename` (
          `deckid` VARCHAR(32) UNIQUE NOT NULL,
          `title` VARCHAR(128) NOT NULL,
          `userid` VARCHAR(32) NOT NULL,
          `create_time` DATE NOT NULL,
          PRIMARY KEY (`deckid`),
          INDEX(`title`(10)),
          FOREIGN KEY (`userid`) REFERENCES users(`userid`) 
         ) ENGINE MyISAM;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con));
  }

  // mysql::num_rows: return number of rows in a query result
  // select to get number of rows
  $select_query="SELECT * FROM `$tablename`;";
  if (!$result=mysqli_query($con, $select_query)) 
    die ("Error in selecting from $tablename " . mysqli_error($con));

  $num_rows=$result->num_rows;
  $deckid='deck' . $_SESSION['username'] . $num_rows;
  $title=$_POST['title'];
  $userid=$_SESSION['userid']; // you must use individual variables to store them

  // insert user's new deck to table; !Values should be single quote. Columns dont have quote
  $insert_query="INSERT INTO $tablename (deckid, title, userid, create_time) 
                     VALUES ('$deckid','$title','$userid','NOW()');";
  if (!mysqli_query($con, $insert_query))
  {
    die ("Error in inserting into $tablename " . mysqli_error($con));
  }
  
  // tags and categories
  $category_str=$_POST['category'];
  $tags=split_to_tags($category_str);

  // check if the table of relationship between tags and decks exists, if not create one
  $tablename='tags';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` (
          `rid` VARCHAR(32) UNIQUE NOT NULL,
          `tag` VARCHAR(32) NOT NULL,
          `deckid` VARCHAR(32) NOT NULL,
          PRIMARY KEY(`rid`),
          FOREIGN KEY(`deckid`) REFERENCES decks(`deckid`)
          ) ENGINE MyISAM;";
  
  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con));
  }

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

  // everything done
  $_SESSION['new_deck']=true;
  header("location:home.php");
} // end of main script
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

