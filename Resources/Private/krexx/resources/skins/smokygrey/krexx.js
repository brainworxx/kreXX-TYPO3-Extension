(function () {
    var runner = function () {
        var smokyGrey = new SmokyGrey();
        smokyGrey.run();
        // We only do this once!
        document.removeEventListener('DOMContentLoaded', runner);
    };

    document.addEventListener('DOMContentLoaded', runner);
})();
