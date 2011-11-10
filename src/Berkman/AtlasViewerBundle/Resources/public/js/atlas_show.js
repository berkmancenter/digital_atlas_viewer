var map, switcherControl;
$(function() {
    $('#metadata').hide();
    $('html, body, #map').css({ margin: 0, padding: 0, width: '100%', height: '100%' });

    var atlasBounds = { minx: null, miny: null, maxx: null, maxy: null }, minZoom = null, maxZoom = null;

    // avoid pink tiles
    OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;
    OpenLayers.Util.onImageLoadErrorColor = "transparent";
    OpenLayers.ImgPath = "http://js.mapbox.com/theme/dark/";


    var options = {
        controls: [],
        projection: new OpenLayers.Projection("EPSG:900913"),
        displayProjection: new OpenLayers.Projection("EPSG:4326"),
        units: "m",
        maxResolution: 156543.0339,
        maxExtent: new OpenLayers.Bounds(-20037508.3427892,-20037508.3427892,20037508.3427892,20037508.3427892), 
    };

    map = new OpenLayers.Map('map', options);

    // create Google Mercator layers
    var gmap = new OpenLayers.Layer.Google("Google Streets", { sphericalMercator: true, numZoomLevels: 21} );

    var layers = [gmap];
    var layer;
    for ( i in pages ) {
        if ( pages[i].bounds.minx < atlasBounds.minx || atlasBounds.minx === null ) {
            atlasBounds.minx = pages[i].bounds.minx;
        }
        if ( pages[i].bounds.miny < atlasBounds.miny || atlasBounds.miny === null ) {
            atlasBounds.miny = pages[i].bounds.miny;
        }
        if ( pages[i].bounds.maxx > atlasBounds.maxx || atlasBounds.maxx === null ) {
            atlasBounds.maxx = pages[i].bounds.maxx;
        }
        if ( pages[i].bounds.maxy < atlasBounds.maxy || atlasBounds.maxy === null ) {
            atlasBounds.maxy = pages[i].bounds.maxy;
        }
        if ( pages[i].minZoom < minZoom || minZoom === null ) {
            minZoom = pages[i].minZoom;
        }
        if ( pages[i].maxZoom > maxZoom || maxZoom === null ) {
            maxZoom = pages[i].maxZoom;
        }

        pages[i].bounds = new OpenLayers.Bounds(
            pages[i].bounds.miny,
            pages[i].bounds.minx,
            pages[i].bounds.maxy,
            pages[i].bounds.maxx
        ).transform(map.displayProjection, map.projection);

        layer = new OpenLayers.Layer.TMS(
            pages[i].name,
            "",
            {
                type: 'png',
                alpha: true,
                transitionEffect: 'resize',
                isBaseLayer: false,
                getURL: function(bounds) {
                    var res = this.map.getResolution();
                    var x = Math.round((bounds.left - this.maxExtent.left) / (res * this.tileSize.w));
                    var y = Math.round((bounds.bottom - this.tileOrigin.lat) / (res * this.tileSize.h));
                    var z = this.map.getZoom();

                    for ( i in pages ) {
                        if (this.id == pages[i].layerId) {
                            var mapBounds = pages[i].bounds,
                                pageId = pages[i].id,
                                minZoom = pages[i].minZoom,
                                maxZoom = pages[i].maxZoom;
                        }
                    }

                    if (mapBounds.intersectsBounds( bounds ) && z >= minZoom && z <= maxZoom ) {
                        return this.url + '../../../tiles/' + atlasId + '/' + pageId + '/' + z + "/" + x + "/" + y + "." + this.type;
                    } else {
                        return "http://www.maptiler.org/img/none.png";
                    }
                }
            }
        );

        pages[i].layerId = layer.id;
        layers.push(layer);
    }

    //if (OpenLayers.Util.alphaHack() == false) { tmsoverlay.setOpacity(0.7); }

    map.addLayers(layers);
    var style_blue = OpenLayers.Util.extend({}, OpenLayers.Feature.Vector.style['default']);
    style_blue.strokeColor = "darkred";
    style_blue.fillColor = "darkred";
    style_blue.fillOpacity = .05;
    style_blue.pointRadius = 10;
    style_blue.strokeWidth = 2;
    style_blue.strokeLinecap = "butt";
    style_blue.zIndex = 999;

    var featureLayer = new OpenLayers.Layer.Vector("layerBBox", { style: style_blue, displayInLayerSwitcher: false });
    map.addLayer(featureLayer);

    switcherControl = new OpenLayers.Control.LayerSwitcher({ roundedCornerColor: 'black' });
    map.addControl(switcherControl);
    switcherControl.maximizeControl();

    $(switcherControl.dataLayers).each(function() {
        var layerId = this.layer.id;
        $(this.inputElem, this.layerSpan).hover(
            function() {
                showBoundingBox(layerId);
            },
            function() {
                hideBoundingBox();
            }
        );
    });

    map.zoomToExtent( new OpenLayers.Bounds( atlasBounds.miny, atlasBounds.minx, atlasBounds.maxy, atlasBounds.maxx ).transform(map.displayProjection, map.projection ) );

    map.addControl(new OpenLayers.Control.PanZoomBar());
    map.addControl(new OpenLayers.Control.MousePosition());
    map.addControl(new OpenLayers.Control.MouseDefaults());
    map.addControl(new OpenLayers.Control.KeyboardDefaults());

});
function showBoundingBox(layerId) {
    var bounds, i, featureLayer = map.getLayersByName("layerBBox")[0];
    featureLayer.removeAllFeatures();

    for (i in pages) {
        if (pages[i].layerId == layerId) {
            bounds = pages[i].bounds;
        }
    }

    var box = new OpenLayers.Feature.Vector(bounds.toGeometry());
    featureLayer.addFeatures([box]);

    map.setLayerIndex(featureLayer, i + 1);
}

function hideBoundingBox() {
    map.getLayersByName('layerBBox')[0].removeAllFeatures();
}
