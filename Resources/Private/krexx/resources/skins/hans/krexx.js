(function () {
    var runner = function () {
        var hans = new Hans();
        hans.run();
        // We only do this once!
        document.removeEventListener('DOMContentLoaded', runner);
    }

    document.addEventListener("DOMContentLoaded", runner);
})();
