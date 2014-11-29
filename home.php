<?php
session_start();
if (!$_SESSION['loggedIn'])
{
  header("location:index.php");
}
require_once "template.php";
require_once "database.php";

?>
<!DOCTYPE html>
<html>
  <head>
    <title>Home-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/home.css">
    <?php 
    include_jquery();
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
    ?>
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
    <div id="deck-list-div">
      <script>
       showDeckList("<?php echo $_SESSION['userid']?>");
      </script>
    </div>
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
        <span id="current-deck-span">
          <script>currentDeck("<?php echo $_SESSION['userid']?>");</script>
        </span>
    </div>
  </div>
<?php
}

// the content in the deck is get displayed when
// loading the deck list. So this message is for
// those who don't have a deck yet
function card_in_deck()
{
?>
  <div class="card-area" id="card-display-div">
    <h3>There is no card. Create/Select a deck</h3>
  </div>
<?php
}
?>