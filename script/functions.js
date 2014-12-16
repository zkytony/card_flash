// Functions of utility use

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

function getRightFontSize() {
    
}