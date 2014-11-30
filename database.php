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
          `username` VARCHAR(128) UNIQUE NOT NULL,
          `password` VARCHAR(128) NOT NULL,
          `register_time` DATE NOT NULL,
          `deckid` VARCHAR(32),
          `activate` BOOL,
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
          `deleted` BOOL,
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
          `deleted` BOOL,
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
          `deleted` BOOL,
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

// Function that prevents MYSQL injection & HTML injection
// To decode the html entities:
//    htmlspecialchars_decode($decoded_string)
function mysqli_entities_fix_string($connect, $string)
{
    return htmlentities(mysqli_fix_string($connect, $string));
}

function mysqli_fix_string($connect, $string)
{
    if (get_magic_quotes_gpc()) $string = stripslashes($string);
    return mysqli_real_escape_string($connect, $string);
}

// Perform a SELECT query and returns the results as a mysqli 
// result object. To get the rows from this object, you should
// use mysqli_fetch_assoc
// $columns should be a string of columns in this format:
// "`col1`,`col2`..."
// $restrict_str should be a string for other restriction when
// selecting such as 'ORDERED BY', 'WHERE', 'LIKE' and so on
// $con is the mysqli_connect object
function select_from($tablename, $columns, $restrict_str, $con)
{
  $query="SELECT " . $columns . "FROM `$tablename`";
  $query.=$restrict_str + ";";
  
  if (!$result=mysqli_query($con, $query)) 
    die ("Error in selecting from $tablename " . mysqli_error($con));
  
  return $result;
}

// Perform a INSERT query to the specified table
// $columns should be a string of columns in this format:
// "`col1`,`col2`..."
// $values is the string that stores each value corresponding
// to each column (the ordering should be the same as in $columns)
function insert_into($tablename, $columns, $values, $con)
{
  $query="INSERT INTO `$tablename` (" . $columns . ")";
  $query.="VALUES (" . $values . ");";
  if (!mysqli_query($con, $query))
  {
    die ("Error in inserting into $tablename " . mysqli_error($con));
  }
}

// Perform a update query to the specified table
// Allows updating multiple columns.
// Required input format:
// - $columns is a string, each column is separated by a comma ','
//   e.g. "col1, col2, col3"
// - $values is a string, each value is separated by a comma ','
//   e.g. "val1, val2, val3"
// Ordering of $values should be according to the ordering
// of $columns
function update_table($tablename, $columns,
                      $values, $restrict_str, $con)
{
  $set_str=" SET ";
  $columns_arr=preg_split("/[\s,]+/", $columns);
  $values_arr=preg_split("/[\s,]+/", $values);
  $len=sizeof($columns_arr);
  for ($i=0; $i<$len; $i++)
  {
    $set_str.="`$columns_arr[$i]` = '$values_arr[$i]'";
    if ($i<$len-1)
    {
      $set_str.=", ";
    } else {
      $set_str.=" ";
    }
  }
  $query="UPDATE `$tablename`";
  $query.=$set_str;
  $query.=$restrict_str;
  if (!mysqli_query($con, $query))
  {
    die ("Error in Update $tablename " . mysqli_error($con));
  }  
}

// Perform a Delete query on a specific table (one table only),
// User needs to specify the restriction, such as 'WHERE", "ORDER BY"...
function delete_from($tablename, $restrict_str, $limit, $con)
{
  $query="DELETE FROM `$tablename`";
  $query.=$restrict_str . " LIMIT " . $limit . ";";
  if (!mysqli_query($con, $query))
  {
    die ("Error in deleting from $tablename " . mysqli_error($con));
  }
}
?>