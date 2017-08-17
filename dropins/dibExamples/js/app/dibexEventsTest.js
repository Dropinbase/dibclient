(function () {
dib.defineAction('Dropins.dibExamples.js.app.dibExEventsTest', ['options','dibMessage',function(options, dibMessage) {
    dibMessage.helpTip("Reset help");
    dibMessage.helpTip(
        "File loaded with the following definition, and opened the help<pre><code>"+
        "(function () {<br/>"+
        "       dib.defineAction('Dropins.dibExamples.js.app.dibExEventsTest', ['options','messageService',function(options, messageService) {<br/>"+
        "        messageService.helpTip(<br/>"+
        "            \"File loaded with the following definition code\"<br/>"+
        "        );<br/>"+
        "       }]);<br/>"+
        "}());</code></pre>"
    );
}]);
}());