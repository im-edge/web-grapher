const ImedgeGraph = function ($element) {
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
        const url = this.$imgElement.data('rrdUrl');
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
        const $el = this.$element.children('.rrd-debug');
        if (window.icinga.ui === null) {
            console.log('Have no Icinga, no debug');
            return;
        }
        const fontSize = window.icinga.ui.getDefaultFontSize();
        const lineHeight = fontSize * 1.2;
        const spaceOnSides = 0.75;
        const right = this.translatePosition(
            this.imageDimensions.width - this.getLeft() - this.getWidth()
        ) - spaceOnSides;
        $el.css({
            top: this.pixel((this.translatePosition(this.getTop() + this.getHeight()) - lineHeight)),
            left: this.pixel((this.translatePosition(this.getLeft()) + spaceOnSides)),
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
        return this.getStart() + ((offset / this.imageRatio - this.getLeft()) / this.getWidth() * this.getDuration());
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
        if (timestamp && this.showsTimestamp(timestamp)) {
            if (this.$cursor === null) {
                this.$cursor = $('<div class="rrd-cursor"></div>');
                this.$canvas.append(this.$cursor);
            }
            const x = this.getTimeOffset(timestamp);
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

    hideCursor: function () {
        if (this.$cursor !== null) {
            this.$cursor.hide();
        }
    },

    showDebug: function () {
        this.$element.children('.rrd-debug').show();
    },

    hideDebug: function () {
        this.$element.children('.rrd-debug').hide();
    },

    showsTimestamp: function (ts) {
        return ts >= this.start && ts <= this.end;
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
