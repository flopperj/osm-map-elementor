L.Icon.FontAwesome = L.Icon.extend({
    markerContainer: null,
    markerSvg: null,
    iconContainer: null,
    icon: null,

    options: {
        popupAnchor: [0, -50]
    },

    /*------------------------------- LEAFLET -------------------------------*/

    createIcon: function () {
        const div = document.createElement("div");
        const options = this.options;

        if (options.iconClasses) {
            div.appendChild(this._createIcon());
        }

        return div;
    },

    /*------------------------------- PUBLIC -------------------------------*/

    setStyle: function (style) {
        Object.assign(this.options, style);
        const path = this.markerSvg.getElementsByTagName("path")[0];
        const icon = this.markerContainer.getElementsByTagName("i")[0];
        if (style.hasOwnProperty("markerColor"))
            path.setAttribute("fill", style.markerColor);
        if (style.hasOwnProperty("markerFillOpacity"))
            path.setAttribute("fill-opacity", style.markerFillOpacity);
        if (style.hasOwnProperty("markerStrokeColor"))
            path.setAttribute("stroke", style.markerStrokeColor);
        if (style.hasOwnProperty("markerStrokeWidth"))
            path.setAttribute("stroke-width", style.markerStrokeWidth);
        if (style.hasOwnProperty("iconXOffset"))
            icon.style.marginLeft = `${style.iconXOffset}px`;
        if (style.hasOwnProperty("iconYOffset"))
            icon.style.marginTop = `${style.iconYOffset}px`;
        if (style.hasOwnProperty("iconColor"))
            icon.style.color = style.iconColor;
        if (style.hasOwnProperty("iconSize"))
            icon.style.fontSize = `${style.iconSize}px`;
    },

    /*------------------------------- PRIVATE -------------------------------*/

    _createIcon: function () {
        const options = this.options;

        // container div
        this.markerContainer = L.DomUtil.create("div", "leaflet-fa-markers");

        // icon container div
        this.iconContainer = L.DomUtil.create("div", 'icon-container');

        // feature icon
        this.icon = L.DomUtil.create(
            "i",
            options.iconClasses + " feature-icon"
        );
        this.icon.style.color = options.iconColor;
        this.icon.style.textAlign = "center";

        // XY position adjustments
        if (options.iconYOffset && options.iconYOffset != 0)
            this.icon.style.marginTop = options.iconYOffset + "px";
        if (options.iconXOffset && options.iconXOffset != 0)
            this.icon.style.marginLeft = options.iconXOffset + "px";

        // set icon size
        if (options.iconSize && options.iconSize !== 0)
            this.icon.style.fontSize = `${options.iconSize}px`;

        // marker styles
        const fillOpacity = options.markerFillOpacity || 1;
        const strokeColor = options.markerStrokeColor || options.markerColor;
        const strokeWidth = options.markerStrokeWidth || 1;

        // marker icon L.DomUtil doesn't seem to like svg, just append out html directly
        this.markerSvg = document.createElement("div");
        this.markerSvg.className = "marker-icon-svg";
        this.markerSvg.innerHTML =
            "<svg " +
            'width="32px" ' +
            'height="52px" ' +
            'viewBox="0 0 32 52" ' +
            'version="1.1" ' +
            'xmlns="http://www.w3.org/2000/svg" ' +
            'xmlns:xlink="http://www.w3.org/1999/xlink">' +
            `<path d="${options.markerPath}"` +
            `fill-opacity="${fillOpacity}"` +
            `fill="${options.markerColor}"` +
            `stroke="${strokeColor}"` +
            `stroke-width="${strokeWidth}"` +
            "></path></svg>";

        this.iconContainer.appendChild(this.icon)
        this.markerContainer.appendChild(this.markerSvg);
        this.markerContainer.appendChild(this.iconContainer);
        return this.markerContainer;
    }
});

L.icon.fontAwesome = function (options) {
    return new L.Icon.FontAwesome(options);
};

L.Icon.FontAwesome.prototype.options.markerPath =
    "M16,1 C7.7146,1 1,7.65636364 1,15.8648485 C1,24.0760606 16,51 16,51 C16,51 31,24.0760606 31,15.8648485 C31,7.65636364 24.2815,1 16,1 L16,1 Z";
