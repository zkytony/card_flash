var deckArr = {}; // an object that matches deck IDs with deck titles
var current_deck_global = ""; // for comparing
var current_userID = "";

// when click the decks, the current deck should change
$(document).ready(function() {

    // for dynamically generated divs, you should handle event in this way
    $(document).on("click", ".deck-title", function() {
        var deckTitle = $(this).text();
        $("#current-deck-span").html(deckTitle);
        if (deckTitle !== current_deck_global) {
            var deckID = getDeckIDFromTitle(deckTitle);
            updateDisplayingDeck(current_userID, deckID);
        }
        current_deck_global = $(this).text();
    });

    $(document).on("click", ".flip-button", function() {
        
    });

    $(document).on("click", ".zoom-button", function() {
        
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

            // execute these lines when current deck is fetched
            var deckID = getDeckIDFromTitle(current_deck_global);
            updateDisplayingDeck(current_userID, deckID);
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
            displayCards(output);
        }
    });
}

// expect JSON string in this fashion:
// { cardid: {
//         title,
//         sub,
//         content,
//   }
// }
function displayCards(json_str) {
    // first, clean whatever is there already
    $("#card-display-div").children().remove();    

    var cardData = JSON.parse(json_str);
    if (jQuery.isEmptyObject(cardData)) {
        var html = "<h3>There is no Card! Select a deck if currently not, and create a card!</h3>";
        $("#card-display-div").append(html);
    } else {
        for (var cardID in cardData) {
            var oneCard = cardData[cardID];
            var title = filterHTMLTags(oneCard["title"]);
            var sub = filterHTMLTags(oneCard["sub"]);
            var content = filterHTMLTags(oneCard["content"]);
            
            var wordsInTitle = title.split("[\s,.]+");
            var wordsInSub = sub.split("[\s,.]+");

            var html = "<div id='" + cardID + "' class='card-tiny'>";
            html += "<h4>" + title + "</h4>";
            html += "<p><i>" + sub + "</i></p>";
            html += "<div class='card-button-group'>";
            html += "<button class='card-tiny-button flip-button' title='Flip'>F</botton>";
            html += "<button class='card-tiny-button zoom-button' title='Zoom'>Z</button>";
            html += "<button class='card-tiny-button zoom-button' title='Edit'>E</button>";
            html += "<button class='card-tiny-button zoom-button' title='Delete'>D</button>";
            html += "</div></div>";


            // adjust the font size of the title
            $("#card-display-div").append(html);
            var pixTitle = getSizeValue($("#"+cardID+" h4").css('font-size')) * title.length;
            var pixDiv = $(".card-tiny").width();
            var ratio = (pixTitle / pixDiv) / 3; // dont get more than three lines of space
            var pixFont = Math.round(25 - ratio * 5);
            $("#"+cardID+" h4").css("font-size", pixFont + "px");
        }
    }
}

function getSizeValue (size) {
    return parseInt(size.substring(0, size.length-2));
}

function getDeckIDFromTitle(deckTitle) {
    for (var id in deckArr) {
        if (deckArr[id] === deckTitle) {
            return id;
        }
    }
    return null;
}