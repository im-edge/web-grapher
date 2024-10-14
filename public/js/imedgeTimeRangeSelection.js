
const ImedgeTimeRangeSelection = function (graph) {
    this.graph = graph;
    this.start = null;
    this.current = null;
};

ImedgeTimeRangeSelection.prototype = {
    setPosition: function (timestamp) {
        let step = Math.round(this.graph.getDuration() / 100);
        step = this.graph.getStepSizeForDuration(this.graph.getDuration());
        // step = 300;
        if (this.start === null) {
            this.start = this.graph.normalizeRoundWithStep(parseInt(timestamp), step);
        }

        this.current = this.graph.normalizeRoundWithStep(parseInt(timestamp), step);
    },

    getStart: function () {
        return this.start;
    },

    getCurrent: function () {
        return this.current;
    },

    getBegin: function () {
        if (this.current > this.start) {
            return this.start;
        } else {
            return this.current;
        }
    },

    getEnd: function () {
        if (this.current > this.start) {
            return this.current;
        } else {
            return this.start;
        }
    },

    getDuration: function () {
        return this.getEnd() - this.getBegin();
    }
};
