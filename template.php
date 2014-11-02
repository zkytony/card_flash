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
    </span>
  </div>
<?php
}
?>