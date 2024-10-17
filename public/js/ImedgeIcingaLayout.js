/**
 * ImedgeIcingaLayout is responsible for...
 */
const ImedgeIcingaLayout = function () {
    this.containers = [];
    this.callbacks = [];
    this.callbackHandler = null;
    this.initialize();
};

ImedgeIcingaLayout.prototype = {
    /**
     * @internal
     */
    initialize: function () {
        console.log('Main IMEdge layout handler loaded');
        const _this = this;
        this.callbackHandler = this.notifyChange.bind(this);
        // TODO: dynamic container detection
        this.initializeContainer($('#col1'));
        this.initializeContainer($('#col2'));
        $(window).on('resize', this.checkForChangedWidth.bind(_this));
        this.checkForChangedWidth(false); // TODO: Check, whether this fires on load
        // Hint -> should we should trigger a reload for sent/embedded images?
    },

    onChangedWidth: function (callback) {
        this.callbacks.push(callback);
    },

    /**
     * @internal
     */
    initializeContainer: function ($container) {
        this.containers.push(new ImedgeIcingaLayoutContainer($container));
    },

    /**
     * @internal
     */
    checkForChangedWidth: function () {
        const handler = this.callbackHandler;
        $.each(this.containers, function (idx, container) {
            container.checkForChangedWidth(handler);
        });
    },

    /**
     * @internal
     */
    notifyChange: function ($container, width) {
        $.each(this.callbacks, function (idx, callback) {
            callback($container, width);
        });
    }
};
