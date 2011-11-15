var map, switcherControl;
$(function() {
    $.fn.getLayer = function() {
        return $(this[0]).closest('.layer-wrap');
    };

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
    var ghyb = new OpenLayers.Layer.Google("Google Hybrid", {type: google.maps.MapTypeId.HYBRID, numZoomLevels: 21});

    var layers = [gmap, ghyb];
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


    OpenLayers.Control.MyCustomLayerSwitcher =
        OpenLayers.Class(OpenLayers.Control.LayerSwitcher,{
            mouseUp: function(evt) {
                if (this.isMouseDown) {
                    this.isMouseDown = false;
             //     this.ignoreEvent(evt);
                }
            },
            checkRedraw: function() {
                var redraw = false;
                if ( !this.layerStates.length ||
                     (this.map.layers.length != this.layerStates.length) ) {
                    redraw = true;
                }
                return redraw;
            },
            redraw: function (){
                //if the state hasn't changed since last redraw, no need 
                // to do anything. Just return the existing div.
                if (!this.checkRedraw()) { 
                    return this.div; 
                } 

                //clear out previous layers 
                this.clearLayersArray("base");
                this.clearLayersArray("data");
                
                var containsOverlays = false;
                var containsBaseLayers = false;
                
                // Save state -- for checking layer if the map state changed.
                // We save this before redrawing, because in the process of redrawing
                // we will trigger more visibility changes, and we want to not redraw
                // and enter an infinite loop.
                var len = this.map.layers.length;
                this.layerStates = new Array(len);
                for (var i=0; i <len; i++) {
                    var layer = this.map.layers[i];
                    this.layerStates[i] = {
                        'name': layer.name, 
                        'visibility': layer.visibility,
                        'inRange': layer.inRange,
                        'id': layer.id
                    };
                }    

                var layers = this.map.layers.slice();
                if (!this.ascending) { layers.reverse(); }
                for(var i=0, len=layers.length; i<len; i++) {
                    var layer = layers[i];
                    var baseLayer = layer.isBaseLayer;

                    if (layer.displayInLayerSwitcher) {

                        if (baseLayer) {
                            containsBaseLayers = true;
                        } else {
                            containsOverlays = true;
                        }    

                        // only check a baselayer if it is *the* baselayer, check data
                        //  layers if they are visible
                        var checked = (baseLayer) ? (layer == this.map.baseLayer)
                                                  : layer.getVisibility();
            
                        // create input element
                        var inputElem = document.createElement("input");
                        inputElem.id = this.id + "_input_" + layer.name;
                        inputElem.name = (baseLayer) ? this.id + "_baseLayers" : layer.name;
                        inputElem.type = (baseLayer) ? "radio" : "checkbox";
                        inputElem.value = layer.name;
                        inputElem.checked = checked;
                        inputElem.defaultChecked = checked;

                        if (!baseLayer && !layer.inRange) {
                            inputElem.disabled = true;
                        }
                        var context = {
                            'inputElem': inputElem,
                            'layer': layer,
                            'layerSwitcher': this
                        };
                        OpenLayers.Event.observe(inputElem, "mouseup", 
                            OpenLayers.Function.bindAsEventListener(this.onInputClick,
                                                                    context)
                        );
                        
                        // create span
                        var labelSpan = document.createElement("span");
                        OpenLayers.Element.addClass(labelSpan, "labelSpan");
                        if (!baseLayer && !layer.inRange) {
                            labelSpan.style.color = "gray";
                        }
                        labelSpan.innerHTML = layer.name;
                        labelSpan.style.verticalAlign = (baseLayer) ? "bottom" 
                                                                    : "baseline";
                        OpenLayers.Event.observe(labelSpan, "click", 
                            OpenLayers.Function.bindAsEventListener(this.onInputClick,
                                                                    context)
                        );
                        // create line break
                        var br = document.createElement("br");
            
                        
                        var groupArray = (baseLayer) ? this.baseLayers
                                                     : this.dataLayers;
                        groupArray.push({
                            'layer': layer,
                            'inputElem': inputElem,
                            'labelSpan': labelSpan
                        });
                                                             
            
                        var groupDiv = (baseLayer) ? this.baseLayersDiv
                                                   : this.dataLayersDiv;

                        if (baseLayer) {
                            groupDiv.appendChild(inputElem);
                            groupDiv.appendChild(labelSpan);
                            groupDiv.appendChild(br);
                        } else {
                            $(inputElem).addClass('layer-toggle');
                            $(groupDiv).append(
                                $('<div class="layer-wrap" id="' + layer.id + '-wrap" />')
                                .data('layerId', layer.id)
                                .html(
                                    $('<div class="layer-handle"/>')
                                    .append(
                                        $(inputElem).after(labelSpan)
                                    )
                                )
                                .append(
                                    $('<div class="layer-inside-wrap" />')
                                    .html('<div class="layer-opacity-slider"/>')
                                    .append('<a class="layer-zoom" href="#"><img src="/DAV/web/bundles/berkmanatlasviewer/images/magnifying_glass_alt_12x12.png" /></a>')
                                    .append('<a class="layer-raise" href="#"><img src="/DAV/web/bundles/berkmanatlasviewer/images/upload_6x12.png" /></a>')
                                )
                            );
                        }
                    }
                }

                // if no overlays, dont display the overlay label
                this.dataLbl.style.display = (containsOverlays) ? "" : "none";        
                
                // if no baselayers, dont display the baselayer label
                this.baseLbl.style.display = (containsBaseLayers) ? "" : "none";        

                var tempLayers = $(this.dataLayersDiv);
                tempLayers.children().each(function(i,li){tempLayers.prepend(li)});

                $(this.div).find('.layersDiv').append($(this.div).find('.baseLbl, .baseLayersDiv'));
                $(this.div).find('.layersDiv').prepend(
                    '<div class="atlasLbl">Atlas</div>' + 
                    '<div class="atlasButtonsDiv">' + 
                        '<a href="#" title="Zoom to entire atlas" class="atlas-zoom"><img src="/DAV/web/bundles/berkmanatlasviewer/images/magnifying_glass_alt_12x12.png"/></a>' +
                        '<a href="#" title="Zoom to visible pages" class="multi-layer-zoom"><img src="/DAV/web/bundles/berkmanatlasviewer/images/magnifying_glass_alt_12x12.png"/></a>' +
                        '<a href="#" title="Hide all pages" class="multi-layer-hide"><img src="/DAV/web/bundles/berkmanatlasviewer/images/layers_12x11.png"/></a>' +
                        '<a href="#" title="Show all pages" class="multi-layer-show"><img src="/DAV/web/bundles/berkmanatlasviewer/images/layers_12x11.png"/></a>' +
                        '<div class="atlas-opacity-slider"/>' +
                    '</div>'
                );
                $(this.dataLbl).html('Pages');
                
                $(this.dataLayersDiv).find('.layer-wrap').hover(
                    function(e) {
                        showBoundingBox($(e.target).getLayer().data('layerId'));
                    },
                    function() {
                        hideBoundingBox();
                    }
                );

                $(this.dataLayersDiv).find('.layer-opacity-slider').slider({
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

                $(this.div).find('.atlas-opacity-slider').slider({
                    slide: function(e, ui) {
                        $('.dataLayersDiv').find('.layer-opacity-slider').each(function() {
                            $(this).slider('value', ui.value);
                        });
                    },
                    value: 100,
                    step: 10
                });

                $(this.div).find('.atlas-zoom').click(function(e) {
                    map.zoomToExtent( new OpenLayers.Bounds( atlasBounds.miny, atlasBounds.minx, atlasBounds.maxy, atlasBounds.maxx ).transform(map.displayProjection, map.projection ), true );
                    e.preventDefault();
                });

                $(this.div).find('.multi-layer-zoom').click(function(e) {
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

                $(this.div).find('.multi-layer-show').click(function(e) {
                    for ( i in map.layers ) {
                        if (!map.layers[i].isBaseLayer && map.layers[i].name != 'layerBBox') {
                            map.layers[i].setVisibility(true);
                            $('.layer-toggle').attr('checked', true);
                        }
                    }
                    e.preventDefault();
                });

                $(this.div).find('.multi-layer-hide').click(function(e) {
                    for ( i in map.layers ) {
                        if (!map.layers[i].isBaseLayer && map.layers[i].name != 'layerBBox') {
                            map.layers[i].setVisibility(false);
                            $('.layer-toggle').attr('checked', false);
                        }
                    }
                    e.preventDefault();
                });

                $(this.dataLayersDiv).find('.layer-opacity-slider').each(function() {
                    $(this).slider('value', (map.getLayer($(this).getLayer().data('layerId')).opacity || 1) * 100);
                });

                $(this.dataLayersDiv).find('.layer-zoom').click(function(e) {
                    var i, bounds, layerId = $(this).getLayer().data('layerId');
                    for (i in pages) {
                        if (pages[i].layerId == layerId) {
                            bounds = pages[i].oBounds;
                        }
                    }
                    map.zoomToExtent(bounds, true);
                    e.preventDefault();
                });

                $(this.dataLayersDiv).find('.layer-raise').click(function(e) {
                    var layerId = $(this).getLayer().data('layerId');
                    $('.dataLayersDiv').prepend($(e.target).getLayer());
                    map.setLayerIndex(map.getLayer(layerId), pages.length + 1);
                });

                return this.div;

            },
           CLASSNAME: "OpenLayers.Control.MyCustomLayerSwitcher"
        })
    ;

    switcherControl = new OpenLayers.Control.MyCustomLayerSwitcher({ roundedCornerColor: 'black' });
    map.addControl(switcherControl);
    switcherControl.maximizeControl();


    OpenLayers.Control.TestClick = OpenLayers.Class(OpenLayers.Control, {                
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

    map.zoomToExtent( new OpenLayers.Bounds( atlasBounds.miny, atlasBounds.minx, atlasBounds.maxy, atlasBounds.maxx ).transform(map.displayProjection, map.projection ), true );

    map.addControl(new OpenLayers.Control.PanZoomBar());
    map.addControl(new OpenLayers.Control.MousePosition());
    map.addControl(new OpenLayers.Control.MouseDefaults());
    map.addControl(new OpenLayers.Control.KeyboardDefaults());
    var testClick = new OpenLayers.Control.TestClick();
    map.addControl(testClick);
    testClick.activate();

    $('.dataLayersDiv').sortable({
        change: function(e, ui) {
            var $layer = $(e.target).getLayer(),
                startIndex = $layer.data('startIndex'),
                newIndex = $layer.parent().children().not('.ui-sortable-helper').index($layer.parent().find('.ui-sortable-placeholder')),
                diff = startIndex - newIndex;
            console.log('startIndex: ' + startIndex + ' - newIndex: ' + newIndex + ' - diff: ' + diff);
            $layer.data('startIndex', newIndex);
            map.raiseLayer(map.getLayer($layer.data('layerId')), diff);
        },
        start: function(e, ui) {
            var $layer = $(e.target).getLayer();
            $layer.data('startIndex', $layer.parent().children().index($layer));
            $layer.addClass('not-transparent');
        },
        stop: function(e) {
            var $layer = $(e.target).getLayer();
            $layer.removeClass('not-transparent');
        },
        handle: '.layer-handle',
        axis: 'y'
    });
});
function showBoundingBox(layerId) {
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

function hideBoundingBox() {
    map.getLayersByName('layerBBox')[0].removeAllFeatures();
}
