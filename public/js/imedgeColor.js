const ImedgeColor = function (color) {
    this.color = ImedgeColor.getRgb(color);
};
ImedgeColor.getRgb = function (color) {
    let r, b, g;
    if (color.match(/^rgb/)) {
        color = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/);
        r = color[1];
        g = color[2];
        b = color[3];
    } else {
        color = +('0x' + color.slice(1).replace(
            color.length < 5 && /./g, '$&$&'
        ));

        r = color >> 16;
        g = color >> 8 & 255;
        b = color & 255;
    }

    return [r, g, b];
}

ImedgeColor.getHsp = function (r, g, b) {
    // http://alienryderflex.com/hsp.html
    return Math.sqrt(
        0.299 * (r * r) +
        0.587 * (g * g) +
        0.114 * (b * b)
    );
}

ImedgeColor.prototype = {
    isLight: function () {
        return ImedgeColor.getHsp(this.color[0], this.color[1], this.color[2]) > 127.5;
    },
    isDark: function () {
        return ImedgeColor.getHsp(this.color[0], this.color[1], this.color[2]) <= 127.5;
    }
};
