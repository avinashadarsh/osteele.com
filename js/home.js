/* Copyright 2009 by Oliver Steele.  Available under the MIT License. */

$(function() {
  var name = $('title').text().match(/(.+?)(?=\s+HTML)/)[0];
  $('#person-controls div').mouseover(function() {
    var p = $(this).text();
    $('body').
      removeClass('person-1 person-2 person-3').
      addClass('person-' + p);
    $('#person-controls div').removeClass('selected');
    $(this).addClass('selected');
    $('title').text($('title').text().replace(/(.+?)(?=\s+HTML)/, 
					      {1:'My', 2:'Your', 3:name}[p]));
  });
  $('p').filter('*:contains(Oliver), *:contains(his)').each(function() {
    var $this = $(this), html = $this.html();
    $this.html(html.replace(
	/((Oliver(\s+Steele)?|\b(He|he)\b)(\s+(is|was))?|\bHis\b|\bhis\b)/g,
      function(_, s) {
	return '<span class="ego">' +
	  '<span class="person-1">' + person(s, 1) + '</span>' +
	  '<span class="person-2">' + person(s, 2) + '</span>' +
	  '<span class="person-3">' + s + ' </span>' +
	  '</span>';
      }));
  });

  $('.shorten').each(function() {
    var $this = $(this);
    var html = $this.html();
    shrink();
    function shrink() {
      $this.html(html.replace(/<!-- more -->(.|\s|\n)*/, '<span class="more"></span>'));
      $this.find('.more').click(grow);
    }
    function grow() {
      $this.html(html + '<span class="less"></span>');
      $this.find('.less').click(shrink);
    }
  });

  var moving = false;
  $('h1 img').mouseover(function() {
    var $t = $(this);
    var $if = $('h1 iframe');
    var start = {left:50,top:50,width:5,height:5};
    var stop = {left:750,top:10,width:300,height:300};
    if (moving) return;
    if ($if.filter(':visible').length) {
      $if.css(stop).animate(start, function(){$if.hide()});
      $('.candids').show('slow');
    } else {
      moving = true;
      $if.show().css(start).animate(stop, function(){moving=false});
      $('.candids').hide('slow');
    }
  });

  $('img:not([title])').each(function() {
    var $this = $(this);
    $this.attr('title', $this.attr("alt"));
  });

  $('.candids img').
    mouseover(function() { $(this).stop().animate({opacity: 1}) }).
    mouseout(function() { $(this).stop().animate({opacity: .75}) });
});

function person(str, person) {
  switch (person) {
  case 1:
    return map({He:'I', is:'am', was:'was', His:'My', his:'my'});
    break;
  case 2:
    return map({He:'You', is:'are', was:'were', His:'Your', his:'your'});
    break;
  case 3:
    return html.replace(/Oliver(?:\s+Steele)/g, 'He');
    break;
  }
  function map(map) {
    return str.replace(/((?:Oliver(?:\s+Steele)?)|\bHe\b|\bhe\b)(?:\s+(is|was))?/g, function(_, s, v) {
      return map.He + ({is:' '+map.is, was:' '+map.was}[v]||v||'');
    }).replace(/\bHis\b/, map.His).replace(/\bhis\b/, map.his);
  }
}
