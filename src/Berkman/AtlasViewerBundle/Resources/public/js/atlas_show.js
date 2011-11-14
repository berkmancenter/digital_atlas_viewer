var map, switcherControl;
$(function() {
    $.fn.getLayer = function() {
        return $(this[0]).closest('.layer-wrap');
    };

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

    OpenLayers.Control.MyCustomLayerSwitcher =
        OpenLayers.Class(OpenLayers.Control.LayerSwitcher,{
            mouseUp: function(evt) {
                if (this.isMouseDown) {
                    this.isMouseDown = false;
             //     this.ignoreEvent(evt);
                }
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
                            $(groupDiv).append($('<div class="layer-wrap" id="' + layer.id + '-wrap" />').data('layerId', layer.id).append('<div class="layer-handle"/>').append($(inputElem).after(labelSpan).after('<div class="layer-opacity-slider"/>')));
                        }
                    }
                }

                // if no overlays, dont display the overlay label
                this.dataLbl.style.display = (containsOverlays) ? "" : "none";        
                
                // if no baselayers, dont display the baselayer label
                this.baseLbl.style.display = (containsBaseLayers) ? "" : "none";        

                var tempLayers = $(this.dataLayersDiv);
                tempLayers.children().each(function(i,li){tempLayers.prepend(li)});
                
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
                        var layerId  = $(ui.handle).parent().parent().data('layerId');
                        map.getLayer(layerId).setOpacity(ui.value / 100);
                    },
                    value: 100,
                    step: 5
                });

                $(this.dataLayersDiv).find('.layer-opacity-slider').each(function() {
                    $(this).slider('value', (map.getLayer($(this).getLayer().data('layerId')).opacity || 1) * 100);
                });

                return this.div;

            },
           CLASSNAME: "OpenLayers.Control.MyCustomLayerSwitcher"
        })
    ;

    switcherControl = new OpenLayers.Control.MyCustomLayerSwitcher({ roundedCornerColor: 'black' });
    map.addControl(switcherControl);
    switcherControl.maximizeControl();


    map.zoomToExtent( new OpenLayers.Bounds( atlasBounds.miny, atlasBounds.minx, atlasBounds.maxy, atlasBounds.maxx ).transform(map.displayProjection, map.projection ) );

    map.addControl(new OpenLayers.Control.PanZoomBar());
    map.addControl(new OpenLayers.Control.MousePosition());
    map.addControl(new OpenLayers.Control.MouseDefaults());
    map.addControl(new OpenLayers.Control.KeyboardDefaults());

    $('.dataLayersDiv').sortable({
        stop: function(e, ui) {
            var $layer = $(e.target).getLayer(),
                diff = $layer.data('startIndex') - $layer.parent().children().index($layer) + 1;
            diff = diff > 0 ? diff - 1 : diff;
            map.raiseLayer(map.getLayer($layer.data('layerId')), diff);
        },
        start: function(e, ui) {
            var $layer = $(e.target).getLayer();
            $layer.data('startIndex', $layer.parent().children().index($layer));
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
            bounds = pages[i].bounds;
        }
    }

    var box = new OpenLayers.Feature.Vector(bounds.toGeometry());
    featureLayer.addFeatures([box]);

    //map.setLayerIndex(featureLayer, pages.length);
}

function hideBoundingBox() {
    map.getLayersByName('layerBBox')[0].removeAllFeatures();
}
