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

function connect()
{
  $db=dbinfo();

  $con=mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);

  if (!$con) die ("Unable to connect to MySQL ");
  return $con;
}

// Perform a SELECT query and returns the results as a mysqli 
// result object. To get the rows from this object, you should
// use mysqli_fetch_assoc
// $columns should be a string of columns in this format:
// "`col1`,`col2`..."
// $restrict_str should be a string for other restriction when
// selecting such as 'ORDERED BY', 'WHERE', 'LIKE' and so on
function select_from($tablename, $columns, $restrict_str)
{
  $query="SELECT " . $columns . "FROM `$tablename`" 
  $query.=$restrict_str;
  $con=connect();
  
  if (!$result=mysqli_query($con, $select_query)) 
    die ("Error in selecting from $tablename " . mysqli_error($con));
  return $result;
}
?>