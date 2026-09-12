(function () {
    var runner = function () {
        var smokyGrey = new SmokyGrey();
        smokyGrey.run();
        // We only do this once!
        window.removeEventListener('load', runner);
    };

    window.addEventListener('load', runner);
})();
