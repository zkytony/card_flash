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
    <script>showDeckList(<?php echo $_SESSION['userid']?>);</script>
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
        <script>currentDeck();</script>
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
    <script>getCurrentDeckTitle();</script>
  </h5>
<?php
}
?>