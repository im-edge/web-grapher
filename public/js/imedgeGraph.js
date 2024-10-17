
const ImedgeGraph = function ($element) {
    this.$element = $element;
    this.$canvas = $element.find('.imedge-graph-canvas');
    this.$imgElement = $element.find('.imedge-graph-img');
    this.$cursor = null;
    this.id = $element.attr('id');
    this.top = null;
    this.left = null;
    this.width = null;
    this.height = null;
    this.start = null;
    this.end = null;
    this.expectedStart = null;
    this.expectedEnd = null;
    this.imageWidth = null;
    this.imageHeight = null;
    this.imageRatio = 1;
    this.currentTimeStamp = null;
    this.selection = false;

    this.initialize();
};

ImedgeGraph.prototype = {
    initialize: function () {
        const _this = this;
        this.$element.data('graph', this.$imgElement.data('graph'));
        this.$element.data('image', this.$imgElement.data('image'));
        this.refreshFromData();
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
            this.selection = new ImmedgeGraphSelection(this);
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
        return this.top !== null;
    },

    refreshFromData: function () {
        const graphData = this.$imgElement.data('graph');
        const imgData = this.$imgElement.data('image');
        if (typeof graphData === 'undefined') {
            // Initial load w/o image ready
            // console.log('Graph has no data', this.$imgElement);
            return;
        }
        this.top = graphData.top;
        this.left = graphData.left;
        this.width = graphData.width;
        this.height = graphData.height;
        this.start = graphData.start;
        this.end = graphData.end;
        this.imageWidth = imgData.width;
        this.imageHeight = imgData.height;
        this.clearSelection();
        this.checkForChangedWidth(false);
        this.debug('From fresh data');
        // this.refreshDebug();
    },

    getAvailableDimensions: function (result = {}) {
        const width = Math.round(this.$canvas.width());
        const height = Math.round(this.$canvas.height());
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
        console.log('IMEdge detected layout change');
        this.checkForChangedWidth();
    },

    checkForChangedWidth: function (reload = true) {
        this.imageRatio = this.$imgElement.width() / this.imageWidth;
        //console.log(this.$imgElement.width(), this.imageWidth, this.imageRatio);
        //this.$element.css({
        // This is currently required to limit our selection range
        // height: pixel(this.translatePosition(imgData.height)),
        // width: this.translatePosition(this.imageWidth) + 'px'
        //});
        if (reload) {
            window.imedge.loader.loadGraph(this, {width: this.$imgElement.width()})
        }
    },

    translatePosition: function (pos) {
        return this.imageRatio * pos;
    },

    getId: function () {
        return this.id;
    },

    getUrl: function () {
        return this.$imgElement.data('rrdUrl');
    },

    getElement: function () {
        return this.$element;
    },

    refreshDebug: function () {
        this.debug('...' /*' t=' + this.top + ' l=' + this.left + ' w=' + this.width + ' h=' + this.height*/);
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
        $el.css({
            // top: (this.top - 1) + 'px',
            // -> 1.5 -> 0.5 + 1px border
            top: pixel((this.translatePosition(this.top + this.height) - lineHeight)),
            left: pixel((this.translatePosition(this.left) + spaceOnSides)),
            right: pixel((this.translatePosition(this.imageWidth - this.left - this.width) - spaceOnSides)),
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
                this.$element.append(this.$cursor);
            }
            const x = this.getTimeOffset(timestamp);
            this.$cursor.css({
                left: pixel(this.translatePosition(x)),
                top: pixel(this.translatePosition(this.getTop())),
                height: pixel(this.translatePosition(this.getHeight()))
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
        return this.top;
    },

    getLeft: function () {
        return this.left;
    },

    getWidth: function () {
        return this.width;
    },

    getHeight: function () {
        if (typeof(this.height) === 'undefined') {
            return null;
        }
        return this.height;
    },

    getStart: function () {
        return this.start;
    },

    getEnd: function () {
        return this.end;
    },

    endsNow: function () {
        const url = this.getUrl();
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
        return this.expectedStart || this.start;
    },

    getExpectedEnd: function () {
        return this.expectedEnd || this.end;
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
        console.log('Has no current timestamp');

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
    },

    setDataFromResult: function (requestedUrl, result) {
        const $graph = this.$element;
        const $img = this.$imgElement;
        const graph = result['graph'];
        const image = result['image'];
        const description = result['description'];
        if (typeof description !== 'undefined') {
            $graph.children('.imedge-graph-legend').html(description)
        } else {
            $graph.children('.imedge-graph-legend').html('');
        }
        if (typeof graph === 'undefined') {
            console.error('Result has no graph data:', requestedUrl);
            return;
        }
        $graph.data('graph', graph);
        $graph.data('image', image);
        $graph.data('value', result['value']);

        const img = new Image(); // create temporary image
        img.src = result['raw']; // add your new image as src on the temporary image
        img.decode().then(() => { // wait until temporary image is decoded
            $img.attr('src', img.src); // replace your actual element now
            if (image.height > 0) {
                // $img.css({height: image.height + 'px'});
            }
        });
//            $img.attr('src', result['raw']);
        $graph.data('rrdUrl', requestedUrl);
        this.refreshFromData();
    }
};
