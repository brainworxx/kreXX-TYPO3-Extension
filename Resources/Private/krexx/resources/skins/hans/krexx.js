(function () {
    var runner = function () {
        var hans = new Hans();
        hans.run();
        // We only do this once!
        window.removeEventListener('load', runner);
    }

    window.addEventListener("load", runner);
})();
