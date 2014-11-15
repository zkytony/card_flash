var deckArr = {};
var current_deck_global = ""; // for comparing
var current_userID = "";

// when click the decks, the current deck should change
$(document).ready(function() {
    $(".deck-title").click(function() {
        var deckTitle = $(this).text();
        $("#current-deck-span").html(deckTitle);
        if (deckTitle !== current_deck_global) {
            var deckID = getDeckIDFromTitle(deckTitle);
            alert("BEFORE");
            updateDisplayingDeck(current_userID, deckID);
            alert("AFTER");
        }
    });
});

function showDeckList(userid) {
    current_userID = userid;
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
    // the JSON string is parsed into a JavaScript object
    // To access the Properties (fields) of a JS object,
    // simply do this for..in loop
    for (var deckID in deckData) {
        // deckID is the key to get the object referenced
        // by it
        deckIDObj = deckData[deckID];
        for (var deckTitle in deckIDObj) {
            htmlString += "<tr><th class='deck-title'>";
            htmlString += "<a href='#'>" + deckTitle + "</a>";
            htmlString += "</th></tr>";

            // accessing the tags array
            tagsArr = deckIDObj[deckTitle];

            htmlString += "<tr class='tags'>";
            for (var i = 0; i < tagsArr.length; i++) {
                htmlString += "<td class='one-tag'>";
                htmlString += tagsArr[i];
                htmlString += "</td>";
            }
            htmlString += "</tr>";
            
            // add to the global array
            deckArr[deckID] = deckTitle;
        }
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
            $("#current-deck-span").html(deckArr[output]);
            current_deck_global = deckArr[output];
        }
    });
}

// updates which deck's card is showing
// updates user's current deck
function updateDisplayingDeck(userid, deckid) {
    $.ajax({
        url: './get_user_info.php',
        data: {action: 'updateDisplay',
               userid: userid,
               deckid: deckid},
        type: 'get',
        success: function(output) {
            alert(output);
        }
    });
}

function getDeckIDFromTitle(deckTitle) {
    for (id in deckArr) {
        if (deckArr[id] === deckTitle) {
            return id;
        }
    }
    return null;
}

