(function (window, $) {
    'use strict';

    const pixel = function (value) {
        return Math.round(value) + 'px';
    };

    let startup;
    let attempt = 0;
    const w = window;
    function launch(icinga)
    {
        w.imedge = new ImedgeGraphHandler(new ImedgeIcingaLayout(), icinga);
    }

    function safeLaunch()
    {
        attempt++;
        if (typeof(w.icinga) !== 'undefined' && w.icinga.initialized) {
            clearInterval(startup);
            launch(w.icinga);
            w.icinga.logger.info('IMEdge is ready');
            console.log('IMEdge (new) is ready');
        } else {
            if (attempt === 3) {
                console.log('IMEdge is still waiting for icinga');
            }
        }
    }

    $(document).ready(function () {
        startup = setInterval(safeLaunch, 150);
        setTimeout(safeLaunch, 1);
    });

})(window, jQuery);
