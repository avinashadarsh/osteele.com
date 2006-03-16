/*
  Author: Oliver Steele
  Copyright: Copyright 2006 Oliver Steele.  All rights reserved.
  Homepage: http://osteele.com/sources/javascript
  License: MIT License.
  
  This file adds a user-extensible style mechanism, that parallels CSS
  styles but can contain properties that are not in the CSS standard.
  
  When this file is loaded, <div> tags in the HTML document that have
  a class of "style" can contain (a subset of) CSS, but with
  nonstandard property names.  Each element that is selected by
  a "div CSS" rule has a .divStyle property.  The value of this
  property is a map of property names to values.
  
  See the http://osteele.com/sources/javascript/gradients.js library
  for an example of how this is used.
  
  == Usage
    <html>
      <-- include the style mechanism -->
      <head>
        <script type="text/javascript" src="behaviour.js"></script>
        <script type="text/javascript" src="divstyle.js"></script>
      </head>
      <body>
        <!-- define the styles: -->
        <div class="style">
          #e1 {my-property: 'string', other-property: 123}
          .myclass {prop2: #ff0000}
        </div>
        
        <!-- define the elements.  The styles are applied to these. -->
        <div id="e1"></div>
        <div id="e2" class="myclass"></div>
        <div id="e3" class="myclass"></div>
        
        <!-- now you can access the styles from JavaScript -->
        <script type="text/javascript">
          alert(document.getElementById('e1').divStyle.myProperty);
          alert(document.getElementById('e2').divStyle.prop1);
          alert(document.getElementById('e3').divStyle.prop2);
        </script>
       </body>
     </html>
  
  == Caveats
  You can't put the style content in comments (because Safari strips these
  before JavaScript can see them).
  
  CSS selectors are limited to what behaviour.js can parse, plus
  disjunctions such as "#my-id, .my-class, p".
  
  CSS simple selectors are limited to at most one modifier ("div.c1", but not
  "div.c1.c2").
  
  Whitespace before '.' and '#' in attribute patterns is stripped.
*/

/*
  Agenda:
  - parse numbers
  - parse colors only when appropriate
  - color names
  - attribute selectors
  
  Corners:
  - test IE
  - CSS parse errors
  
  Literacy:
  - add document.divStylesheet
  
  Future:
  - attribute selectors
  - commas
  - check types
  - pseudo-elements
*/

/*
 * Utilities
 */
try {OSUtils} catch(e) {OSUtils = {}}
if (!OSUtils.Array) {OSUtils.Array = {}}
if (!OSUtils.Hash) {OSUtils.Hash = {}}

OSUtils.Array.union = function (a, b) {
	var c = new Array(a.length);
	for (var i = 0; i < a.length; i++)
		c[i] = a[i];
	for (var i = 0; i < b.length; i++)
		if (!OSUtils.Array.contains(a, b[i]))
			c.push(b[i]);
	return c;
};

OSUtils.Array.contains = function (ar, e) {
	for (var i = 0; i < ar.length; i++)
		if (ar[i] == e)
			return true;
	return false;
};

OSUtils.Hash.update = function(a, b) {
    for (var p in b)
        a[p] = b[p];
	return a;
};

/*
 * DivStyle package
 */
var DivStyle = {};

DivStyle.CSSStyleSheet = function () {
    this.cssRules = [];
};

DivStyle.CSSStyleSheet.prototype.addRules = function (text) {
    var parser = new CSSParser(new CSSBuilder(this));
    parser.parse(text);
};

DivStyle.CSSStyleSheet.prototype.addRule = function(selector, properties) {
    this.cssRules.push(new DivStyle.CSSRule(selector, properties));
};

