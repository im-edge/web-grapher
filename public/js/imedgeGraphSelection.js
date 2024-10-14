const ImedgeGraphSelection = function (graph) {
    this.graph = graph;
    this.tplSelection = '<div class="rrd-selection"> </div>';
    this.tplBefore = '<div class="rrd-time-not-selection selection-before" style="left: 0"> </div>';
    this.tplMain   = '<div class="rrd-time-selection"> </div>';
    this.tplAfter  = '<div class="rrd-time-not-selection selection-after" style="right: 0"> </div>';
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

    getSelection: function() {
        if (typeof(this.$selection) === 'undefined' || this.$selection === null) {
            const $graph = this.graph.$canvas;
            let $selection = $graph.find('.rrd-selection');
            if ($selection.length === 0) {
                $selection = $(this.tplSelection);
                $graph.prepend($selection);
                $selection.css({
                    top: pixel(this.graph.translatePosition(this.graph.getTop())),
                    left: pixel(this.graph.translatePosition(this.graph.getLeft())),
                    height: pixel(this.graph.translatePosition(this.graph.getHeight())),
                    width: pixel(this.graph.translatePosition(this.graph.getWidth()))
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
            'width': pixel(this.graph.translatePosition(left))
        });
        this.rectMain.css({
            'left': pixel(this.graph.translatePosition(left)),
            'width': pixel(this.graph.translatePosition(right - left))
        });
        this.rectAfter.css({
            'width': pixel(this.graph.translatePosition(this.graph.getWidth() - right))
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
    }
};
