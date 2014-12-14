<?php
// functions for the view when editing a card
// need separate style file to customize style

function card_form()
{
?>
  <div class="card-div">
    <form name="card_form" id="card_form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
      <?php 
      card_front();
      card_back();
      ?>
      <textarea class="hidden" id="hidden_input" name="card_content" style="display:none"></textarea>
      <input type="text" class="hidden" id="hidden_cardid" name="card_id" value="" style="display:none">
      <input type="submit" value="Done" id="submit" name="submit-card" class="submit-card"/>
    </form>
  </div>
<?php
}

function card_front()
{
?>
  <div class="card-frame" id="card_front_edit">
    Title: <input class="card-field" id="card_title" type="text" name="card_title"  />
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