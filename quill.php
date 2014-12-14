<?php
// this function only builds the HTML elements for displaying a quill RTE
// For the functionalities, you need to use separate script and include
// them in your page
function build_editor()
{
?>
  <!-- Create the toolbar container -->
  <div id="toolbar">
    <!-- Add font size dropdown -->
    <select class="ql-size">
      <option value="small">Small</option>
      <option value="normal" selected>Normal</option>
      <option value="large">Large</option>
      <option value="huge">Huge</option>
    </select>
    <!-- Add a bold button -->
    <button type="button" class="ql-bold">Bold</button>
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