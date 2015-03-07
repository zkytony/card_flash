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
        . mysqli_error($con) . "\n");

  return $result;
}

// Perform a INSERT query to the specified table
// $columns should be a string of columns in this format:
// "`col1`,`col2`..."
// $values is the string that stores each value corresponding
// to each column (the ordering should be the same as in $columns)
// "'val1','val2'..."
function insert_into($tablename, $columns, $values, $con)
{
  $query="INSERT INTO `$tablename` (" . $columns . ")";
  $query.="VALUES (" . $values . ");";
  if (!mysqli_query($con, $query))
  {
    die ("Error in inserting into $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
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
    die ("Error in Update $tablename " . mysqli_error($con) . " YOUR QUERY IS " . $query . "\n");
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
    die ("Error in Update $tablename " . mysqli_error($con) . " YOUR QUERY IS " . $query . "\n");
  }
}

// It is always uncomfortable to type all the STR_TO_DATE string and
// pass it in select_from(); Thus, this function takes in a time string,
// and a format string, and returns the string 'STR_TO_DATE(xx,xx)' that
// is good for using in select_from()
function str_to_date($datetime, $format) {
  return "STR_TO_DATE(\"{$datetime}\", \"{$format}\")";
}
?>
