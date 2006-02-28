/*
  Author: Oliver Steele
  Copyright: Copyright 2006 Oliver Steele.  All rights reserved.
  Homepage: http://osteele.com/sources/javascript
  License: MIT License.
  
  == Overview
  TextCanvasController provides an API similar to that of the
  WHATWG +canvas+ element, but with the addition of a +drawString()+ method.
  +drawString()+ which gives the appearance of rendering a string on
  the canvas surface, although it is actually implemented by creating
  an HTML element that is absolutely positioned within the canvas's
  container.
  
  For example:
    // <div id="canvasContainer"></div>
    var container = document.getElementById('canvasContainer');
    var textCanvasController = new TextCanvasController(container);
    var ctx = textCanvasController.getContext('2d');
    ctx.moveTo(0, 0);
    ctx.lineTo(100, 100);
    ctx.stringStyle.color = 'red';
    ctx.drawString(0, 0, "red");
    ctx.stringStyle.color = 'blue';
    ctx.drawString(100, 100, "blue");
  
  There is a live standalone example at
  http://osteele.com/sources/javascript/textcanvas-demo.html.
  
  This library is only known to work in Firefox.  It is known
  not to work in Safari.  The {OpenLaszlo version}[http://osteele.com/sources/openlaszlo/textdrawview-demo.swf] is cross-browser (even Internet Explorer).
    
  == API
  === TextCanvasController
  ==== <tt>var textCanvasController = new TextCanvasController(container)</tt>
  Create a virtual "text canvas" within +container+ is an HTML div.
  
  ==== <tt>textCanvasController.setDimension(width, height)</tt>
  Set the width and height of the canvas.
  
  ==== <tt>context = textCanvasController.getContext('2d')</tt>
  Returns a 2D context, modified to accept the following methods:
    
  === TextCanvas context
  ==== <tt>context.drawString(x, y, string)</tt>
  Draw string at (x, y), with the font and text style properties
  specified in context.style (below).

  ==== <tt>context.erase()</tt>
  Erase the content of the canvas.  This is equivalent to
  <tt>context.clearRect(0, 0, canvas.width, canvas.height)</tt>,
  except that it also removes any strings created by
  context.drawString().
  
  ==== <tt>context.style</tt>
  An instance of ElementCSSInlineStyle.  Calls to drawString
  use the font and text properties in this style object.  (This
  API is analogous to the stateful mechanism that the 2d context
  provides for setting stroke and fill properties.)
    
  This implementation uses the container's style object
  for this.  This won't have any effect if you only set the
  font and style properties, but will have surprising results
  if you set other properties.
      
  == Known Bugs
  This has only been tested under Firefox.  It is known not
  to work in Safari.
  
  The strings are implemented as HTML divs, which are
  positioned absolutely in front of the canvas.  They
  therefore don't behave exactly as though they were on
  the canvas:
  - they don't respect the current transform
  - they don't respect 
  - nontext elements that are drawn subsequent to
    a string will be positioned over it, not under it
  (This last point could be fixed.  See the blog entry
  at http://osteele.com/archives/2006/02/textcanvas.)
    
  == Related Work
  There is also an version of this library for OpenLaszlo.
  Its home page is http://osteele.com/sources/openlaszlo/textdrawview,
  and there is a live example {here}[http://osteele.com/sources/openlaszlo/textdrawview-demo.swf].
*/

function TextCanvasController(container) {
    this.container = container;
	if (!container.style.position)
		container.style.position = 'relative';
    var canvas = document.createElement('canvas');
    this.canvas = canvas;
	canvas.style.position = 'absolute';
    container.appendChild(canvas);
    this.labels = [];
}

// Font and text properties.  These are applied to strings that are
// rendered with drawString.
TextCanvasController.CSSStringProperties = 'color direction fontFamily fontSize fontSizeAdjust fontStretch fontStyle fontVariant fontWeight letterSpacing lineHeight textAlign textDecoration textIndent textShadow textTransform unicodeBidi whiteSpace wordSpacing'.split(' ');

TextCanvasController.prototype.getContext = function(contextID) {
   	var ctx = this.canvas.getContext(contextID);
	if (contextID == '2d')
		this.attachMethods(ctx, this);
	return ctx;
};

TextCanvasController.prototype.setDimensions = function(width, height) {
	var container = this.container;
	var canvas = this.canvas;
	this.container.style.width = canvas.width = width;
	this.container.style.height = canvas.height = height;
}

TextCanvasController.prototype.clear = function() {
    var canvas = this.canvas;
	var ctx = canvas.getContext("2d");
	ctx.clearRect(0, 0, canvas.width, canvas.height);
	for (var i = 0; i < this.labels.length; i++)
		this.container.removeChild(this.labels[i]);
	this.labels = [];
};

TextCanvasController.prototype.attachMethods = function(ctx, controller) {
	ctx.drawString = function(x, y, string) {
		controller.addLabel(x, y, string);
	};
	
	ctx.clear = function () {
		controller.clear();
	};
    
    ctx.stringStyle = controller.container.style;
};

TextCanvasController.prototype.addLabel = function(x, y, string) {
	var label = document.createElement('div');
	var text = document.createTextNode(string);
	label.appendChild(text);
    var style = this.container.style;
    var cssNames = TextCanvasController.CSSStringProperties;
    for (var i = 0; i < cssNames.length; i++) {
        var name = cssNames[i];
        label.style[name] = style[name];
    }
	label.style.position = 'absolute';
	label.style.left = x;
	label.style.top = y;
	this.container.appendChild(label);
	this.labels.push(label);
}