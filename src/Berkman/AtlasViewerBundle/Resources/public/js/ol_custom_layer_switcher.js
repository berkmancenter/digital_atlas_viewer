OpenLayers.Control.CustomLayerSwitcher =
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
                                    $(inputElem).after(labelSpan).after('<div class="layer-expand-control"></div>')
                                )
                            )
                            .append(
                                $('<div class="layer-inside-wrap" />')
                                .html('Opacity:<div class="layer-opacity-slider"/>')
                                .append('<a class="layer-zoom atlas-control" href="#" title="Zoom to Page"><img src="/DAV/web/bundles/berkmanatlasviewer/images/fullscreen_12x12.png" alt="Zoom to Page"/></a>')
                                .append('<a class="layer-raise atlas-control" href="#" title="Put layer on top"><img src="/DAV/web/bundles/berkmanatlasviewer/images/upload_6x12.png" alt="Put layer on top"/></a>')
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
                    '<div class="atlas-opacity-slider"/>' +
                    '<a href="#" title="Zoom to entire atlas" class="atlas-zoom atlas-control"><img src="/DAV/web/bundles/berkmanatlasviewer/images/fullscreen_12x12.png"/></a>' +
                    '<a href="#" title="Zoom to visible pages" class="multi-layer-zoom atlas-control"><img src="/DAV/web/bundles/berkmanatlasviewer/images/magnifying_glass_alt_12x12.png"/></a>' +
                    '<a href="#" title="Hide all pages" class="multi-layer-hide atlas-control"><img src="/DAV/web/bundles/berkmanatlasviewer/images/layers_12x11.png"/></a>' +
                    '<a href="#" title="Show all pages" class="multi-layer-show atlas-control"><img src="/DAV/web/bundles/berkmanatlasviewer/images/layers_12x11.png"/></a>' +
                '</div>'
            );
            $(this.dataLbl).html('Pages');
            

            return this.div;

        },
       CLASSNAME: "OpenLayers.Control.MyCustomLayerSwitcher"
    })
;
