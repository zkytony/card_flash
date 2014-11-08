// when click the decks, the current deck should change
$(".deck-item").click(function() {
    var deckTitle = $(this).text();
    $("#current_deck").html(deckTitle);
});