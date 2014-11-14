// when click the decks, the current deck should change
$(document).ready(function() {
    $(".deck-item").click(function() {
        var deckTitle = $(this).text();
        $("#current_deck").html(deckTitle);
    });
});

function showDeckList(userid) {
    $.ajax({
        url: './get_user_info.php',
        data: {action: 'deckList',
               userid: userid},
        type: 'get',
        success: function(output) {
            displayDeckList(output);
        }, // output should be a JSON format string
    });
}

function displayDeckList(json_str) {
    var listDiv = document.getElementById("deck-list-div");
    alert(json_str);
    json_data = JSON.parse(json_str);
    alert(json_data.count);
}

function currentDeck(userid) {
    $.ajax({
        url: './get_user_info.php',
        data: {action: 'currentDeck',
               userid: userid},
        type: 'get',
        success: function(output) {
            $("#current-deck-span").text = output;
        }
    });
}