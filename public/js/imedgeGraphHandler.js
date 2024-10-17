
const ImedgeGraphHandler = function (layout, icinga) {
    this.layout = layout;
    this.icinga = icinga;
    this.lastId = 0;
    this.graphs = {};
    this.doubleClickTimeout = 300;
    this.clicks = 0;
    this.selectingGraph = false;
    this.selectionRange = null;
    this.skipClick = false;
    setTimeout(this.initialize.bind(this), 0);
};

ImedgeGraphHandler.prototype = {
    initialize: function () {
        this.window = new ImedgeWindow();
        this.cursor = new ImedgeGraphCursor();
        this.loader = new ImedgeGraphLoader(this);
        /*
        this.icinga.loader.loadUrlBeforeIMEdge = this.icinga.loader.loadUrl;
        const _this = this;

        this.icinga.loader.loadUrl = function (url, $target, data, method, action, autorefresh, progressTimer, extraHeaders) {
            if (typeof extraHeaders === 'undefined') {
                extraHeaders = {'X-IMEdge-ColorScheme': _this.window.colorScheme};
            } else {
                extraHeaders['X-IMEdge-ColorScheme'] = _this.window.colorScheme;
            }
            this.loadUrlBeforeIMEdge(url, $target, data, method, action, autorefresh, progressTimer, extraHeaders);
        }.bind(this.icinga.loader);
         */
        document.addEventListener('wheel', function (event) {
            if ($(event.target).closest('.imedge-graph-canvas').length > 0) {
                event.preventDefault();
            }
        }, {passive: false});
        $(document).on('mousemove', '.imedge-graph-canvas', this.mouseMove.bind(this));
        $(document).on('mouseenter', '.imedge-graph-canvas', this.mouseEnter.bind(this));
        $(document).on('mouseleave', '.imedge-graph-canvas', this.mouseLeave.bind(this));
        $(document).on('mousedown', '.imedge-graph-canvas', this.mouseDown.bind(this));
        $(document).on('wheel', '.imedge-graph-canvas', this.scroll.bind(this));
        $(document).on('mouseup', this.mouseUp.bind(this));
        $(document).on('click', '.imedge-graph-canvas', this.mouseClick.bind(this));
        $(document).on('click', '.rrd-toggle-ds', this.toggleDs.bind(this));
        this.layout.onChangedWidth(this.adjustWidthForAllImages.bind(this));
        $(document).on('rendered', '.container', this.containerRendered.bind(this));
        $(document).on('close-column', '.container', this.containerRendered.bind(this));
        $(document).on('click', '.imedge-graph a', this.linkClicked.bind(this));
        setTimeout(this.registerNewGraphs.bind(this), 0);
        // this.loadAllGraphs();
        setInterval(this.registerNewGraphs.bind(this), 1000);
    },

    containerRendered: function (event) {
        this.registerNewGraphs();
        this.forgetObsoleteGraphs();
        // this.loadAllGraphs();
    },

    toggleFullscreen: function (graph) {
        const $element = graph.$element;
        if ($element.hasClass('fullscreen')) {
            $element.detach().appendTo($element.originalParent).css({
                position: 'unset',
                zIndex: 'unset',
                top: 'unset',
                left: 'unset',
                height: '100%',
                background: 'unset'
            });
            delete($element.originalParent);
            $element.removeClass('fullscreen');
        } else {
            $element.originalParent = $element.parent();
            $element.detach().appendTo($('#layout')).css({
                position: 'absolute',
                zIndex: 1000,
                top: 0, left: 0,
                height: '100vh',
                background: this.window.getInheritedBackgroundColor($('#col1')[0])
            });
            $element.addClass('fullscreen');
        }
        const $canvas = $element.canvas;
        this.loader.loadGraph(graph, {
            width: Math.floor($canvas.width()),
            height: Math.floor($canvas.height()),
        });
    },

    linkClicked: function (e) {
        e.stopPropagation();
        e.preventDefault();
        const $a = $(e.currentTarget);
        const $graph = $a.closest('.imedge-graph');
        if ($graph.length === 0) {
            console.log('no .imedge-graph-canvas');
            return false;
        }
        const graph = this.getGraphForElement($graph);
        if (graph === null) {
            console.log('no graph for link');
            return false;
        }
        const url = this.icinga.utils.parseUrl($a.attr('href'));

        if ($a.hasClass('imedge-toggle-fullscreen')) {
            $a.removeClass('icon-resize-full');
            $a.addClass('icon-resize-small');
            this.toggleFullscreen(graph);
            return false;
        }
        let adjust = {};
        $.each(url.params, function (_, param) {
            // Hint: template is here for max only, must go
            if ($.inArray(param.key, ['start', 'end', 'template']) !== -1) {
                adjust[param.key] = param.value
            }
        });
        const width = graph.$canvas.width();
        const height = graph.$canvas.height();
        if (width) {
            adjust['width'] = Math.floor(width);
        }
        if (height) {
            adjust['height'] = Math.floor(height);
        }
        if ($.isEmptyObject(adjust)) {
            console.log('no adjustment')
            console.log(url);
        } else {
            console.log('adjustment')
            console.log(adjust);
            const $set = $graph.closest('.imedge-graph-set');
            if ($set.length === 0) {
                this.loader.loadGraph(graph, adjust);
            } else {
                const _this = this;
                $set.find('.imedge-graph').each(function (_, $graph) {
                    const graph = _this.getGraphForElement($graph);
                    _this.loader.loadGraph(graph, adjust);
                });
            }
        }
        return false;
    },

    scroll: function (event) {
        const $container = $(event.currentTarget).closest('div.imedge-graph-set'); // Hint: currently unused
        const delta = event.originalEvent.deltaY;
        if ($container.length) {
            const _this = this;
            // TODO: Nope. Determine range for active one, and then zoom the other ones
            $.each($container.find('.imedge-graph'), function (idx, $graph) {
                const graph = _this.getGraphForElement($graph);
                if (graph) {
                    _this.zoomGraph(graph, delta);
                }
            })
        } else {
            const graph = this.getGraphForElement(event.currentTarget);
            if (!graph) {
                console.log('Scrolling, no graph');
                return;
            }

            this.zoomGraph(graph, delta);
        }
        event.stopPropagation();
        event.stopImmediatePropagation();
        return false;
    },

    zoomGraph: function (graph, delta) {
        if (delta === 0 || isNaN(delta)) {
            console.log('Invalid delta, ignoring: ', delta);
            return;
        }
        if (delta > 0) {
            delta = 3;
        } else {
            delta = -2;
        }
        // +90, -90
        // event.originalEvent.deltaY = 0;
        const maxEnd = Math.floor(new Date().getTime() / 1000);
        const duration = graph.getDuration();
        const position = graph.getCurrentTimestamp();
        if (position === null) {
            console.log('Sorry, got no position - cannot zoom');
            return;
        }
        const currentStart = graph.getExpectedStart();
        const currentEnd = graph.getExpectedEnd();
        if (position < currentStart || position > currentEnd) {
            console.log('Position lost: ' + position + ' (' + currentStart + ', ' + currentEnd + ')');
            return;
        }
        const stepSize = graph.getStepSizeForDuration(currentEnd - currentStart);
        const left = position - currentStart;
        const right = currentEnd - position;
        console.log('Step size:', stepSize, 'Position:', position, 'Left:', left, 'Right:', right);
        const leftFactor = left / duration;
        const rightFactor = right / duration;
        const requestedMod = delta * 900; // TODO: steps
        console.log('Requested mod:', requestedMod, 'fac left', leftFactor, 'fac right', rightFactor);
        const newStart = graph.normalizeFloorWithStep(currentStart - requestedMod * leftFactor, stepSize);
        let newEnd = graph.normalizeCeilWithStep(currentEnd + requestedMod * rightFactor, stepSize);
        console.log('new Start:', newStart, 'new End:', newEnd);
        /*
                    if ((newEnd - newStart) < currentEnd) { // TODO: move up, check against duration, calculate once
                        requestedMod = currentEnd / 2;
                        //newStart = (currentStart - requestedMod * leftFactor);
                        newEnd = (currentEnd + requestedMod * rightFactor);
                    }
        */
//             console.log(newEnd, maxEnd);
        if (newEnd > maxEnd) {
            console.log('Capped end', newEnd, 'to max', maxEnd);

            newEnd = graph.normalizeCeilWithStep(maxEnd, stepSize);
        }
        if (newStart >= newEnd || isNaN(newStart) || isNaN(newEnd)) {
            console.log('Invalid, new start:', newStart, ', end', newEnd, ', step:', stepSize, ', left:', left, ', right: ', right, ', duration: ', duration);
            return;
        }

        if (newStart === currentStart && newEnd === currentEnd) {
            console.log('No change, staying at current zoom level');
            // return;
        }

        console.log('Loading start:', newStart, ', end', newEnd, ', step:', stepSize, ', left:', left, ', right: ', right, ', duration: ', duration);
        this.changeTimeRange(graph, newStart, newEnd);
    },

    mouseClick: function (event) {
        // Left click only
        if (event.which !== 1) {
            return true;
        }
        if (this.skipClick) {
            event.stopPropagation();
            event.preventDefault();
            this.skipClick = false;
            return false;
        } else {
            return true;
        }
    },

    mouseDown: function (event) {
        // Left click only
        if (event.which !== 1) {
            return true;
        }
        return this.startSelecting(event);
    },

    startSelecting: function (event) {
        this.cursor.hide();
        this.refreshCursors();

        const graph = this.getGraphForElement($(event.currentTarget).closest('.imedge-graph'));
        if (!graph.hasImage()) {
            return true;
        }
        event.stopPropagation();
        event.preventDefault();
        this.selectingGraph = graph;
        this.selectionRange = new ImedgeTimeRangeSelection(graph);
        this.updateSelection(event, graph);

        return false;
    },

    updateSelection: function (event, graph) {
        const ts = graph.getTimeForMouseEvent(event);
        const range = this.selectionRange;
        if (range) {
            range.setPosition(ts);
            graph.getSelection().adjustSelectionRectangles(
                graph.getTimeOffset(range.getBegin()) - graph.getLeft(),
                graph.getTimeOffset(range.getEnd()) - graph.getLeft()
            );
        }
    },

    mouseUp: function (event) {
        if (this.selectingGraph === false || this.selectingGraph === null) { // TODO: why both?
            return true;
        }
        this.clicks++;
        if (this.clicks === 2) {
            this.clicks = 0;
            event.stopPropagation();
            event.preventDefault();
            this.doubleClick(this.selectingGraph);
            return false;
        }
        const _this = this;
        setTimeout(function () {
            _this.clicks = 0;
        }, this.doubleClickTimeout);

        this.endSelecting(event);
        return false;
    },

    isValidSelection: function () {
        return this.selectionRange && this.selectionRange.getBegin() !== this.selectionRange.getEnd();
    },

    changeTimeRange: function (graph, start, end) {
        const $container = graph.getElement().closest('.imedge-graph-set');
        // TODO should be at least 10xStep
        // $('input[name="start"]').val(this.start);
        // $('input[name="end"]').val(this.end);
        if ($container.length) {
            const self = this;
            $container.find('.imedge-graph.imedge-graph-registered').each(function (idx, $graph) {
                let graph = self.graphs[$graph.id];
                self.loader.loadGraph(graph, {
                    start: start,
                    end: end
                });
            });
        } else {
            this.loader.loadGraph(graph, {
                start: start,
                end: end
            });
        }
    },

    endSelecting: function (event) {
        this.cursor.show();
        this.refreshCursors();
        const graph = this.selectingGraph;
        const maxEnd = Math.floor(Date.now() / 1000);
        event.stopPropagation();
        event.preventDefault();
        if (this.isValidSelection()) {
            this.changeTimeRange(
                graph,
                this.selectionRange.getBegin(),
                Math.min(this.selectionRange.getEnd(), maxEnd)
            );
            this.skipClick = true;
            this.resetSelection();
        } else if (this.selectingGraph) {
            this.resetSelection();
        }
    },

    resetSelection: function () {
        this.selectingGraph.clearSelection();
        this.selectingGraph = null;
        this.selectionRange = null;
    },
    /*
            changeSelectionX: function (x) {
                if (x !== this.currentX) {
                    var left = Math.min(x, this.startX);
                    var right = Math.max(x, this.startX);
                    this.currentX = x;
                    // console.log('From', left, 'to', right);
                    this.adjustSelectionRectangles(left, right);
                    this.start = this.calculateTimeFromOffset(left);
                    this.end = this.calculateTimeFromOffset(right);
                }
            },
    */
    doubleClick: function (graph) {
        // TODO: combine with dirty/queue
        this.loader.loadGraph(graph, {
            start: false,
            end: false
        });
        this.resetSelection();
    },

    registerNewGraphs: function () {
        const _this = this;
        $('.imedge-graph').not('.imedge-graph-registered').each(function (idx, graph) {
            _this.registerGraph($(graph));
        });
    },

    toggleDs: function (event) {
        const $a = $(event.currentTarget);
        const ds = $a.data('dsName');
        // link -> div -> div. Might be improved
        $a.toggleClass('disabled');
        const _this = this;
        $.each(this.getGraphsRelatedToLegendLink($a), function (idx, graph) {
            const url = graph.getUrl();
            _this.loader.loadGraph(graph, {disableDatasources: _this.toggleDsParam(url, ds)});
        });
    },

    getGraphsRelatedToLegendLink: function ($a) {
        let legendGraphs = [];
        const _this = this;
        $a.closest('.imedge-graph-set').find('.imedge-graph').each(function (idx, element) {
            const graph = _this.getGraphForElement(element);
            if (graph) {
                legendGraphs.push(graph);
            }
        });

        return legendGraphs;
    },

    toggleDsParam: function (url, toggleDs) {
        const params = window.icinga.utils.parseUrl(url).params;
        let disabled = [];
        $.each(params, function (idx, param) {
            if (param.key === 'disableDatasources' && param.value) {
                disabled = decodeURIComponent(param.value).split(',');
            }
        });
        const dsIdx = disabled.indexOf(toggleDs);
        if (dsIdx > -1) {
            disabled.splice(dsIdx, 1);
        } else {
            disabled.push(toggleDs);
        }

        return disabled.join(',');
    },

    forgetObsoleteGraphs: function () {
        let lost = [];
        $.each(this.graphs, function (idx, graph) {
            if ($('#' + graph.id).length === 0) {
                lost.push(idx);
            }
        });
        const self = this;
        $.each(lost, function (_, idx) {
            delete(self.graphs[idx]);
        })
    },

    registerGraph: function ($graph) {
        const id = 'imedge-graph_' + ++this.lastId;
        // TODO: check for existing ID
        $graph.attr('id', id);
        $graph.addClass('imedge-graph-registered');
        // const $debug = $('<div class="rrd-debug"></div>').hide();
        // $graph.append($debug);
        const graph = new ImedgeGraph($graph);
        this.graphs[id] = graph;
        if (!$graph.find('img').data('preLoaded')) {
            this.loader.loadGraph(graph, graph.getAvailableDimensions());
        }

        return this.graphs[id];
    },

    getGraphForElement: function (element) {
        const graph = this.graphs[$(element).attr('id')];
        if (typeof graph === 'undefined') {
            return null;
        } else {
            return graph;
        }
    },

    mouseEnter: function (event) {
        if (this.selectingGraph) {
            return;
        }
        const graph = this.getGraphForElement($(event.currentTarget).closest('.imedge-graph'));
        if (graph === null) {
            console.log('Mouseenter for no graph?!');
            console.log(event);
            return;
        }
        const $graph = graph.getElement();
        graph.debug('Ready');
        graph.showDebug();
        const $description = $graph.children('div.description.hidden');
        if (!$description.is(':empty')) {
            $description.addClass('hovered');
        }
    },

    mouseLeave: function (event) {
        const graph = this.getGraphForElement($(event.currentTarget).closest('.imedge-graph'));
        if (graph === null) {
            console.log('MouseLeave for no graph?!');
            console.log(event);
            return;
        }
        const $graph = graph.getElement();
        $graph.children('div.description.hidden').removeClass('hovered');
        graph.hideDebug();
        graph.hideCursor();
    },

    mouseMove: function (event) {
        let graph;

        if (this.selectingGraph) {
            graph = this.selectingGraph;
        } else {
            graph = this.getGraphForElement($(event.currentTarget).closest('.imedge-graph'));
        }
        if (! graph) {
            icinga.logger.info('Moving, no graph');
            return;
        }
        if (graph.isSelecting()) {
            this.updateSelection(event, graph);
            return;
        }
        const ts = graph.getTimeForMouseEvent(event);
        if (! isFinite(ts)) {
            // icinga.logger.error('Got no TS on', graph, 'for', event);
            return;
        }
        this.cursor.show(ts);
        this.refreshCursors();
    },

    refreshCursors: function () {
        const cursor = this.cursor;
        console.log('refreshing cursors');
        $.each(this.graphs, function (idx, foundGraph) {
            foundGraph.refreshCursor(cursor);
        });
    },

    loadAllGraphs: function () {
        const loader = this.loader;
        $.each(this.graphs, function (idx, graph) {
            loader.loadGraph(graph, {});
        });
    },

    adjustWidthForAllImages: function ($container, width) {
        const _this = this;
        width = Math.floor(width);
        width -= 24; // padding, margin
        $container.find('.imedge-graph').each(function (idx, element) {
            let graph = _this.getGraphForElement(element);
            if (! graph) {
                return;
            }
            let $img = graph.$imgElement;
            if ($img.width() !== width) {
                console.log($img.width(), 'is not', width, ' - reloading');
                // this is for responsiveness:
                // _this.loader.loadGraph(graph, {width: width});
                _this.loader.loadGraph(graph, {});// , {width: width});
            }
        });
    }
};
