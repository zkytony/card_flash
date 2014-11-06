$(".card-field").change(function() {
    $("#card_front_preview").children().remove();
    var html = "<h3>" + $("#card_name").val() + "</h3><br />";
    html += "<h5>" + $("#card_sub").val() + "</h5><br />";
    $("#card_front_preview").append(html);
});