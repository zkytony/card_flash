<?php
/*
   Handles the ajax requests relating to the user's info
   Handling:
      get_deck_list(),
      get_current_deck(),
      update_displaying_deck(),
      delete_card(),

   p.s. the name of this file may not be appropriate for its use
 */
require_once "database.php";
require_once "modules.php";

session_start();
if (!$_SESSION['loggedIn'])
{
  header("Location:index.php");
}

if (isset($_POST['action']) && !empty($_POST['action']))
{
  $action=$_POST['action'];
  $userid=$_POST['userid'];
  $con=connect();
  $user=$_SESSION['user'];
  switch($action)
    {
    case 'deckList': 
      // Intend to build JSON string in this fashion:
      // { deckid:
      //   { title:
      //     { [tag1, tag2 ...] }
      //   }
      // }
      $decks_obj = $user->get_decks(true, $con);
      $decks_arr = array();
      foreach ($decks_obj as $deck) {
        $deckid = $deck->get_id();
        $title = $deck->get_info()['title'];
        $decks_arr[$deckid][$title] = $deck->get_tags(true, $con);
      }
      echo json_encode($decks_arr); // encode as JSON string
      break;

    case 'currentDeck': 
      $current_deckid = $user->get_info()['deckid'];
      $deck = new Deck($current_deckid, $con);
      echo $deck->get_info()['title'];
      break;

    case 'updateDisplay':
      // Intend to build JSON string in this fashion:
      // { cardid: {
      //         title,
      //         sub,
      //         content,
      //   }
      // }
      $deckid = $_POST['deckid'];

      $user->update_current_deck($deckid, $con);
      $cards = Card::get_cards($deckid, true, $con);

      $card_data=array();
      foreach ($cards as $card) 
      {
        $info = $card->get_info();
        $cardid=htmlspecialchars_decode($info['cardid']);
        $title=htmlspecialchars_decode($info['title']);
        $sub=htmlspecialchars_decode($info['sub']);
        $content=htmlspecialchars_decode($info['content']);
        
        $card_data[$cardid]['title']=$title;
        $card_data[$cardid]['sub']=$sub;
        $card_data[$cardid]['content']=$content;
      }
      echo json_encode($card_data);
      break;
    case 'deleteCard':
      $cardid=$_POST['cardid'];
      $success = Card::delete($cardid, $con);
      if ($success) {
        echo "success";
      } else {
        echo "failed";
      }
      break;
    case 'deleteDeck':
      $deckid=$_POST['deckid'];
      $success = Deck::delete($deckid, $con);
      if ($success) {
        echo "success";
      } else {
        echo "failed";
      }
      break;
    }
} else {
    header("Location:index.php");
}
?>