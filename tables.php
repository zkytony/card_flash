<?php
function init_tables($con)
{
  init_users_table($con);
  init_decks_table($con);
  init_cards_table($con);
  init_tags_table($con);
  init_folders_table($con);
  init_shares_table($con);
  init_followers_table($con);
  init_subscribers_table($con);
  init_circles_table($con);
  init_members_table($con);
  init_comments_table($con);
  init_timeline_table($con);
  init_activity_tables($con);
}

function init_users_table($con)
{
  // connected to mysql
  $tablename='users';

  // attention, you must use ` to quote names
  // basically, email is the username -- unique required
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`userid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`email` VARCHAR(128) UNIQUE NOT NULL,"
        ."`first` VARCHAR(128) NOT NULL,"
        ."`last` VARCHAR(128) NOT NULL,"
        ."`password` VARCHAR(128) NOT NULL,"
        ."`birth` DATE NOT NULL,"
        ."`register_time` DATE NOT NULL,"
        ."`current_deckid` VARCHAR(32),"
        ."`activate` BOOL NOT NULL,"
        ."`online` BOOL NOT NULL,"
        ."`followers` INT(16) NOT NULL,"
        ."`following` INT(16) NOT NULL,"
        ."`subscribing` INT(16) NOT NULL,"
        ."PRIMARY KEY(`userid`)"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
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
        ."`last_edit` DATETIME NOT NULL," // Is this necessary?
        ."`deleted` BOOL NOT NULL,"
        ."`open` BOOL NOT NULL,"
        ."`subscribers` INT(16) NOT NULL,"
	."`folderid` VARCHAR(32),"
	."`like` INT(10) NOT NULL DEFAULT '0',"
	."`flips` INT(10) NOT NULL DEFAULT '0',"
	."`views` INT(10) NOT NULL DEFAULT '0',"
        ."INDEX(`title`(10)),"
        ."PRIMARY KEY (`deckid`),"
        ."FOREIGN KEY (`userid`) REFERENCES users(`userid`) "
        ."    ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";


  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
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
	."`like` INT(10) NOT NULL DEFAULT '0',"
        ."INDEX(`title`(10)),"
        ."INDEX(`sub`(10)),"
        ."PRIMARY KEY (`cardid`),"
        ."FOREIGN KEY (`deckid`) REFERENCES decks(`deckid`)"
        ."    ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";


  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
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
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// folders table: to categorize decks.
// Yet we will only need 2 levels - We don't need multiple depth of categories.
// This is different from tags. This is used so that when users are browsing
// their deck list, or creating new decks, things can be clearer for them.
function init_folders_table($con)
{
  $tablename='folders';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
	."`folderid` VARCHAR(32) UNIQUE NOT NULL,"
	."`name` VARCHAR(128) NOT NULL,"
	."`userid` VARCHAR(32) NOT NULL,"
	."PRIMARY KEY(`folderid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}



// `type` will have these value bindings
// 1: shared as visitor
// 2: shared as editor
// `userid` is the target user
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
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

