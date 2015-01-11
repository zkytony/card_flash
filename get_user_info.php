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
require_once "models.php";

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
      //   { title: "title",
      //     tags: [tag1, tag2 ...]
      //   }
      // }
      $decks_obj = $user->get_decks(true, $con);
      $decks_arr = array();
      foreach ($decks_obj as $deck) {
        $deckid = $deck->get_id();
        $title = $deck->get_info()['title'];
        $decks_arr[$deckid]['title'] = $title;
        $decks_arr[$deckid]['tags'] = $deck->get_tags(true, $con);
      }
      echo json_encode($decks_arr); // encode as JSON string
      break;
    case 'sharedDeckList':
      // Intend to build JSON string in this fashion:
      // { deckid:
      //   { title: "title",
      //     tags: [tag1, tag2 ...] 
      //     owner: owner_username
      //   }
      // }
      // 0 as type, in order to get all
      $deckid_array = Share::shared_decks($user->get_id(), 0, $con);
      $decks_arr = array();
      foreach ($deckid_array as $deckid) {
        $deck = new Deck($deckid, $con);
        $title = $deck->get_info()['title'];
        $decks_arr[$deckid]['title'] = $title;
        $decks_arr[$deckid]['tags'] = $deck->get_tags(true, $con);
        $owner = new User($deck->get_info()['userid'], $con);
        $decks_arr[$deckid]['owner'] = $owner->get_info()['username'];
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

    case 'shareDeck':
      // Share the deck to users that exist;
      // Returns a json string of usernames that do not exist
      // Format:
      // {[username1, username2, ...]}
      $deckid=$_POST['deckid'];
      $usernames = $_POST['usernames'];
      // assume only visitor -- type 2
      $not_exist = array();
      foreach ($usernames as $username) {
        $userid = User::id_from_name($username, $con);
        if (strlen($userid) == 0) {
          $not_exist[] = $username;
        } else {          
          Share::share_to($deckid, $userid, 2, $con);
        }
      }
      echo json_encode($not_exist);
      break;
    }
} else {
    header("Location:index.php");
}
?>