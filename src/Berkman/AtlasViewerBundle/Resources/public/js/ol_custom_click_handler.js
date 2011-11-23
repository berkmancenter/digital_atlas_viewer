OpenLayers.Control.ClickHandler = OpenLayers.Class(OpenLayers.Control, {                
    defaultHandlerOptions: {
        'single': false,
        'double': true,
        'pixelTolerance': 0,
        'stopSingle': false,
        'stopDouble': true
    },

    initialize: function(options) {
        this.handlerOptions = OpenLayers.Util.extend(
            {}, this.defaultHandlerOptions
        );
        OpenLayers.Control.prototype.initialize.apply(
            this, arguments
        ); 
        this.handler = new OpenLayers.Handler.Click(
            this, {
                'dblclick': this.onClick 
            }, this.handlerOptions
        );
    }, 

    onClick: function(e) {
        var lonLat = map.getLonLatFromViewPortPx(e.xy), popup, layerIds = [], $html = $('<p>Pages here:</p><ul/>');
        for ( i in pages ) {
            if (pages[i].oBounds.containsLonLat(lonLat)) {
                $html.append('<li>' + map.getLayer(pages[i].layerId).name + '</li>');
            }
        }

        popup = new OpenLayers.Popup(null, lonLat, new OpenLayers.Size(100, 100), $html.html(), true);
        popup.autosize = true;
        map.addPopup(popup, true);
    },
});
