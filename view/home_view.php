<?php

// display current user's deck list
function deck_list()
{
?>
  <div class="deck-list">
    <h5>Here are your decks</h5>
    <div id="deck-list-div">
      <script>
       showDeckList("<?php $user = $_SESSION['user']; echo $user->get_id();?>");
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
      <a href="new_card.php" id="new-card-link" onclick="checkCurrentDeck(event)">New Card</a>
        <h6>Current deck: </h6>
        <span id="current-deck-span">
          <script>currentDeck("<?php $user = $_SESSION['user']; echo $user->get_id();?>");</script>
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

function edit_card_div()
{
?>
  <div class="pop-up-div" id="edit-card-area">
    <?php
    card_form(); // got from card_edit_view.php
    ?>
    <a href="javascript:void(0)" class="close-pop-up-button" onclick="closePopUp();">Close</a>
  </div>
<?php
}

function flip_card_div()
{
?>
  <div class="pop-up-div" id="flip-card-area-wrapper">
    <div id="flip-card-area"></div>
    <a href="javascript:void(0)" class="close-pop-up-button" onclick="closePopUp();">Close</a>
  </div>
<?php
}


function include_js_plugin()
{
?>
  <script src="script/jquery.transit.min.js"></script>
  <script src="script/jquery.quickflip.min.js"></script>
<?php
}
?>