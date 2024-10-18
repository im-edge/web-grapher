
/**
 * Takes care of loading images
 *
 * LoadingQueue -> push, load max 6 at once. Once an image is done -> next one. URL changes while not pending: replace
 * Request for a graph with pending request -> deferred queue, add timer with 100ms to deferred timers - if not already set
 *
 * @param imedgeGraphHandler
 * @constructor
 */
const ImedgeGraphLoader = function (imedgeGraphHandler) {
    this.graphHandler = imedgeGraphHandler;
    this.deferredRequests = {};
    this.deferredTimers = {};
    this.dirtyGraphs = {};
    this.loadingGraphs = {};
    this.initialize();
};

ImedgeGraphLoader.prototype = {
    initialize: function () {
        // TODO: module.icinga.utils
        this.utils = window.icinga.utils;
        setInterval(this.triggerLoading.bind(this), 500);
        this.triggerLoading();
    },

    loadGraph: function (rrdGraph, tweakParams) {
        let url = rrdGraph.getUrl();
        if (typeof url === 'undefined') {
            return;
        }
        tweakParams = rrdGraph.getAvailableDimensions(tweakParams);
        const requestedUrl = this.applyUrlParams(url, tweakParams);
        this.tellGraphAboutExpectedParams(rrdGraph, tweakParams);
        if (requestedUrl === rrdGraph.getUrl()) {
            return;
        }
        /*
        // Doesn't work, SNMP is on another node UUID (not Metric)
        let sendUrl = requestedUrl;
        if (rrdGraph.getDuration() === 0 && rrdGraph.endsNow()) {
            this.applyUrlParams(sendUrl, {triggerScenario: 'interfaceTraffic'});
        }
        */

        let request = $.ajax({
            url: requestedUrl,
            cache: true,
            headers: {
                'X-IMEdge-ColorScheme':  (this.graphHandler.window.colorScheme)
            }
        });
        request.done(this.handleGraphResult.bind(this));
        // request.fail(this.onFailure);
        // request.always(this.onComplete);
        request.rrdGraph = rrdGraph;
        request.requestedUrl = requestedUrl;

        return request;
    },

    tellGraphAboutExpectedParams: function (rrdGraph, expectedParams) {
        if (expectedParams.start) {
            rrdGraph.setExpectedStart(expectedParams.start)
        }
        if (expectedParams.end) {
            rrdGraph.setExpectedEnd(expectedParams.end)
        }
    },

    markDirty: function (graph) {
        this.dirtyGraphs[graph.getId()] = graph;
        this.triggerLoading();
    },

    triggerLoading: function () {
        const _this = this;
        $.each(this.dirtyGraphs, function (idx, rrdGraph) {
            let loading = _this.loadingGraphs;
            if (typeof loading[idx] === 'undefined') {
                loading[idx] = _this.loadGraph(rrdGraph);
                console.log('Loading', idx);
            } else {
                if (! _this.dirtyGraphs[idx].wantsUrl(loading[id].requestedUrl)) {
                    loading[idx].cancel();
                    loading[idx] = _this.loadGraph(rrdGraph);
                    console.log('URL changed', idx);
                } else {
                    console.log('URL stays the same:', idx);
                }
            }
        });
    },

    finishLoading: function (rrdGraph, requestedUrl, result) {
        const idx = rrdGraph.getId();
        if (idx in this.dirtyGraphs && this.dirtyGraphs[idx].wantsUrl(requestedUrl)) {
            delete(this.dirtyGraphs[idx]);
        }
        // This used to be commented out?!
        delete(this.loadingGraphs[idx]);
        rrdGraph.setDataFromResult(requestedUrl, result);
        this.addGraphSettingsToContainerUrl(rrdGraph);
    },

    addGraphSettingsToContainerUrl: function (rrdGraph) {
        const $container = rrdGraph.$element.closest('.container');
        const newUrl = this.utils.addUrlParams(this.utils.removeUrlParams($container.data('icingaUrl'), [
            'metricStart',
            'metricEnd'
        ]), {
            metricStart: rrdGraph.getStart(),
            metricEnd: rrdGraph.getEnd(),
        });
        $container.find('>.controls a.refresh-container-control').attr('href', newUrl);
        $container.data({icingaUrl: newUrl});
        if (typeof window.icinga !== 'undefined') {
            window.icinga.history.pushCurrentState();
        }
    },

    failLoading: function (idx) {
        delete(this.loadingGraphs[idx]);
    },

    handleGraphResult: function (result, textStatus, request) {
        const rrdGraph = request.rrdGraph;
        this.finishLoading(rrdGraph, request.requestedUrl, result);
    },

    applyUrlParams(url, params) {
        //params.rnd = new Date().getTime();
        if (params.start === false) {
            url = this.utils.removeUrlParams(url, ['start']);
            delete(params.start);
        }
        if (params.end === false) {
            url = this.utils.removeUrlParams(url, ['end']);
            delete(params.end);
        }
        // Fake end: params.end = Math.floor(new Date().getTime() / 1000);
        url = this.utils.addUrlParams(url, params);

        return url;
    }
};
