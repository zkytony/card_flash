$(".card-field").change(function() {
    $("#card_front_preview").children().remove();
    var html = "<h3>" + $("#card_title").val() + "</h3><br />";
    html += "<h5>" + $("#card_sub").val() + "</h5><br />";
    $("#card_front_preview").append(html);
});

$("#card_form").submit(function() {
    var content = $("#card_back_preview").children();
    $("#hidden_input").append(content);
    return; // submit
});