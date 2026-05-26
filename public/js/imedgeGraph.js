const ImedgeGraph = function ($element, imedgeWindow) {
    this.window = imedgeWindow;
    this.graphDimensions = {
        top: null,
        left: null,
        width: null,
        height: null,
        start: null,
        end: null,
    };
    this.imageDimensions = {
        width: null,
        height: null,
    };
    this.valueRange = {
        min: null,
        max: null,
    };
    this.$element = $element;
    this.$canvas = $element.find('.imedge-graph-canvas');
    this.$imgElement = $element.find('.imedge-graph-img');
    this.$cursor = null;
    this.id = $element.attr('id');

    this.activeUrl = null;
    this.expectedUrl = null;
    this.requestedUrl = null;
    this.expectedStart = null;
    this.expectedEnd = null;
    this.imageRatio = 1;
    this.currentTimeStamp = null;
    this.selection = false;

    this.initialize();
};

ImedgeGraph.prototype = {
    initialize: function () {
        const _this = this;
        this.refreshFromImageData();
        this.checkForChangedWidth();
        $(window).on('resize', this.checkForChangedWidth.bind(_this));
        $('#layout').on('layout-change', this.layoutChanged.bind(_this));
    },

    isSelecting: function () {
        return this.selection !== false;
    },

    getSelection: function () {
        if (this.selection === false) {
            this.$element.addClass('selecting');
            this.selection = new ImedgeGraphSelection(this);
        }

        return this.selection;
    },

    clearSelection: function () {
        if (this.isSelecting()) {
            this.$element.removeClass('selecting');
            this.selection.remove();
            this.selection = false;
        }
    },

    hasImage: function () {
        return this.getTop() !== null;
    },

    refreshFromImageData: function () {
        const url = this.$imgElement.data('imedgeGraphUrl');
        if (typeof url === 'undefined') {
            console.log('Got an image without URL');
            return;
        }
        this.expectedUrl = url;
        this.requestedUrl = url;
        const graphDimensions = this.$imgElement.data('graph');
        if (typeof graphDimensions === 'undefined') {
            return;
        }
        const imageDimensions = this.$imgElement.data('image');
        if (typeof imageDimensions === 'undefined') {
            console.log('Got graphData, but no imageData')
            return;
        }
        this.activeUrl = url;
        this.graphDimensions = graphDimensions;
        this.imageDimensions = imageDimensions;
        this.clearSelection();
        this.checkForChangedWidth(false);
    },

    setDataFromResult: function (requestedUrl, result) {
        const $graph = this.$element;
        const $img = this.$imgElement;
        const graphDimensions = result['graph'];
        const imageDimensions = result['image'];
        const description = result['description'];
        if (typeof description !== 'undefined') {
            $graph.children('.imedge-graph-legend').html(description)
        } else {
            $graph.children('.imedge-graph-legend').html('');
        }
        this.$canvas.find('.imedge-graph-selection').remove();
        if (typeof graphDimensions === 'undefined') {
            console.error('Result has no graph data: ', requestedUrl);
            return;
        }
        if (typeof imageDimensions === 'undefined') {
            console.error('Result has no image data: ', requestedUrl);
            return;
        }

        this.graphDimensions = graphDimensions;
        this.imageDimensions = imageDimensions;
        this.valueRange = result['value'];

        const img = new Image(); // create temporary image
        img.src = result['raw']; // add your new image as src on the temporary image
        img.decode().then(() => { // wait until temporary image is decoded
            $img.attr('src', img.src); // replace your actual element now
        });
        this.activeUrl = requestedUrl;
        this.clearSelection();
        this.checkForChangedWidth();
    },

    getAvailableDimensions: function (result = {}) {
        const width = Math.floor(this.$canvas.width());
        const height = Math.floor(this.$canvas.height());
        if (width > 0) {
            result.width = width;
        } else {
            console.log('Got invalid width from Canvas: ' + this.$canvas.width());
        }
        if (height > 0) {
            result.height = height;
        } else {
            console.log('Got invalid width from Canvas: ' + this.$canvas.height());
        }

        return result;
    },

    applyColorScheme: function (result = {}) {
        result.colorScheme = this.window.colorScheme;
        return result;
    },

    layoutChanged: function () {
        this.checkForChangedWidth();
    },

    checkForChangedWidth: function (reload = true) {
        const oldRatio = this.imageRatio;
        this.imageRatio = this.$imgElement.width() / this.imageDimensions.width;
        if (reload && (Math.floor(oldRatio * 1000) !== Math.floor(this.imageRatio / 1000))) {
            window.imedge.loader.loadGraph(this, this.getAvailableDimensions());
        }
    },

    translatePosition: function (pos) {
        return this.imageRatio * pos;
    },

    getId: function () {
        return this.id;
    },

    getActiveUrl: function () {
        return this.activeUrl;
    },

    setExpectedUrl: function (url) {
        this.expectedUrl = url;
    },

    getExpectedUrl: function () {
        if (this.expectedUrl === null) {
            return null;
        }
        return window.imedge.loader.applyUrlParams(this.expectedUrl, this.getAvailableDimensions());
    },

    getRequestedUrl: function () {
        return this.requestedUrl;
    },

    getElement: function () {
        return this.$element;
    },

    refreshDebug: function () {
        this.debug('...' /*' t=' + this.top + ' l=' + this.left + ' w=' + this.width + ' h=' + this.height*/);
    },

    // Hint: duplicate
    pixel: function (value) {
        return Math.round(value) + 'px';
    },

    debug: function (text) {
        if (this.getHeight() < 40) {
            return;
        }
        const d = new Date(this.getCurrentTimestamp() * 1000);
        const l = new Date(this.getStart() * 1000);
        const r = new Date(this.getEnd() * 1000);
        const $el = this.$element.children('.imedge-graph-debug');
        if (window.icinga.ui === null) {
            console.log('Have no Icinga, no debug');
            return;
        }
        const fontSize = window.icinga.ui.getDefaultFontSize();
        const lineHeight = fontSize * 1.25;
        const spaceOnSides = 0.75;
        const right = this.translatePosition(
            this.imageDimensions.width - this.getLeft() - this.getWidth()
        ) - spaceOnSides;
        $el.css({
            // oben:
            top: this.pixel(this.translatePosition(this.getTop()) + lineHeight),
            // unten:
            // top: this.pixel(this.translatePosition(this.getTop() + this.getHeight()) - lineHeight),
            left: this.pixel(this.translatePosition(this.getLeft()) + spaceOnSides),
            right: this.pixel(right),
            textAlign: 'right'
        });
        // $el.html(l.toLocaleString() + ' - ' + r.toLocaleString() + '(' + d.toLocaleString() + '): ' + text);
        $el.html(d.toLocaleString());
    },

    stillExists: function () {
        return this.$element.parents('html').length > 0;
    },

    getTimeForMouseEvent: function (event) {
        const x = event.clientX - this.$element.offset().left;
        return this.calculateTimeFromOffset(x);
    },

    calculateTimeFromOffset: function (offset) {
        return Math.floor(
            this.getStart() + ((offset / this.imageRatio - this.getLeft()) / this.getWidth() * this.getDuration())
        );
    },

    getHorizontalOffsetForMouseEvent: function (event) {
        return event.clientY - this.$element.offset().top;
    },

    getVerticalOffsetForMouseEvent: function (event) {
        return event.clientX - this.$element.offset().left;
    },

    getTimeOffset: function (timestamp) {
        return parseInt(this.getLeft() + ((timestamp - this.getStart()) / this.getDuration()) * this.getWidth());
    },

    refreshCursor: function (cursor) {
        if (cursor.isHidden()) {
            this.hideCursor();
        } else if (this.showsTimestamp(cursor.getTimestamp())) {
            this.showCursor(cursor.getTimestamp());
            this.refreshDebug();
        } else {
            if (cursor.getTimestamp() < this.getStart()) {
                this.showCursor(this.getStart());
            } else {
                this.showCursor(this.getEnd());
            }
        }
    },

    showCursor: function (timestamp) {
        if (typeof this.graphDimensions === 'undefined') {
            this.hideCursor();
            return;
        }
        let start = this.graphDimensions.start;
        let timePercent = (timestamp - start) / (this.graphDimensions.end - start);
        if (timestamp && this.showsTimestamp(timestamp)) {
            if (this.$cursor === null) {
                this.$cursor = $('<div class="imedge-graph-cursor"></div>');
                this.$canvas.append(this.$cursor);
            }
            const x = this.getTimeOffset(timestamp);
            if (this.rawData && typeof this.rawData.meta !== 'undefined') {
                let adjustedStart = Math.floor(start / this.rawData.meta.step) * this.rawData.meta.step;
                let adjustedTimestamp = Math.floor(timestamp / this.rawData.meta.step) * this.rawData.meta.step;
                let pos = Math.floor((timestamp - adjustedStart) / this.rawData.meta.step);
                let cursorData = this.rawData.data[pos];
                let legends = this.rawData.meta.legend;
                if (typeof cursorData !== 'undefined') {
                    const self = this;
                    let text = $('<ul></ul>');
                    let d = new Date(adjustedTimestamp * 1000);
                    let $li = $('<li></li>').text(' (' + d.toLocaleDateString(undefined, {
                        weekday: 'long',
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    }) + ')');
                    $li.prepend($('<strong></strong> ').text(d.toLocaleTimeString()));
                    text.append($li);
                    let lastLabel = '';
                    let lastSuffix = '';
                    const output = this.formatValues(legends, cursorData);
                    if (output.length === 0) {
                        this.getCursorDetails(timePercent).html('').hide();
                    } else {
                        $.each(output, function (_, label) {
                            let li = $('<li></li>');
                            li.text(label);
                            text.append(li);
                        });
                        this.getCursorDetails(timePercent).html(text).show();
                    }
                }
            } else {
                this.getCursorDetails(timePercent).html('').hide();
            }
            this.$cursor.css({
                left: this.pixel(this.translatePosition(x)),
                top: this.pixel(this.translatePosition(this.getTop())),
                height: this.pixel(this.translatePosition(this.getHeight()))
            });
            this.currentTimeStamp = Math.round(timestamp);

            this.$cursor.show();
        } else {
            this.hideCursor();
        }
    },

    getCursorDetails: function (timePercent) {
        if (this.$canvas.hasClass('hint-left')) {
            if (timePercent < 0.25) {
                this.$canvas.removeClass('hint-left')
            }
        } else {
            if (timePercent > 0.75) {
                this.$canvas.addClass('hint-left')
            }
        }

        let $container = this.$canvas.find('.imedge-graph-cursor-details');
        if ($container.length === 0) {
            $container = $('<div class="imedge-graph-cursor-details"></div>');
            this.$canvas.prepend($container);
        }

        return $container;
    },

    hideCursor: function () {
        if (this.$cursor !== null) {
            this.$cursor.hide();
            this.$canvas.find('.imedge-graph-cursor-details').hide();
        }
    },

    formatPrefix: function (value, base = 1000, decimals = 2) {
        if (value === 0) {
            return '0';
        }

        const sign = value < 0 ? '-' : '';
        let abs = Math.abs(value);

        const prefixes = base === 1024
            ? ['', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'Zi', 'Yi']
            : ['', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];

        let index = 0;
        while (abs >= base && index < prefixes.length - 1) {
            abs /= base;
            index++;
        }

        let formatted = abs.toFixed(decimals);
        if (formatted.includes('.')) {
            formatted = formatted.replace(/\.?0+$/, '');
        }

        if (prefixes[index] === '') {
            return sign + formatted;
        }

        return sign + formatted + ' ' + prefixes[index];
    },

    formatValues: function (legends, values) {
        const result = new Array(legends.length).fill(undefined);
        const groups = {};

        legends.forEach((legend, i) => {
            const match = legend.match(/^(.*)\s+\((min|avg|max)\)$/);
            if (match) {
                const base = match[1];       // e.g. 'bit/s inbound'
                const type = match[2];       // min, avg, max

                if (!groups[base]) {
                    groups[base] = {
                        indices: {},
                        unit: '',
                        label: ''
                    };
                    // unit space label
                    const spaceIdx = base.indexOf(' ');
                    if (spaceIdx === -1) {
                        groups[base].unit = base;
                        groups[base].label = '';
                    } else {
                        groups[base].unit = base.substring(0, spaceIdx);
                        groups[base].label = base.substring(spaceIdx + 1);
                    }
                }

                groups[base].indices[type] = i;
                groups[base][type + 'Val'] = this.formatPrefix(values[i], 1000, 2);
            } else {
                // no min/avg/max-suffix: keep
                if (values[i] !== 0) {
                    result[i] = this.formatPrefix(values[i], 1000, 2) + legend;
                }
            }
        });

        for (const [base, group] of Object.entries(groups)) {
            const { minVal, avgVal, maxVal, unit, label, indices } = group;

            if (indices.min !== undefined && indices.avg !== undefined && indices.max !== undefined) {
                const avgIdx = indices.avg;
                const minIdx = indices.min;
                const maxIdx = indices.max;

                if (minVal === avgVal && avgVal === maxVal) {
                    if (avgVal.match('/^0 /') || avgVal === '0') { // ??
                        continue;
                    }
                    result[avgIdx] = `${avgVal}${unit} ${label}`.trim();
                } else {
                    result[avgIdx] = `${avgVal}${unit} ${label} (min ${minVal}${unit}, max ${maxVal}${unit})`;
                }

                result[minIdx] = null;
                result[maxIdx] = null;
            } else {
                for (const type of ['min', 'avg', 'max']) {
                    const idx = indices[type];
                    const val = group[type + 'Val'];
                    if (idx !== undefined) {
                        if (val === '0') { // ??
                            continue;
                        }
                        result[idx] = `${val}${unit} ${label} (${type})`.trim();
                    }
                }
            }
        }

        return result.filter(entry => entry !== null).filter(entry => typeof entry !== 'undefined');
    },
    showDebug: function () {
        this.$element.children('.imedge-graph-debug').show();
    },

    hideDebug: function () {
        this.$element.children('.imedge-graph-debug').hide();
    },

    showsTimestamp: function (ts) {
        return ts >= this.getStart() && ts <= this.getEnd();
    },

    getDuration: function () {
        return this.getEnd() - this.getStart();
    },

    getTop: function () {
        return this.graphDimensions.top;
    },

    getLeft: function () {
        return this.graphDimensions.left;
    },

    getWidth: function () {
        return this.graphDimensions.width;
    },

    getHeight: function () {
        return this.graphDimensions.height;
    },

    getStart: function () {
        return this.graphDimensions.start;
    },

    getEnd: function () {
        return this.graphDimensions.end;
    },

    endsNow: function () {
        const url = this.getActiveUrl();
        const params = window.icinga.utils.parseUrl(url).params;
        let end = null;
        $.each(params, function (_, param) {
            if (param.key === 'end') {
                end = param.value;
            }
        });

        return end === 'now';
    },

    getExpectedStart: function () {
        return this.expectedStart || this.getStart();
    },

    getExpectedEnd: function () {
        return this.expectedEnd || this.getEnd();
    },

    setExpectedStart: function (start) {
        this.expectedStart = start;
    },

    setExpectedEnd: function (end) {
        this.expectedEnd = end;
    },

    getCurrentTimestamp: function () {
        if (this.currentTimeStamp) {
            return this.currentTimeStamp;
        }
        // console.log('Has no current timestamp');

        return Math.round(this.getEnd() - this.getDuration() / 2);
    },

    normalizeFloorWithStep: function (time, step) {
        return Math.floor(time / step) * step;
    },

    normalizeCeilWithStep: function (time, step) {
        return Math.ceil(time / step) * step;
    },

    normalizeRoundWithStep: function (time, step) {
        return Math.round(time / step) * step;
    },

    getStepSizeForDuration: function (duration) {
        if (duration < 60 * 70) {
            return 1;
        } else if (duration < 3600 * 25) {
            return 60 * 15;
        } else if (duration < 86400 * 32) {
            return 3600 * 3;
        } else {
            return 86400;
        }
    }
};
