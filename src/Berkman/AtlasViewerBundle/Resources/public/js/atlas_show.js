var map, switcherControl, bBoxFrozen = false;
$(function() {

    // Get the page "block" in which an element is contained 
    $.fn.getLayer = function() {
        return $(this[0]).closest('.layer-wrap');
    };

    // Try to avoid pink tiles
    OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;
    OpenLayers.Util.onImageLoadErrorColor = "transparent";
    OpenLayers.ImgPath = "http://js.mapbox.com/theme/dark/";

    // Create our variables
    var options = {
            controls: [],
            projection: new OpenLayers.Projection("EPSG:900913"),
            displayProjection: new OpenLayers.Projection("EPSG:4326"),
            units: "m",
            maxResolution: 156543.0339,
            maxExtent: new OpenLayers.Bounds(-20037508.3427892,-20037508.3427892,20037508.3427892,20037508.3427892), 
            numZoomLevels: maxZoom
        },
        gmap = new OpenLayers.Layer.Google("Google Streets", { sphericalMercator: true} ),
        ghyb = new OpenLayers.Layer.Google("Google Hybrid", {type: google.maps.MapTypeId.HYBRID}),
        layers = [gmap, ghyb],
        layer;

    map = new OpenLayers.Map('map', options);

    // Create layers out of the atlas's pages
    for ( i in pages ) {
        pages[i].oBounds = new OpenLayers.Bounds(
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
                            var mapBounds = pages[i].oBounds,
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
    map.addLayers(layers);

    // Add the feature layer which will serve as the page bounding box on hover
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
    map.setLayerIndex(featureLayer, 1000);

    // Add the layer switcher and make it visible by default
    switcherControl = new OpenLayers.Control.CustomLayerSwitcher({ roundedCornerColor: 'black' });
    map.addControl(switcherControl);
    switcherControl.maximizeControl();

    // Hide the metadata and page-specific controls by default
    $('#metadata, .layer-inside-wrap').hide();

    // Add all the other controls
    map.addControl(new OpenLayers.Control.PanZoomBar());
    map.addControl(new OpenLayers.Control.MousePosition());
    map.addControl(new OpenLayers.Control.MouseDefaults());
    map.addControl(new OpenLayers.Control.KeyboardDefaults());

    // Add the custom click handler so we can show which pages have been doubleclicked on
    var clickHandler = new OpenLayers.Control.ClickHandler();
    map.addControl(clickHandler);
    clickHandler.activate();

    // Zoom to the atlas extent by default
    map.zoomToExtent( new OpenLayers.Bounds( atlasBounds.miny, atlasBounds.minx, atlasBounds.maxy, atlasBounds.maxx ).transform(map.displayProjection, map.projection ), true );

    /**
     * Here come the event handlers
     */ 

    /* ---- Metadata handlers ----*/
    $('#metadata-toggle').click(function() {
        $('#metadata').slideToggle('fast');
        $(this).slideToggle('fast');
    });

    $('#metadata-close').click(function() {
        $('#metadata').slideToggle('fast', function() { $('#metadata-toggle').slideToggle('fast'); });
    });

    /* ---- Atlas-level handlers ---- */
    $('.olControlLayerSwitcher').mousewheel(function(e) { e.stopPropagation(); });
    $('.atlas-opacity-slider').slider({
        slide: function(e, ui) {
            $('.dataLayersDiv').find('.layer-opacity-slider').each(function() {
                $(this).slider('value', ui.value);
            });
        },
        value: 100,
        step: 10
    });

    $('.atlas-zoom').click(function(e) {
        map.zoomToExtent( new OpenLayers.Bounds( atlasBounds.miny, atlasBounds.minx, atlasBounds.maxy, atlasBounds.maxx ).transform(map.displayProjection, map.projection ), true );
        e.preventDefault();
    });

    $('.multi-layer-zoom').click(function(e) {
        var minx = null, miny = null, maxx = null, maxy = null;
        for( i in pages ) {
            if ( map.getLayer(pages[i].layerId).getVisibility() ) {
                if ( pages[i].bounds.minx < minx || minx === null ) {
                    minx = pages[i].bounds.minx;
                }
                if ( pages[i].bounds.miny < miny || miny === null ) {
                    miny = pages[i].bounds.miny;
                }
                if ( pages[i].bounds.maxx > maxx || maxx === null ) {
                    maxx = pages[i].bounds.maxx;
                }
                if ( pages[i].bounds.maxy < maxy || maxy === null ) {
                    maxy = pages[i].bounds.maxy;
                }
            }
        }
        map.zoomToExtent( new OpenLayers.Bounds( miny, minx, maxy, maxx ).transform(map.displayProjection, map.projection ), true );
        e.preventDefault();
    });

    $('.multi-layer-show').click(function(e) {
        for ( i in map.layers ) {
            if (!map.layers[i].isBaseLayer && map.layers[i].name != 'layerBBox') {
                map.layers[i].setVisibility(true);
                $('.layer-toggle').attr('checked', true);
            }
        }
        e.preventDefault();
    });

    $('.multi-layer-hide').click(function(e) {
        for ( i in map.layers ) {
            if (!map.layers[i].isBaseLayer && map.layers[i].name != 'layerBBox') {
                map.layers[i].setVisibility(false);
                $('.layer-toggle').attr('checked', false);
            }
        }
        e.preventDefault();
    });
    $('.dataLayersDiv').sortable({
        change: function(e, ui) {
            var $layer = $(ui.item).getLayer(),
                startIndex = $layer.data('startIndex'),
                newIndex = $layer.parent().children().not('.ui-sortable-helper').index($layer.parent().find('.ui-sortable-placeholder')),
                diff = startIndex - newIndex;
            //console.log('startIndex: ' + startIndex + ' - newIndex: ' + newIndex + ' - diff: ' + diff);
            $layer.data('startIndex', newIndex);
            map.raiseLayer(map.getLayer($layer.data('layerId')), diff);
        },
        start: function(e, ui) {
            var $layer = $(e.target).getLayer();
            $layer.data('startIndex', $layer.parent().children().index($layer));
            $layer.addClass('not-transparent');
            bBoxFrozen = true;
        },
        stop: function(e) {
            var $layer = $(e.target).getLayer();
            $layer.removeClass('not-transparent');
            bBoxFrozen = false;
        },
        handle: '.layer-handle',
        axis: 'y'
    });

    /* ---- Page-level handlers ---- */
    $('.layer-wrap').hover(
        function(e) {
            showBoundingBox($(e.target).getLayer().data('layerId'));
        },
        function() {
            hideBoundingBox();
        }
    );

    $('.layer-expand-control').click(function() {
        $(this).getLayer().find('.layer-inside-wrap').slideToggle();
        $(this).getLayer().find('.layer-expand-control').toggleClass('expanded-layer');
    });

    $('.layer-handle').dblclick(function() {
        $(this).getLayer().find('.layer-inside-wrap').slideToggle();
        $(this).getLayer().find('.layer-expand-control').toggleClass('expanded-layer');
    });

    $('.layer-zoom').click(function(e) {
        var i, bounds, layerId = $(this).getLayer().data('layerId');
        for (i in pages) {
            if (pages[i].layerId == layerId) {
                bounds = pages[i].oBounds;
            }
        }
        map.zoomToExtent(bounds, true);
        e.preventDefault();
    });

    $('.layer-raise').click(function(e) {
        var layerId = $(this).getLayer().data('layerId');
        $('.dataLayersDiv').prepend($(e.target).getLayer());
        map.setLayerIndex(map.getLayer(layerId), pages.length + 1);
    });

    $('.layer-opacity-slider').slider({
        slide: function(e, ui) {
            var layerId  = $(ui.handle).getLayer().data('layerId');
            map.getLayer(layerId).setOpacity(ui.value / 100);
        },
        change: function(e, ui) {
            var layerId  = $(ui.handle).getLayer().data('layerId');
            map.getLayer(layerId).setOpacity(ui.value / 100);
        },
        value: 100,
        step: 5
    });
});

function showBoundingBox(layerId) {
    if (!bBoxFrozen) {
        var bounds, i, featureLayer = map.getLayersByName("layerBBox")[0];
        featureLayer.removeAllFeatures();

        for (i in pages) {
            if (pages[i].layerId == layerId) {
                bounds = pages[i].oBounds;
            }
        }

        var box = new OpenLayers.Feature.Vector(bounds.toGeometry());
        featureLayer.addFeatures([box]);
    }
}

function hideBoundingBox() {
    if (!bBoxFrozen) {
        map.getLayersByName('layerBBox')[0].removeAllFeatures();
    }
}
