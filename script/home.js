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
    deckData = JSON.parse(json_str);
    var htmlString = "<table>";
    // dealing with the JavaScript object:
    for (var deckTitle in deckData) {
        htmlString += "<tr><th class='deck-title'>";
        htmlString += "<a href='#'>" + deckTitle + "</a>";
        htmlString += "</th></tr>";
        tagsArr = deckData[deckTitle];
        htmlString += "<tr class='tags'>";
        for (var i = 0; i < tagsArr.length; i++) {
            htmlString += "<td class='one-tag'>";
            htmlString += tagsArr[i];
            htmlString += "</td>";
        }
        htmlString += "</tr>";
    }
    htmlString += "</table>";
    $("#deck-list-div").append(htmlString);
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