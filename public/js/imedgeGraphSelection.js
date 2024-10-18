const ImedgeGraphSelection = function (graph) {
    this.graph = graph;
    this.tplSelection = '<div class="imedge-graph-selection"> </div>';
    this.tplBefore = '<div class="imedge-graph-time-not-selection selection-before" style="left: 0"> </div>';
    this.tplMain   = '<div class="imedge-graph-time-selection"> </div>';
    this.tplAfter  = '<div class="imedge-graph-time-not-selection selection-after" style="right: 0"> </div>';
    this.rectBefore = null;
    this.rectMain = null;
    this.rectAfter = null;
    this.initialize();
};

ImedgeGraphSelection.prototype = {
    initialize: function () {
        this.rectBefore = $(this.tplBefore);
        this.rectMain = $(this.tplMain);
        this.rectAfter = $(this.tplAfter);
        this.getSelection().append(this.rectAfter, this.rectMain, this.rectBefore);
    },

    getSelection: function () {
        if (typeof(this.$selection) === 'undefined' || this.$selection === null) {
            const $canvas = this.graph.$canvas;
            let $selection = $canvas.find('.imedge-graph-selection');
            if ($selection.length === 0) {
                $selection = $(this.tplSelection);
                $canvas.prepend($selection);
                $selection.css({
                    top: this.pixel(this.graph.translatePosition(this.graph.getTop())),
                    left: this.pixel(this.graph.translatePosition(this.graph.getLeft())),
                    height: this.pixel(this.graph.translatePosition(this.graph.getHeight())),
                    width: this.pixel(this.graph.translatePosition(this.graph.getWidth()))
                });
            }

            this.$selection = $selection;
        }

        return this.$selection;
    },

    isValidSelection: function () {
        // Ask TimeRange?
        return true;
    },

    adjustSelectionRectangles: function (left, right) {
        this.rectBefore.css({
            'width': this.pixel(this.graph.translatePosition(left))
        });
        this.rectMain.css({
            'left': this.pixel(this.graph.translatePosition(left)),
            'width': this.pixel(this.graph.translatePosition(right - left))
        });
        this.rectAfter.css({
            'width': this.pixel(this.graph.translatePosition(this.graph.getWidth() - right))
        });
        if (this.isValidSelection()) {
            this.rectMain.removeClass('invalid');
        } else {
            this.rectMain.addClass('invalid');
        }
    },

    remove: function () {
        if (this.rectMain !== null) {
            this.rectMain.remove();
            this.rectBefore.remove();
            this.rectAfter.remove();
            this.rectMain = null;
            this.rectBefore = null;
            this.rectAfter = null;
        }
    },

    // Hint: duplicate
    pixel: function (value) {
        return Math.round(value) + 'px';
    }
};
