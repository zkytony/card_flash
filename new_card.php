<?php
session_start();
if (!$_SESSION['loggedIn'])
{
    header("location:index.php");
}
require_once "template.php";
require_once "quill.php";
?>
<html>
  <head>
    <title>New Card-<?php echo $_SESSION['username'] ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="./css/home.css">
    <link rel="stylesheet" type="text/css" href="./css/card.css">
    <link rel="stylesheet" type="text/css" href="./css/m_quill.css">
    <script src="http://code.jquery.com/jquery-2.1.0.min.js"></script>
  </head>
  <body>
    <?php 
    top_bar();
    card_form();
    preview_card();
    ?>
    <script src="./script/new_card.js"></script>
  </body>
</html>
<?php 
function card_form()
{
?>
  <div class="card-form">
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
      <?php 
      card_front();
      card_back();
      ?>
      <input type="submit" value="Done" id="submit" name="card_submit" class="submit-card"/>
    </form>
  </div>
<?php
}

function card_front()
{
?>
  <div class="card-frame" id="card_front_edit">
   Title: <input class="card-field" id="card_name" type="text" name="card_name"  />
    Subdescription: <input class="card-field card-sub" id="card_sub" type="text" name="card_sub" />
  </div>
<?php
}

function card_back()
{
?>
  <div class="card-frame" id="card_back_edit">
    <?php 
    build_editor(); // from quill.php
    ?>
  </div>
<?php
}

function preview_card()
{
?>
  <div class="preview-card">
    <h2>Preview</h2>
    <div class="card-frame" id="card_front_preview">
      <h4>Front</h4>
    </div>
    <div class="card-frame" id="card_back_preview">
      <h4>Back</h4>
    </div>
  </div>
<?php
}
?>

