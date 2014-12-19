<?php
// this function only builds the HTML elements for displaying a quill RTE
// For the functionalities, you need to use separate script and include
// them in your page
function build_editor()
{
?>
  <!-- Create the toolbar container -->
  <div id="toolbar">
    <span class="ql-format-group">
      <!-- Add font size dropdown -->
      <select class="ql-size">
        <option value="14px">Small</option>
        <option value="18px" selected>Normal</option>
        <option value="32px">Large</option>
      </select>
    </span>
    <span class="ql-format-group">
      <!-- Add a bold button -->
      <button type="button" class="ql-bold"><b>B</b></button>
      <!-- Add italic button -->
      <button type="button" class="ql-italic"><i>I</i></button>
      <button type="button" class="ql-underline"><u>U</u></button>
    </span>
  </div>
  <!-- Create the editor container -->
  <div id="editor">
    <div>Hi!</div>
    <div><br></div>
  </div>

  <script src="http://cdn.quilljs.com/0.18.1/quill.js"></script>
<?php
}
?>