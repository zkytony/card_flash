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
  init_shares_table($con);
}

function init_users_table($con)
{
  // connected to mysql
  $tablename='users';

  // attention, you must use ` to quote names
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`userid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`username` VARCHAR(128) UNIQUE NOT NULL,"
        ."`password` VARCHAR(128) NOT NULL,"
        ."`register_time` DATE NOT NULL,"
        ."`current_deckid` VARCHAR(32),"
        ."`activate` BOOL NOT NULL,"
        ."`online` BOOL NOT NULL,"
        ."PRIMARY KEY(`userid`),"
        ."INDEX(`username`(10))" 
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
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
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`deckid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`title` VARCHAR(128) NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`create_time` DATETIME NOT NULL,"
        ."`last_edit` DATETIME NOT NULL,"
        ."`deleted` BOOL NOT NULL,"
        ."`open` BOOL NOT NULL,"
        ."INDEX(`title`(10)),"
        ."PRIMARY KEY (`deckid`),"
        ."FOREIGN KEY (`userid`) REFERENCES users(`userid`) "
        ."    ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";


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
  // `type` is the type of the card:
  //     0 - Normal
  //     1 - User Card
  //     2 - Status Card
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`cardid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`title` VARCHAR(128) NOT NULL,"
        ."`sub` TINYTEXT NOT NULL,"
        ."`content` MEDIUMTEXT NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`create_time` DATE NOT NULL,"
        ."`deleted` BOOL NOT NULL,"
        ."`type` INT(1) NOT NULL,"
        ."INDEX(`title`(10)),"
        ."INDEX(`sub`(10)),"
        ."PRIMARY KEY (`cardid`),"
        ."FOREIGN KEY (`deckid`) REFERENCES decks(`deckid`)"
        ."    ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";


  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con));
  }
}

function init_tags_table($con)
{
  $tablename='tags';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`tagid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`tag` VARCHAR(32) NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`deleted` BOOL NOT NULL,"
        ."PRIMARY KEY(`tagid`),"
        ."FOREIGN KEY(`deckid`) REFERENCES decks(`deckid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
  
  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con));
  }
}

// `type` will have these value bindings
// 1: shared as visitor
// 2: shared as editor
function init_shares_table($con)
{
  $tablename='shares';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`shareid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`type` INT(1) NOT NULL,"
        ."PRIMARY KEY(`shareid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE,"
        ."FOREIGN KEY(`deckid`) REFERENCES decks(`deckid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
  
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
  $query="SELECT " . $columns . " FROM `$tablename`";
  $query.=$restrict_str . ";";
  if (!$result=mysqli_query($con, $query)) 
    die ("Error in selecting from $tablename; The query is $query " 
        . mysqli_error($con));

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
    die ("Error in inserting into $tablename " . mysqli_error($con) . " The query was: " . $query);
  }
}

// Perform a update query to the specified table
// Allows updating multiple columns.
// Required input format:
// - $columns is an array containing the columns that you want
// to update, for example: 
//   e.g. array("`col1`", "`col2`", "`col3`"...)
// - $values is an array, containing the value you want to set
// corresponding to each column in the same order
//   e.g. array("'val1'", "'val2'", "'val3'"...)
// Ordering of $values should be according to the ordering
// of $columns
// Problem: if value contains single quote or comma, this breaks!
function update_table($tablename, $columns,
                      $values, $restrict_str, $con)
{
  $set_str=" SET ";
  for ($i=0; $i<sizeof($columns); $i++)
  {
    $set_str.="$columns[$i] = $values[$i]";

    if ($i<sizeof($columns)-1)
    {
      $set_str.=", ";
    } else {
      $set_str.=" ";
    }
  }
  $query="UPDATE `$tablename`";
  $query.=$set_str;
  $query.=$restrict_str . ";";
  if (!mysqli_query($con, $query))
  {
    die ("Error in Update $tablename " . mysqli_error($con) . " YOUR QUERY IS " . $query);
  }  
}

// Perform a Delete query on a specific table (one table only),
// User needs to specify the restriction, such as 'WHERE", "ORDER BY"...
function delete_from($tablename, $restrict_str, $limit, $con)
{
  $query="DELETE FROM `$tablename`";
  $query.=$restrict_str; 
  if (strlen($limit) > 0)
  {
    $query .= " LIMIT " . $limit;
  }
  $query .= ";";
  if (!mysqli_query($con, $query))
  {
    die ("Error in deleting from $tablename " . mysqli_error($con));
  }
}
?>