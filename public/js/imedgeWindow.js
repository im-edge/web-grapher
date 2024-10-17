const ImedgeWindow = function () {
    this.defaultBackground = this.getDefaultBackground();
    this.bgElement = $('#col1')[0];
    this.backgroundScheme = this.detectBackgroundScheme();
    this.colorScheme = this.detectColorScheme();
    const _this = this;
    if (typeof window.matchMedia === 'function') {
        window
            .matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', e => e.matches && _this.darkModeActivated());
        window.matchMedia('(prefers-color-scheme: light)')
            .addEventListener('change', e => e.matches && _this.lightModeActivated());
    }
};

ImedgeWindow.prototype = {
    detectBackgroundScheme: function () {
        return this.backgroundIsDark() ? 'dark' : 'light';
    },
    detectColorScheme: function () {
        if (typeof window.matchMedia === 'function') {
            if (window.matchMedia('(prefers-color-scheme: light)').matches) {
                return 'light';
            }
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
        }

        return null;
    },
    backgroundIsDark: function () {
        const color = new ImedgeColor(this.getInheritedBackgroundColor(this.bgElement));
        return color.isDark();
    },
    backgroundIsLight: function () {
        const color = new ImedgeColor(this.getInheritedBackgroundColor(this.bgElement));
        return color.isLight();
    },
    getInheritedBackgroundColor: function (el) {
        let bgColor = window.getComputedStyle(el).backgroundColor
        if (bgColor !== this.defaultBackground) {
            return bgColor;
        }

        if (!el.parentElement) {
            return this.defaultBackground;
        }

        return this.getInheritedBackgroundColor(el.parentElement)
    },
    getDefaultBackground: function () {
        // usually returns 'rgba(0, 0, 0, 0)'
        const div = document.createElement('div');
        document.head.appendChild(div)
        const bg = window.getComputedStyle(div).backgroundColor
        document.head.removeChild(div)
        return bg
    },
    darkModeActivated: function () {
        this.backgroundScheme = this.detectBackgroundScheme();
        this.colorScheme = 'dark';
    },
    lightModeActivated: function () {
        this.backgroundScheme = this.detectBackgroundScheme();
        this.colorScheme = 'light';
    }
};
