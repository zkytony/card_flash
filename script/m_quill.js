var editor = new Quill('#editor', {
  styles: {
    'body': {
      'font-size': "17px",
      'padding': "7px"
    }
  }
});
editor.addModule('toolbar', { container: '#toolbar' });

// change the prevew dynamically
editor.on('text-change', function(delta, source) {
  $("#card_back_preview").children().remove();
  $("#card_back_preview").append(editor.getHTML());
});