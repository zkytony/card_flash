// when click the decks, the current deck should change
$(".deck-item").click(function() {
    var deckTitle = $(this).text();
    $("#current_deck").html(deckTitle);
});

function showDeckList(userid) {
    $.ajax({url: '/var/www/html/flashcard/get_user_info.php',
            data: {action: 'decklist',
                   userid: userid},
            type: 'post',
            success: displayDeckList(output), // output JSON
            dataType: "json"
           });
}

function displayDeckList(json_str) {
    
}
