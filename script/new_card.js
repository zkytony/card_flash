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

$(document).ready(function() {
    $(".card-field").change(function() {
        $("#card_front_preview").children().remove();
        var html = "<h3>" + $("#card_title").val() 
            + "</h3><br />";
        html += "<h5>" + $("#card_sub").val() + "</h5><br />";
        $("#card_front_preview").append(html);
    });

    $("#card_form").submit(function() {
        var html = editor.getHTML();
        $("#hidden_input").val(html);
        return; // submit
    });
});