function init_followers_table($con) {
  $tablename='followers';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`flwrid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`flwr_userid` VARCHAR(32) NOT NULL,"
        ."PRIMARY KEY(`flwrid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE,"
        ."FOREIGN KEY(`flwr_userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

function init_subscribers_table($con) {
  $tablename='subscribers';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`sbrid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`sbr_userid` VARCHAR(32) NOT NULL,"
        ."PRIMARY KEY(`sbrid`),"
        ."FOREIGN KEY(`deckid`) REFERENCES decks(`deckid`)"
        ."   ON DELETE CASCADE,"
        ."FOREIGN KEY(`sbr_userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// circles is just like friends
// circle is like group
function init_circles_table($con) {
  $tablename='circles';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`circleid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL," // userid for the creator
        ."`title` VARCHAR(32) NOT NULL,"
        ."`forwhat` VARCHAR(128) NOT NULL,"
        ."`create_time` DATETIME NOT NULL,"
        ."`member_count` INT(4) NOT NULL,"
        ."`admin_count` INT(1) NOT NULL,"
        ."PRIMARY KEY(`circleid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// represents the members for each circle
// `role` refers to the role of the user in the circle:
// 0 - admin --> the ability to kick people out, and assign other people as admin
// 1 - normal --> normal abilities: leave, invite others, see updates
function init_members_table($con) {
  $tablename='members';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`memberid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`circleid` VARCHAR(32) NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL," // userid for the member
        ."`role` INT(1) NOT NULL," // role of the user
        ."`join_time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`memberid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE,"
        ."FOREIGN KEY(`circleid`) REFERENCES circles(`circleid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// Stores information about comments
// userid - the user who made this comment
// reply_commentid - the comment id of the comment that this comment is replying. If this
//     is a new comment, the value is NULL
// type: the type of comment this is for. Currently we have:
// 0 - commenting on a card
// 1 - commenting on a deck
// targetid - the id of that target that this comment pointing to. For example,
//     if this comment is for card, then targetid is a cardid.
function init_comments_table($con) {
  $tablename='comments';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`commentid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`reply_commentid` VARCHAR(32) NOT NULL,"
        ."`type` INT(1) NOT NULL,"
        ."`targetid` VARCHAR(32) NOT NULL,"
	."`content` TEXT NOT NULL,"
        ."`time` DATETIME NOT NULL,"
	."`like` INT(10) NOT NULL DEFAULT '0',"
        ."PRIMARY KEY(`commentid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// refid is the id for reference in other activity tables
// reftable is the tablename of the specific activity table
// Activity types:
// 0 - user register
// 1 - user creates a deck
// 2 - card(s) added to a deck
// 3 - tag(s) of a deck is changed
// 4 - a deck is shared to other users
// 5 - a user subscribes to a deck
// 6 - a user joins a group
// 7 - a user follows another user
// 8 - a deck's information is edited (title, description, open, close)
// 9 - a card's information is edited (title, subtitle, content)
// 10 - a user comments on someting
// 11 - a user likes something
function init_timeline_table($con) {
  $tablename='timeline';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`timeid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`refid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`reftable` VARCHAR(32) NOT NULL,"
        ."`type` INT(2) NOT NULL,"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`timeid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

function init_activity_tables($con) {
  init_activity_user_register_table($con);
  init_activity_deck_new_del_table($con);
  init_activity_card_new_del_table($con);
  init_activity_tags_changed_table($con);
  init_activity_deck_share_table($con);
  init_activity_deck_subscribe_table($con);
  init_activity_group_join_table($con);
  init_activity_user_follow_table($con);
  init_activity_deck_updated_table($con);
  init_activity_card_updated_table($con);
  init_activity_user_comments_table($con);
  init_activity_user_likes_table($con);
  init_activity_user_view_deck_table($con);
}

function init_activity_user_register_table($con) {
  $tablename='activity_user_register';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// circleid here is not null if the deck is created for a circle with that id
// Stores the activity for creating a new deck OR deleting a deck
function init_activity_deck_new_del_table($con) {
  $tablename='activity_deck_new_del';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`new` BOOL NOT NULL,"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// circleid here is not null if the card is added for a deck in a circle with that id
// Stores the activity for creating a card OR deleting a card
// If want to have the functionality like 'NUM of cards are added to deck XX', a
// better way to do is by filtering the time within a range and count the number
function init_activity_card_new_del_table($con) {
  $tablename='activity_card_new_del';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`cardid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`new` BOOL NOT NULL,"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// circleid here is not null if the tags are changed for a deck in a circle with that id
// NOTICE: when a user untag a tag, this activity is not very valuable. 
// The valuable activity is when he add some new tags
// added_tags : tags that are added now (they now exist) - Tags should be separated by comma
function init_activity_tags_changed_table($con) {
  $tablename='activity_tags_changed';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"    
        ."`added_tags` VARCHAR(128),"
        ."`circleid` VARCHAR(32),"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// Stores deck share and unshare activity
// `sharing` is true if the deck is shared,
// false if it is unshared
function init_activity_deck_share_table($con) {
  $tablename='activity_deck_share';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`from_userid` VARCHAR(32) NOT NULL,"
        ."`to_userid` VARCHAR(32) NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`sharing` BOOL NOT NULL,"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`from_userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE,"
        ."FOREIGN KEY(`to_userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// Stores the activity of subscribing or unsubscribing a deck
// `subscribing` is true if user subscribes a deck,
// false otherwise
function init_activity_deck_subscribe_table($con) {
  $tablename='activity_deck_subscribe';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`subscribing` BOOL NOT NULL,"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// `init` is true if the user creates the group
function init_activity_group_join_table($con) {
  $tablename='activity_group_join';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`init` BOOL NOT NULL,"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// `following` is true if `userid` follows `targetid`
// false if `userid` is followed by `targetid`
function init_activity_user_follow_table($con) {
  $tablename='activity_user_follow';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`targetid` VARCHAR(32),"
        ."`following` BOOL NOT NULL,"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// Stores the activity that a deck is updated.
// This DOES NOT include when user adds a card.
// This also DOES NOT record what is specificly updated. It
// just records that the deck's information (title, description...) is updated
function init_activity_deck_updated_table($con) {
  $tablename='activity_deck_updated';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`deckid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// Stores the activity that a card is updated.
// This DOES NOT include when user adds a card.
// This also DOES NOT record what is specificly updated. It
// just records that the card's information (title, description...) is updated
function init_activity_card_updated_table($con) {
  $tablename='activity_card_updated';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`cardid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// Stores the activity that a user makes a comment on something
// type: the type of comment this is for. Currently we have:
// 0 - commenting on a card
// 1 - commenting on a deck
// targetid - the id of that target that this comment pointing to. For example,
//     if this comment is for card, then targetid is a cardid.
function init_activity_user_comments_table($con) {
  $tablename='activity_user_comments';
  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
        ."`commentid` VARCHAR(32) NOT NULL,"
	."`type` INT(1) NOT NULL,"
	."`targetid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE,"
        ."FOREIGN KEY(`commentid`) REFERENCES comments(`commentid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// Stores activity that a user likes something
// type:
// 0 - likes a card
// 1 - likes a deck
// 2 - likes a comment
// targetid: the id of the thing that is liked. 
// For example, if the type is '0', then the targetid should be a cardid
function init_activity_user_likes_table($con) {
  $tablename='activity_user_likes';

  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
	."`type` INT(1) NOT NULL,"
	."`targetid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}

// The activity that a user views a deck.
// `deckid` is the deck that a user viewed
// `cardid` is the card that the user was looking at when
// he was viewing the deck
//    (It is always the most current one)
// Everytime a user looks at a different card, the card column
// and time will update
function init_activity_user_view_deck_table($con) {
  $tablename='activity_user_view_deck';

  $query="CREATE TABLE IF NOT EXISTS `$tablename` ("
        ."`actid` VARCHAR(32) UNIQUE NOT NULL,"
        ."`userid` VARCHAR(32) NOT NULL,"
	."`deckid` VARCHAR(32) NOT NULL,"
	."`cardid` VARCHAR(32) NOT NULL,"
        ."`circleid` VARCHAR(32),"
        ."`time` DATETIME NOT NULL,"
        ."PRIMARY KEY(`actid`),"
        ."FOREIGN KEY(`userid`) REFERENCES users(`userid`)"
        ."   ON DELETE CASCADE"
        .") ENGINE InnoDB"
        ."  CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

  if (!mysqli_query($con, $query))
  {
    die ("Unable to create table $tablename " . mysqli_error($con) . " The query was: " . $query . "\n");
  }
}
?>
