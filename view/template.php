<?php
// mainly functions for html
function top_bar()
{
?>
  <div class="top-bar">
    <span class="info-span">
      <h3>Hi! <?php echo $_SESSION['username'] ?></h3>
      <form action="logout.php" method="post">
        <input type="submit" value="logout" id="logout-button">
      </form>
      <a href="home.php">Home</a>
    </span>
  </div>
<?php
}

function include_jquery()
{
?>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<?php 
}

function include_important_scripts()
{
?>
  <script src="script/functions.js"></script>
<?php
}

function include_fonts()
{
?>
  <link href='http://fonts.googleapis.com/css?family=Vollkorn:400,400italic,700,700italic' rel='stylesheet' type='text/css'>
<?php
}
?>