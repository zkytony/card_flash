var deckIDTitles = {}; // an object that matches deck IDs with deck titles
var cardDeck = {}; // an object that matches card IDs with deck IDs
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

    $(document).on("click", ".edit-card-button", function() {
        $("#edit-card-area").css('display', 'block');
        $("#overlay_shade").css('display', 'block');
    });

    $(document).on("click", ".delete-card-button", function() {
        var id = $(this).parent().attr("id"); // parent is the button grp
        var cardID = id.split("-")[2]; // id is button-group-cardidxx
        var deckID = cardDeck[cardID];
        
        var sure = confirm("Delete this card?");
        if (sure) {
            $.ajax({
                url: './get_user_info.php',
                data: {action: 'deleteCard',
                       userid: current_userID,
                       cardid: cardID},
                type: 'get',
                success: function(output) {
                    if (output == "success") {
                        alert("Deleted successfully");
                        updateDisplayingDeck(current_userID, deckID);
                        delete cardDeck[cardID]; // delete the key
                    } else {
                        alert("Delete Failed");
                    }
                }
            });
        }
    });

    $(document).on("click", ".delete-deck-button", function() {
        var id = $(this).parent().attr("id"); // parent is the button group
        var deckID = id.split("-")[3]; // id is deck-button-group-deckxx
        
        var sure = confirm("Delete this deck?");
        if (sure) {
            $.ajax({
                url: './get_user_info.php',
                data: {action: 'deleteDeck',
                       userid: current_userID,
                       deckid: deckID},
                type: 'get',
                success: function(output) {
                    if (output == "success") {
                        alert("Deleted successfully");
                        showDeckList(current_userID);

                        // clear the current deck if it is the one being deleted
                        if (deckIDTitles[deckID] == current_deck_global) {
                            updateDisplayingDeck(current_userID, "");
                            current_deck_global = "";
                            $("#current-deck-span").text("");
                        }

                        delete deckIDTitles[deckID]; // delete the key
                        for (cardID in cardDeck) {
                            if (cardDeck[cardID] == deckID) {
                                delete cardDeck[cardID];
                            }
                        }

                    } else {
                        alert("Delete Failed");
                    }
                }
            });
        }
    });
});

// prevents the browser to direct to new_card.php if no current deck is specified
function checkCurrentDeck(event) {
    if (typeof current_deck_global  === 'undefined' || current_deck_global == "") {
        alert("Select a deck first");
        
        if (event.preventDefault) {  // W3C variant
	    event.preventDefault()
	} else { // IE<9 variant:
	    event.returnValue = false
	}
    }
};

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
    // first clear everything in the list
    $("#deck-list-div").children().remove();

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
            htmlString += "<tr><th class='deck-title' id='deck-" + deckID + "'>";
            htmlString += "<a href='#'>" + deckTitle + "</a></th>";
            htmlString += "<td class='deck-button-group' id='deck-button-group-" + deckID + "'>";
            htmlString += "<button class='deck-tiny-button delete-deck-button'>D</button>"; 
            htmlString += "</td></tr>";

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
            deckIDTitles[deckID] = deckTitle;
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
            $("#current-deck-span").html(deckIDTitles[output]);
            current_deck_global = deckIDTitles[output];

            // execute these lines when current deck is fetched
            var deckID = getDeckIDFromTitle(current_deck_global);
            updateDisplayingDeck(current_userID, deckID);
        }
    });
}

// updates which deck's card is showing
// display the cards
function updateDisplayingDeck(userid, deckid) {
    $.ajax({
        url: './get_user_info.php',
        data: {action: 'updateDisplay',
               userid: userid,
               deckid: deckid},
        type: 'get',
        success: function(output) {
            displayCards(output, deckid);
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
function displayCards(json_str, deckID) {
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
            html += "<div class='card-button-group' id='button-group-" + cardID + "'>";
            html += "<button class='card-tiny-button flip-button' title='Flip'>F</botton>";
            html += "<button class='card-tiny-button edit-card-button' title='Edit'>E</button>";
            html += "<button class='card-tiny-button delete-card-button' title='Delete'>D</button>";
            html += "</div></div>";

            cardDeck[cardID] = deckID; // fill in this JS object

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

// This function has potential problem. Needs to be replaced
function getDeckIDFromTitle(deckTitle) {
    for (var id in deckIDTitles) {
        if (deckIDTitles[id] === deckTitle) {
            return id;
        }
    }
    return null;
}

// turns the pop up divs into display:none when clicked 'close'
function closeCardEdit() {
    $("#edit-card-area").css('display', 'none');
    $("#overlay_shade").css('display', 'none');
}