<?php
function dbinfo()
{
  $db = array (
    "hostname"=>"localhost",
    "database"=>"flashcard",
    "username"=>"kaiyu",
    "password"=>"123abc",
  );
  return $db;
}

function init_tables($con)
{
  init_users_table($con);
  init_decks_table($con);
  init_cards_table($con);
  init_tags_table($con);
}

function init_users_table($con)
{
  // connected to mysql
  $tablename='users';

  // attention, you must use ` to quote names
  $query="CREATE TABLE IF NOT EXISTS `$tablename` (
          `userid` VARCHAR(32) UNIQUE NOT NULL,
          `username` VARCHAR(128) NOT NULL,
          `password` VARCHAR(128) NOT NULL,
          `register_time` DATE NOT NULL,
          `deckid` VARCHAR(32),
          PRIMARY KEY(`userid`),
          CONSTRAINT `current_deckid` FOREIGN KEY (`deckid`) REFERENCES decks(`deckid`),
          INDEX(`username`(10))) ENGINE MyISAM;";
  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con));      
  }
}

function init_decks_table($con)
{
  $tablename='decks';
  // create the table for decks if not exists
  // relates to users table
  $query="CREATE TABLE IF NOT EXISTS `$tablename` (
          `deckid` VARCHAR(32) UNIQUE NOT NULL,
          `title` VARCHAR(128) NOT NULL,
          `userid` VARCHAR(32) NOT NULL,
          `create_time` DATE NOT NULL,
          PRIMARY KEY (`deckid`),
          INDEX(`title`(10)),
          FOREIGN KEY (`userid`) REFERENCES users(`userid`) 
         ) ENGINE MyISAM;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con));
  }
}

function init_cards_table($con)
{
  $tablename='cards';
  // create the table for decks if not exists
  // relates to users table
  $query="CREATE TABLE IF NOT EXISTS `$tablename` (
          `cardid` VARCHAR(32) UNIQUE NOT NULL,
          `title` VARCHAR(128) NOT NULL,
          `sub` TINYTEXT NOT NULL,
          `content` MEDIUMTEXT NOT NULL,
          `userid` VARCHAR(32) NOT NULL,
          `deckid` VARCHAR(32) NOT NULL,
          `create_time` DATE NOT NULL,
          PRIMARY KEY (`cardid`),
          INDEX(`title`(10)),
          INDEX(`sub`(10)),
          FOREIGN KEY (`userid`) REFERENCES users(`userid`),
          FOREIGN KEY (`deckid`) REFERENCES decks(`deckid`)
         ) ENGINE MyISAM;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con));
  }
}

function init_tags_table($con)
{
  $tablename='tags';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` (
          `rid` VARCHAR(32) UNIQUE NOT NULL,
          `tag` VARCHAR(32) NOT NULL,
          `deckid` VARCHAR(32) NOT NULL,
          PRIMARY KEY(`rid`),
          FOREIGN KEY(`deckid`) REFERENCES decks(`deckid`)
          ) ENGINE MyISAM;";
  
  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con));
  }
}

// use userid to get current deckid 
// and then get the title for that deck
function get_current_deck_title()
{
  $db=dbinfo();

  $con=mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);

  if (!$con) die ("Unable to connect to MySQL ");

  $tablename='users';
  $userid=$_SESSION['userid'];
  $select_query="SELECT `deckid` FROM `$tablename` WHERE `userid`='$userid';";
  if (!$result=mysqli_query($con, $select_query)) 
    die ("Error in selecting from $tablename " . mysqli_error($con));
  $deckid="";
  while ($row=mysqli_fetch_assoc($result))
  {
    $deckid=$row['deckid'];
    break;
  }
  // get the name of deck
  $tablename='decks';
  $select_query="SELECT `title` FROM `$tablename` WHERE `deckid`='$deckid';";
  if (!$result=mysqli_query($con, $select_query)) 
    die ("Error in selecting from $tablename " . mysqli_error($con));
  $deck_title="";
  while ($row=mysqli_fetch_assoc($result))
  {
    $deck_title=$row['title'];
    break;
  }

  return $deck_title;
// end of method get_deck_title
}
?>