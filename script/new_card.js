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
        var cardTitleFiltered = filterHTMLTags($("#card_title").val());
        var html = "<h3>" + cardTitleFiltered
            + "</h3><br />";
        
        var cardSubFiltered = filterHTMLTags($("#card_sub").val());
        html += "<h5>" + cardSubFiltered + "</h5><br />";
        $("#card_front_preview").append(html);
    });

    $("#card_form").submit(function() {
        var html = editor.getHTML();
        $("#hidden_input").val(html);
        return; // submit
    });
});