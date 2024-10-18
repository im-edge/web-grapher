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
    this.loadingScheduled = false;
    this.deferredRequests = {};
    this.deferredTimers = {};
    this.dirtyGraphs = {};
    this.dirtyQueue = [];
    this.loadingGraphs = {};
    this.initialize();
};

ImedgeGraphLoader.prototype = {
    initialize: function () {
        // TODO: module.icinga.utils
        this.utils = window.icinga.utils;
        setInterval(this.scheduleLoading.bind(this), 500);
    },

    loadGraph: function (graph, tweakParams) {
        let url = graph.getExpectedUrl();
        if (typeof url === 'undefined') {
            // Cannot load a graph with no Url
            return;
        }
        tweakParams = graph.getAvailableDimensions(tweakParams);
        url = this.applyUrlParams(url, tweakParams);
        if (url === graph.getActiveUrl()) {
            console.log('expected is active');
            return; // TODO: force if scheme changed?
        }
        graph.setExpectedUrl(url); // Remains the same, if tweak is empty
        this.tellGraphAboutExpectedParams(graph, tweakParams); // end, start
        this.markDirty(graph);
    },

    reallyLoadGraph: function (graph, url) {
        let request = $.ajax({
            url: url,
            cache: true,
            headers: {
                'X-IMEdge-ColorScheme':  (this.graphHandler.window.colorScheme)
            },
            success: this.loadingSucceeded.bind(this),
            error: this.loadingFailed.bind(this),
            complete: this.loadingCompleted.bind(this)
        });
        request.graph = graph;
        request.requestedUrl = url;
        graph.requestedUrl = url;
        this.loadingGraphs[graph.getId()] = request;
        // Doesn't work, SNMP is on another node UUID (not Metric)
        // if (graph.getDuration() === 0 && graph.endsNow()) {
        //     this.applyUrlParams(url, {triggerScenario: 'interfaceTraffic'});
        // }

        return request;
    },

    loadingSucceeded: function (result, textStatus, request) {
        const graph = request.graph;
        graph.setDataFromResult(request.requestedUrl, result);
        this.addGraphSettingsToContainerUrl(graph);
    },

    loadingFailed: function (request, status, error) {
        console.log('Loading ' + request.requestedUrl + ' failed (' + status + '): ' + error);
    },

    loadingCompleted: function (request, status) {
        const graph = request.graph;
        const idx = graph.getId();
        delete(this.loadingGraphs[idx]);
        this.scheduleLoading();
    },

    tellGraphAboutExpectedParams: function (graph, expectedParams) {
        if (expectedParams.start) {
            graph.setExpectedStart(expectedParams.start)
        }
        if (expectedParams.end) {
            graph.setExpectedEnd(expectedParams.end)
        }
    },

    markDirty: function (graph) {
        const id = graph.getId();
        if (id in this.dirtyGraphs) {
            return;
        }
        this.dirtyGraphs[id] = graph;
        this.dirtyQueue.push(id)
        this.scheduleLoading();
    },

    scheduleLoading: function () {
        if (this.loadingScheduled) {
            return;
        }
        this.loadingScheduled = true;
        setTimeout(this.triggerLoading.bind(this), 5);
    },

    triggerLoading: function () {
        this.loadingScheduled = false;
        const _this = this;
        let id, graph;
        let deferred = [];
        while (this.dirtyQueue.length) {
            id = this.dirtyQueue.shift();
            graph = this.dirtyGraphs[id];
            if (typeof graph === 'undefined') {
                // already processed via dirtyQueue
                continue;
            }
            delete(this.dirtyGraphs[id]);
            graph = _this.graphHandler.graphs[id];
            if (typeof graph === 'undefined') {
                // graph has been destroyed in the meantime
                continue;
            }
            if (!graph.stillExists()) {
                // graph vanished from DOM, not (yet) destroyed
                continue;
            }

            if (id in _this.loadingGraphs) {
                deferred.push(id);
            } else if (graph.getRequestedUrl() !== graph.getExpectedUrl()) {
                _this.reallyLoadGraph(graph, graph.getExpectedUrl())
            }
        }
        this.dirtyQueue = deferred;
    },

    addGraphSettingsToContainerUrl: function (graph) {
        const $container = graph.$element.closest('.container');
        const newUrl = this.utils.addUrlParams(this.utils.removeUrlParams($container.data('icingaUrl'), [
            'metricStart',
            'metricEnd'
        ]), {
            metricStart: graph.getStart(),
            metricEnd: graph.getEnd(),
        });
        $container.find('>.controls a.refresh-container-control').attr('href', newUrl);
        $container.data({icingaUrl: newUrl});
        if (typeof window.icinga !== 'undefined') {
            // We decided do not pollute history, as we have way too many requests
            // window.icinga.history.pushCurrentState();
        }
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
