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
    }
}

function get_deck_list($userid, $con)
{
  $tablename="decks";
  $column="`title`,`deckid`";
  $restrict_str="WHERE `userid`='" . $userid . "';";
  $result=select_from($tablename, $column, $restrict_str, $con);

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
    $decks[$title]=$tags_array;
  }
  return json_encode($decks); // encode as JSON string
}

function get_current_deck($userid, $con)
{
  $tablename="users";
  $column="`deckid`";
  $restrict_str="WHERE `userid`='" . $userid . "';";
  $result=select_from($tablename, $column, $restrict_str, $con);

  $current_deck="";
  while ($row=mysqli_fetch_assoc($result))
  {
    $current_deck=$row['deckid'];
  }
  return $current_deck;
}
?>