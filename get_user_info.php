<?php
session_start();
if (!$_SESSION['loggedIn'])
{
  header("Location:index.php");
}
require_once "database.php";

if (isset($_GET['action']) && !empty($_GET['action']))
{
  $action=$_GET['action'];
  $userid=$_GET['userid'];
  $con=connect();
  switch($action)
    {
    case 'deckList': 
      echo get_deck_list($userid, $con); 
      break;
    case 'currentDeck': 
      echo get_current_deck($userid, $con); 
      break;
    case 'updateDisplay':
      $deckid=$_GET['deckid'];
      echo update_displaying_deck($userid, $deckid, $con);
      break;
    }
} else {
    header("Location:index.php");
}

function get_deck_list($userid, $con)
{
  $tablename="decks";
  $column="`title`,`deckid`";
  $restrict_str="WHERE `userid`='" . $userid . "';";
  $result=select_from($tablename, $column, $restrict_str, $con);

  // intend to build JSON string in this fashion:
  // { deckid:
  //   { title:
  //     { [tag1, tag2 ...] }
  //   }
  // }
  $decks=array();
  while ($row=mysqli_fetch_assoc($result))
  {
    $title=$row['title'];
    $deckid=$row['deckid'];
    $tags_result=select_from("tags", "`tag`", 
                             "WHERE `deckid`='$deckid'", $con);
    $tags_array=array();
    while($tags_row=mysqli_fetch_assoc($tags_result))
    {
      $tags_array[]=$tags_row['tag'];
    }
    // you actually don't need to create $decks as 2D array. AMAZING
    $decks[$deckid][$title]=$tags_array; // deckid => title, title => tags
  }
  return json_encode($decks); // encode as JSON string
}

function get_current_deck($userid, $con)
{
  $tablename="users";
  $column="`deckid`";
  $restrict_str="WHERE `userid`='" . $userid . "';";
  $result=select_from($tablename, $column, $restrict_str, $con);

  $current_deckid="";
  while ($row=mysqli_fetch_assoc($result))
  {
    $current_deckid=$row['deckid'];
  }
  return $current_deckid;
}

// this is displaying the cards in the deck in home.php
// It is not necessary (Maybe yes) to return the content
// of the card because it will not be shown
// Content of the card should be retrieved when the user
// clicks on any spacific card
function update_displaying_deck($userid, $deckid, $con)
{
  $tablename="users";
  $column="deckid";
  $value="$deckid";
  $restrict_str="WHERE `userid`='" . $userid . "'";
  update_table($tablename, $column, $value, $restrict_str, $con);

  // retrieve cards from database
  $tablename="cards";
  $column="`cardid`, `title`, `sub`";
  $restrict_str="WHERE `deckid`='" . $deckid . "'";
  $result=select_from($tablename, $column, $restrict_str, $con);

  // intend to return JSON string in this fashion:
  // { cardid: {
  //         title,
  //         sub,
  //   }
  // }
  $card_data=array();
  while ($row=mysqli_fetch_assoc($result))
  {
    $cardid=$row['cardid'];
    $title=$row['title'];
    $sub=$row['sub'];
    
    $card_data[$cardid]["title"]=$title;
    $card_data[$cardid]["sub"]=$sub;
  }
  return json_encode($card_data);
}
?>