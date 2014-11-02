<?php
session_start();
if (!$_SESSION['loggedIn'])
{
    header("location:index.php");
}
require_once "template.php";
?>
<html>
  <head>
    <title>Home-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="home.css">
  </head>
  <body>
    <?php 
    top_bar();
    deck_list();
    option_panel();
    ?>
  </body>
</html>
<?php 
function deck_list()
{
?>
  <div class="deck-list">
    <h5>Here are your decks</h5>
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
  </div>
  </div>
<?php
}
?>