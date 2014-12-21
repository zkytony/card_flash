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
    case 'deleteCard':
      $cardid=$_GET['cardid'];
      echo delete_card($cardid, $con);
      break;
    case 'deleteDeck':
      $deckid=$_GET['deckid'];
      echo delete_deck($deckid, $con);
      break;
    }
} else {
    header("Location:index.php");
}

function get_deck_list($userid, $con)
{
  $tablename="decks";
  $column="`title`,`deckid`";
  $restrict_str="WHERE `userid`='$userid' AND `deleted` = '0' ORDER BY `create_time`";
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
    $restrict_str="WHERE `deckid`='$deckid' AND `deleted` = '0' ORDER BY `tag`";
    $tags_result=select_from("tags", "`tag`", $restrict_str, $con);
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
  $restrict_str="WHERE `userid`='$userid'";
  $result=select_from($tablename, $column, $restrict_str, $con);

  $current_deckid="";
  while ($row=mysqli_fetch_assoc($result))
  {
    $current_deckid=$row['deckid'];
  }
  return $current_deckid;
}

// fetch the title, subtitle, content of the card
// based on userid and deckid, and returns a json string
// containing these data
function update_displaying_deck($userid, $deckid, $con)
{
  $tablename="users";
  $column=array("`deckid`");
  $value=array("'$deckid'");
  $restrict_str="WHERE `userid`='" . $userid . "'";
  update_table($tablename, $column, $value, $restrict_str, $con);

  // retrieve cards from database
  $tablename="cards";
  $column="`cardid`, `title`, `sub`, `content`";
  $restrict_str="WHERE `deckid`='$deckid' AND `deleted` = '0' ORDER BY `create_time`";
  $result=select_from($tablename, $column, $restrict_str, $con);

  // intend to return JSON string in this fashion:
  // { cardid: {
  //         title,
  //         sub,
  //         content,
  //   }
  // }
  $card_data=array();
  while ($row=mysqli_fetch_assoc($result))
  {
    $cardid=htmlspecialchars_decode($row['cardid']);
    $title=htmlspecialchars_decode($row['title']);
    $sub=htmlspecialchars_decode($row['sub']);
    $content=htmlspecialchars_decode($row['content']);
    
    $card_data[$cardid]['title']=$title;
    $card_data[$cardid]['sub']=$sub;
    $card_data[$cardid]['content']=$content;
  }
  return json_encode($card_data);
}

// delete a card. Yet instead of actually deleting it from
// the database, we mark it as 'deleted', in case the user
// wants to restore
function delete_card($cardid, $con)
{
  $column=array("`deleted`");
  $value=array("'1'");
  $restrict_str="WHERE `cardid` = '$cardid'";
  update_table("cards", $column, $value, $restrict_str, $con);
  return "success";
}

// delete the deck. Mark it as deleted by setting the corresponding
// value in the `deleted` column
function delete_deck($deckid, $con)
{
  // first mark all cards in this deck as deleted
  $result=select_from("cards", "`cardid`", 
                      "WHERE `deckid` = '$deckid'", $con);
  while ($row=mysqli_fetch_assoc($result))
  {
    delete_card($row['cardid'], $con);
  }

  // then, mark all tags of this deck as delted
  $result=select_from("tags", "`rid`",
                      "WHERE `deckid` = '$deckid'", $con);
  while ($row=mysqli_fetch_assoc($result))
  {
    delete_tag($row['rid'], $con);
  }
  
  // then, mark the deck as deleted
  $restrict_str="WHERE `deckid` = '$deckid'";
  update_table("decks", array("`deleted`"), array("'1'"), $restrict_str, $con);
  return "success";
}

function delete_tag($rid, $con)
{
  $restrict_str="WHERE `rid` = '$rid'";
  update_table("tags", array("`deleted`"), array("'1'"), $restrict_str, $con);
  return "success";
}
?>