DivStyle.CSSRule = function (selector, properties) {
    var newProperties = {};
    for (var p in properties)
        if (p.match(/-/)) {
            var words = p.split(/-/);
            for (var i = 0, w; w = words[i]; i++)
                if (i && w)
                    words[i] = w.charAt(0).toUpperCase() + w.slice(1);
            newProperties[words.join('')] = properties[p];
        }
    for (var p in newProperties)
        properties[p] = newProperties[p];
    this.selector = selector.join(' ').replace(/([#\.])\s+/, '$1');
    this.style = properties;
};

DivStyle.CSSRule.prototype.getSelectedElements = function() {
	// FIXME: doesn't work if attribute selector contains a ','
	var selectors = this.selector.split(/\s*,\s*/);
	var results = null;
	for (var i = 0, selector; selector = selectors[i++]; ) {
		var elements = document.getElementsBySelector(selector) || [];
		if (results) results = OSUtils.Array.union(results, elements);
		else results = elements;
	}
    return results;
};

DivStyle.getStyleSheet = function() {
    if (document.divStylesheet) return document.divStylesheet;
    var styleSheet = new DivStyle.CSSStyleSheet;
    var elements = document.getElementsByTagName('div');
    for (var i = 0, e; e = elements[i++]; )
        if (e.className.match(/\bstyle\b/i)) {
			e.style.display = 'none';
            styleSheet.addRules(e.innerHTML);
		}
    return document.divStylesheet = styleSheet;
};

DivStyle.applyStyles = function () {
    var rules = DivStyle.getStyleSheet().cssRules;
    for (var ri = 0, rule; rule = rules[ri++];) {
        var elements = rule.getSelectedElements();
        for (var ei = 0, e; e = elements[ei++]; ) {
			e.divStyle = e.divStyle || OSUtils.Hash.update({}, DivStyle.defaultProperties);
			OSUtils.Hash.update(e.divStyle, rule.style);
		}
    }
};

DivStyle.propertyDefinitions = {};
DivStyle.defaultProperties = {};

DivStyle.defineProperty = function(name, type, defaultValue) {
    DivStyle.propertyDefinitions[name] = {type: type, defaultValue: defaultValue};
	if (defaultValue != undefined)
		DivStyle.defaultProperties[name] = defaultValue;
};


/*
 * Parser
 */
function CSSParser(builder) {
    this.builder = builder;
};

CSSParser.ColorNames = {
	aqua: 0x00FFFF,
	black: 0x000000,
	blue: 0x0000FF,
	fuchsia: 0xFF00FF,
	gray: 0x808080,
	green: 0x008000,
	lime: 0x00FF00,
	maroon: 0x800000,
	navy: 0x000080,
	olive: 0x808000,
	purple: 0x800080,
	red: 0xFF0000,
	silver: 0xC0C0C0,
	teal: 0x008080,
	white: 0xFFFFFF,
	yellow: 0xFFFF00
};

CSSParser.parseColor = function(value) {
    if (value.charAt(0) == '#') {
        var n = parseInt(value.slice(1), 16);
        switch (!isNaN(n) && value.length-1) {
        case 3:
            return ((n & 0xf00) << 8 | (n & 0xf0) << 4 | (n & 0xf)) * 17;
        case 6:
            return n;
        default:
            error('invalid color: ' + value);
        }
    }
	if (CSSParser.ColorNames[value])
		return CSSParser.ColorNames[value];
    error('invalid color');
	return 0x000000;
};

CSSParser.TOKEN_PATTERNS = {
    IDENT: /^\w[\w\d-]*/,
    STRING: /^"([^\\\"]|\\.)*"|'([^\\\']|\\.)*'/,
    HASH: /^#[\w\d-]+/,
    SKIP_WS: /^\s+/m,
    SKIP_LC: /^\/\/.*/,
    SKIP_BC: /^\/\*([^\*]|\*[^\/])*\*\//m
};

CSSParser.transitions = {
	initial: {
		'IDENT': ['+'],
		'HASH': ['+'],
		'*': ['+'],
		'.': ['+'],
		',': ['+'],
		'{': ['setSelector', 'propertyName']
	},
    propertyName: {
		'.}': ['endProperties', 'initial'],
		IDENT: ['setPropertyName', 'propertyColon']
	},
    propertyColon: {':': [null, 'propertyValue']},
	propertyValue: {
		IDENT: ['+'],
		HASH: ['+'],
		STRING: ['+'],
		';': ['setPropertyValue', 'propertyName'],
		'}': ['endPropertiesWithValue', 'initial']
	}
};

CSSParser.prototype.nextToken = function () {
    var slice = this.text.slice(this.i);
    if (!slice) return null;
    for (var p in CSSParser.TOKEN_PATTERNS) {
        var m = slice.match(CSSParser.TOKEN_PATTERNS[p]);
        if (m && m.index == 0) {
            this.i += m[0].length;
            if (p.match(/^SKIP/))
                return this.nextToken();
            var value = m[0];
            if (p == 'NUMBER')
                value = parseInt(value);
            return {type: p, value: value};
        }
    }
    var c = this.text.charAt(this.i++);
    return {type: c, value: c};
};

CSSParser.prototype.parse = function(text) {
    this.text = text;
    this.i = 0;
    var state = 'initial';
    var values = [];
    while (true) {
        var token = this.nextToken();
        if (!token) return;
        var entry = CSSParser.transitions[state][token.type];
        if (!entry) throw 'parse error at state ' + state + ', token ' + token;
        var action = entry[0];
		var nextState = entry[1] || state;
        if (action == '+')
            values.push(token.value);
        else if (action) {
            var fn = this.builder[action];
            if (!fn) throw 'unknown action ' + action;
            fn.apply(this.builder, [values, token.value]);
            values = [];
        }
        state = nextState;
    }
};

function CSSBuilder(styleSheet) {
    this.styleSheet = styleSheet;
};

CSSBuilder.prototype.setSelector = function(values) {
    this.selector = values;
    this.properties = {};
};

CSSBuilder.prototype.setPropertyName = function(_, name) {
    this.propertyName = name;
};

CSSBuilder.prototype.setPropertyValue = function(values) {
    var value = values.join(' ');
    if (values.length == 1 && value.match(/^#/))
        value = CSSParser.parseColor(value);
    this.properties[this.propertyName] = value;
};

CSSBuilder.prototype.endProperties = function() {
    this.styleSheet.addRule(this.selector, this.properties);
};

CSSBuilder.prototype.endPropertiesWithValue = function(values) {
    this.setPropertyValue(values);
    this.endProperties();
};


/*
 * Initialization
 */

DivStyle.initialize = function() {
	DivStyle.applyStyles();
};

if (window.addEventListener) {
    window.addEventListener('load', DivStyle.initialize, false);
} else if (window.attachEvent) {
    window.attachEvent('onload', DivStyle.initialize);
} else {
    window.onload = (function() {
        var nextfn = window.onload || function(){};
        return function() {
            DivStyle.initialize();
            nextfn();
        }
    })();
}
