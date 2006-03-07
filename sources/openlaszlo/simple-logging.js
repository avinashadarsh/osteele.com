/*
  Author: Oliver Steele
  Copyright: Copyright 2006 Oliver Steele.  All rights reserved.
  Download: http://osteele.com/sources/openlaszlo/simple-logging.js
  License: MIT License.
  
  This file defines functions +info+, +warn+, +error+, and +debug+
  that are compatible with those defined in {readable.js}[http://osteele.com/sources/javascript/readable.js],
  {inline-console.js}[http://osteele.com/sources/javascript/inline-console.js],
  and fvlogger[http://www.alistapart.com/articles/jslogging].
*/

function __debug_message(level, args) {
    Debug.write.apply(Debug, [level].concat(args));
}

function info() {__debug_message('info', arguments)}
function debug() {__debug_message('debug', arguments)}
function warn() {Debug.warn.apply(Debug, arguments)}
function error() {Debug.error.apply(Debug, arguments)}
