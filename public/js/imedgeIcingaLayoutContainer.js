/**
 * We create a container instance for every Icinga Web 2
 *
 * Only task: notify about changed width
 *
 * @param $container
 * @constructor
 */
const ImedgeIcingaLayoutContainer = function ($container) {
    this.$container = $container;
    this.width = null;
};

ImedgeIcingaLayoutContainer.prototype = {
    checkForChangedWidth: function (handler) {
        const $col = this.$container;
        let width = null;

        if ($col.css('display') !== 'none') {
            width = $col.width();
        }

        if (this.width !== width) {
            this.width = width;
            handler($col, width);
        }
    },
};
