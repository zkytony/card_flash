<?php
session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}
require_once "template.php";
require_once "database.php";

?>
<html>
  <head>
    <title>Home-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/home.css">
    <script src="http://code.jquery.com/jquery-2.1.0.min.js"></script>
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
      echo "<h3 class='notify'>Added a new deck</h3>";
      $_SESSION['new_card']=false;
    }
    ?>
    <script src="./script/home.js"></script>
  </body>
</html>

<?php
// php functions

// display current user's deck list
function deck_list()
{
?>
  <div class="deck-list">
    <h5>Here are your decks</h5>
    <?php 
    // get user's decks
    $db=dbinfo();
    $con=mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);

    if (!$con) die ("Unable to connect to MySQL " . mysqli_error($con));
    
    $tablename='decks';
    $result=mysqli_query($con, "SHOW TABLES LIKE '$tablename'");
    $exists=mysqli_num_rows($result) > 0;
    if (!$exists)
    {
      echo "The table $tablename does not exist!";
    } else {
      $current_userid=$_SESSION['userid'];
      $select_query="SELECT `deckid`,`title` FROM `$tablename` 
                     WHERE `userid`='$current_userid';";
      $result=mysqli_query($con, $select_query);
      if (!$result)
      {
        echo ("Unable to select from $tablename " . mysqli_error($con));
      } else {
        // selected
        echo "<ul>";
        while($row=mysqli_fetch_assoc($result))
        {
          $title=$row['title'];
          $deckid=$row['deckid'];
          echo "<li>";
          echo "<a class='deck-item' href='#'>$title</a>";
          echo "<p style='display:none' id></p>";

          // get all tags 
          $tablename='tags';
          $select_query="SELECT `tag` FROM `$tablename` 
                         WHERE `deckid`='$deckid';";
          $select_result=mysqli_query($con, $select_query);
          if (!$select_result) die ("Unable to select from $tablename" . mysqli_error($con));

          // selected
          echo "<span class='tag-span'>";
          while($tag_row=mysqli_fetch_assoc($select_result))
          {
            $tag=$tag_row['tag'];
            echo "<p class='one-tag'>";
            echo $tag;
            echo "</p>";
          }
          echo "</li>";
        }
        echo "</ul>";
      }
    }
    
    ?>
  </div>
<?php
}

function option_panel()
{
?>
  <div class="option-panel">
    <div class="panel-button" id="create-deck">
      <a href="new_deck.php">New Deck</a>
    </div>
    <div class="panel-button" id="create-card">
      <a href="new_card.php">New Card</a>
        <h6>Current deck: </h6>
        <?php 
        echo current_deck();
        ?>
    </div>
  </div>
<?php
}

function card_in_deck()
{
?>
  <div class="card-area">
    <h3>Not implemented yet. Place showing the cards</h3>
  </div>
<?php
}

function current_deck() 
{
?>
  <h5 id="current_deck">
    <?php 
    echo get_current_deck_title();
    ?>
  </h5>
<?php
}
?>