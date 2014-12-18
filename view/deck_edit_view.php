<?php
function deck_form()
{
?>
  <div class="deck-form-div">
    <form name="deck_form" action="<?php echo $_SERVER['PHP_SELF'];?>"  method="post">
      Title:<input class="deck-field" type="text" name="title" id="title"/>
      Tags:<input class="deck-field" type="text" name="category" id="category"/>
      <input type="submit" name="submit-deck" id="submit-deck" value="Submit" />
    </form>
  </div>
<?php
}
?>