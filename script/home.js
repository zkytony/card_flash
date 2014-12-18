var deckIDTitles = {}; // an object that matches deck IDs with deck titles
var cardDeck = {}; // an object that matches card IDs with deck IDs
var cardInfo = {}; // an object that matches card IDs with its info (title, sub, content)
var cardIDCurrent = new Array(); // an array that contains only the cardid for the current deck -- convenient for flipping
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
        popUp("flip-card-area-wrapper");

        var id = $(this).parent().parent().attr("id"); // the id for each displayed card, in format i-cardXX
        var i = id.split("-")[0];
        var cardID = id.split("-")[1];
        
        createCardFlipDiv(i, cardID, "flip-card-area");
    });

    $(document).on("click", ".edit-card-button", function() {
        popUp("edit-card-area");

        var id = $(this).parent().attr("id"); // parent is the button grp
        var cardID = id.split("-")[2]; // id is button-group-cardidxx
        var deckID = cardDeck[cardID];

        if (typeof(editor) != "undefined") {
            $('#card_title').attr('value', cardInfo[cardID]['cardTitle']);
            $('#card_sub').attr('value', cardInfo[cardID]['cardSub']);
            $('#hidden_cardid').attr('value', cardID);
            editor.setHTML(cardInfo[cardID]['cardContent']);
        }
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
    }); // end of document click listener

    $(document).on("click", ".flipper", function() {
        if (!$(this).hasClass('flip')) {
            $(this).addClass('flip');
        } else {
            $(this).removeClass('flip');
        }
    });

    $("#card_form").submit(function() {
        var html = editor.getHTML();
        $("#hidden_input").val(html);
        return; // submit
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

// fetch the list of decks the current user has from database
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

// display the deck list based on the server's response as json
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

// fetch the current_deck data from database
// updates the display of cards of the current deck
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
    cardIDCurrent = new Array();
    var cardData = JSON.parse(json_str);
    if (jQuery.isEmptyObject(cardData)) {
        var html = "<h3>There is no Card! Select a deck if currently not, and create a card!</h3>";
        $("#card-display-div").append(html);
    } else {
        var i = 0;
        for (var cardID in cardData) {
            var oneCard = cardData[cardID];
            var title = filterHTMLTags(oneCard["title"]);
            var sub = filterHTMLTags(oneCard["sub"]);
            var content = filterHTMLTags(oneCard["content"]);
            
            var wordsInTitle = title.split("[\s,.]+");
            var wordsInSub = sub.split("[\s,.]+");

            var id = i + "-" + cardID;
            var html = "<div id='" + id + "' class='card-tiny'>";
            html += "<h4>" + title + "</h4>";
            html += "<p><i>" + sub + "</i></p>";
            html += "<div class='card-button-group' id='button-group-" + cardID + "'>";
            html += "<button class='card-tiny-button flip-button' title='Flip'>F</botton>";
            html += "<button class='card-tiny-button edit-card-button' title='Edit'>E</button>";
            html += "<button class='card-tiny-button delete-card-button' title='Delete'>D</button>";
            html += "</div></div>";

            cardDeck[cardID] = deckID; // fill in this JS object
            cardInfo[cardID] = {
                cardTitle : title,
                cardSub : sub,
                cardContent : content
            }; // fill in this JS object
            cardIDCurrent.push(cardID);
            $("#card-display-div").append(html);

            // adjust the font size of the title
            var titleFontSize = getRightFontSize(title, $(".card-tiny").width(), 25, 3);
            $("#"+id+" h4").css("font-size", titleFontSize + "px");

            i++; // increment the index
        } // for loop ends
    }
}

// assuming size is in px
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

// set up the quill editor -- for edit card
var editor;
$('#edit-card-area').ready(function() {
    editor = new Quill('#editor', {
        styles: {
            'body': {
                'font-size': "17px",
                'padding': "7px"
            }
        }
    });
    editor.addModule('toolbar', { container: '#toolbar' });
});

// turns the pop up divs into display:none when clicked 'close'
function closePopUp() {
    $(".pop-up-div").css('display', 'none');
    $("#overlay_shade").css('display', 'none');
}

// assume the element has class 'pop-up-div'
function popUp(divId) {
    $("#" + divId).css('display', 'block');
    $("#overlay_shade").css('display', 'block');
}

// appends the elements necessary for the div of flipping cards
// based on the card index and cardID
function createCardFlipDiv(i, cardID, parentID) {
    $("#" + parentID).children().remove();

    var id = i + "-" + cardID;
    i = parseInt(i);
    var first = i-1 < 0;
    var last = i+1 >= cardIDCurrent.length;
    var html = "";

    // add the card divs
    if (!first) {
        html += cardFrontBackHTML(i-1, cardIDCurrent[i-1], false);
    }
    html += cardFrontBackHTML(i, cardID, true);
    if (!last) {
        html += cardFrontBackHTML(i+1, cardIDCurrent[i+1], false);
    }
    // add the Prev Next buttons
    html += handlePrevNext(i, cardID, parentID);

    $("#" + parentID).append(html);
}

// returns the html string for one card's front and back for the flip div
function cardFrontBackHTML(i, cardID, display) {
    var id = i + "-" + cardID;
    var html = "<div class='card-zoom flipper' id='zoom-" + id + "'";
    if (!display) {
        html += "style='display:none';";
    }
    html += ">";
    html += "<div class='card-front-zoom flipper-front' id='zoom-front-" + id + "'>";
    html += "<span class='zoom-card-title'>" + cardInfo[cardID]['cardTitle'] + "</span>";
    html += "<p>" + cardInfo[cardID]['cardSub'] + "</p></div>";
    html += "<div class='card-back-zoom flipper-back' id='zoom-back-" + id + "'>";
    html += cardInfo[cardID]['cardContent'] + "</div></div>";
    return html;
}

// returns the html string that displays 'Prev' and 'Next' properly
function handlePrevNext(i, cardID, parentID) {
    var first = i-1 < 0;
    var last = i+1 >= cardIDCurrent.length;
    var html = "";

    // Add the 'Prev' and 'Next' button
    if (!first) { // add the Prev button
        html += '<a href="javascript:void(0)"';
        var prevID = 'prev-' + (i-1) + '-' + cardID;
        html += 'class="change-card-link" id="' + prevID + '" ';
        html += 'onclick="browseFromTo(' + i + ', ' + (i-1) + ', \'' + parentID + '\', true)"';
        html += '>Prev</a>';
    }
    if (!last) { // add the Next button
        html += '<a href="javascript:void(0)"';
        var nextID = 'next-' + (i+1) + '-'+ cardID;
        html += 'class="change-card-link" id="' + nextID + '" ';
        html += 'onclick="browseFromTo(' + i + ', ' + (i+1) + ', \'' + parentID + '\', false)"';
        html += '>Next</a>';
    }
    return html;
}

// go to the previous or next card in the right way -- for animation purposes; This function assumes that 'from' and 'to' are two valid indices
function browseFromTo(fromIndex, toIndex, parentID, previous) {
    var curId = "zoom-" + fromIndex + "-" + cardIDCurrent[fromIndex];
    var toId = "zoom-" + toIndex + "-" + cardIDCurrent[toIndex];
    $("#" + toId).css('display', 'block'); // make that card visible
    if (previous) {
        $("#" + toId).transition({
            x: 40, 
            y: 40,
            duration: 100,
            complete: function() {
                $("#" + curId).css('display', 'none');
                $("#" + toId).transition({
                    x: 0, 
                    y: 0,
                    duration: 70,
                    complete: function() {
                        cardTransitionComplete(fromIndex, toIndex, parentID, true);
                    }
                });
            }
        });
    } else {
        $("#" + toId).transition({
            x: -40, 
            duration: 100,
            complete: function() {
                $("#" + curId).css('display', 'none');
                $("#" + toId).transition({
                    x: 0,
                    duration: 70,
                    complete: function() {
                        cardTransitionComplete(fromIndex, toIndex, parentID, false);
                    }
                });
            }
        });
    }
}

// called when the animation of changing the card completes
// appropriately handle the DOM after the change
function cardTransitionComplete(fromIndex, toIndex, parentID, previous) {
    var curId = "zoom-" + fromIndex + "-" + cardIDCurrent[fromIndex];
    var toId = "zoom-" + toIndex + "-" + cardIDCurrent[toIndex];
    // because we have three card divs at any time, there is also the other div's Id
    var theOtherId = "zoom-";
    // need to know if card from which we transit is the first, last or neither
    var first = fromIndex-1 < 0;
    var last = fromIndex+1 >= cardIDCurrent.length;

    if (previous && !last) {
        theOtherId += (fromIndex + 1) + "-" + cardIDCurrent[fromIndex + 1];
    } else if (!previous && !first) {
        theOtherId += (fromIndex - 1) + "-" + cardIDCurrent[fromIndex - 1];
    }

    // remove the other div:
    $("#" + theOtherId).remove();
    $(".change-card-link").remove(); // remove the Prev and Next buttons
    
    var html = "";
    // build another card div in replacement of the one we deleted
    if (previous && ((fromIndex-2) >= 0)) {
        html += cardFrontBackHTML(fromIndex-2, cardIDCurrent[fromIndex-2], false);
    } else if (!previous && ((fromIndex+2) < cardIDCurrent.length)) {
        html += cardFrontBackHTML(fromIndex+2, cardIDCurrent[fromIndex+2], false);
    }

    // change the Prev and Next button accordingly
    if (previous) {
        html += handlePrevNext(fromIndex-1, cardIDCurrent[fromIndex-2], parentID);
    } else {
        html += handlePrevNext(fromIndex+1, cardIDCurrent[fromIndex+2], parentID);
    }
    $("#" + parentID).append(html);
}