// Functions of utility use

// This function filters potentially dangerous html
// tags, and replace the matched HTML block with "WARNING"
function filterHTMLTags(htmlStr) {
    var harmTags = ["script", "embed", "link",
                    "listing", "meta", "noscript", 
                    "object", "plaintext", "xmp"];
    
    for (var i = 0; i < harmTags.length; i++) {
        var matchStr = "<" + harmTags[i] + "[^>]*>";

        if (harmTags[i] != "meta" && harmTags[i] != "link")
        { 
            matchStr += ".*?</" + harmTags[i] + ">?";
        }
        var regObj = new RegExp(matchStr, "i");
        htmlStr = htmlStr.replace(regObj, "WARNING");
    }
    return htmlStr;
}

// This function takes in a String, pixel of the <div>,
// base font size from which the actual size is adjusted,
// and the lineLimit is how many lines you want the string to
// occupy; The scale which the font size decreases will
// be 5
function getRightFontSize(str, divPx, basePx, lineLimit) {
    /*
    // see if any word in this str is too long
    // if any word is too long, break the word using "-"
    var words = str.split(/[\s]+/);
    str = ""; // want to refill this string
    for (i in words) {
        if (words[i].length * basePx > divPx) {
            var brokenStr = "";
            var len = 0;
            for (var j = 0; j < words[i].length; j++) {
                len++;
                brokenStr += words[i].charAt(j);
                var margin = 5;
                if (divPx - len * basePx <= margin) {
                    brokenStr += "- ";
                    len = 0;
                } 
            }
            str += brokenStr
        } else {
            str += words[i] + " ";
        }
    }
    */
    var strPx = str.length * basePx;
    var ratio = (strPx / divPx) / lineLimit;
    var fontPx = Math.round(basePx - ratio * 5);
    return fontPx;
}

// This is a helper function for home.php, for flipping the element
// by adding a class 'flip' to the element
// Expects the paramter to be JQuery Object
function flip(jqueryObj) {
    if (!jqueryObj.hasClass('flip')) {
        jqueryObj.addClass('flip');
    } else {
        jqueryObj.removeClass('flip');
    }
}

function quillEditor(editorId, toolbarId) {
    var editor = new Quill('#' + editorId, {
        styles: {
            'body': {
                'font-size':'17px',
                'padding': "7px"
            }
        }
    });
    editor.addModule('toolbar', { container: '#' + toolbarId });
    return editor;
}