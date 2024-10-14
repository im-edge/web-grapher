const ImedgeGraphCursor = function () {
    this.timestamp = null;
    this.visible = false;
};

ImedgeGraphCursor.prototype = {
    isVisible: function () {
        return this.visible;
    },

    isHidden: function () {
        return ! this.visible;
    },

    show: function (timestamp) {
        if (typeof timestamp !== 'undefined') {
            this.timestamp = timestamp;
        }
        this.visible = true;
    },

    hide: function () {
        this.visible = false;
    },

    getTimestamp: function () {
        return this.timestamp;
    }
};
