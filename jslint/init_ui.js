// init_ui.js
// 2012-05-09

// This is the web browser companion to fulljslint.js. It is an ADsafe
// lib file that implements a web ui by adding behavior to the widget's
// html tags.

// It stores a function in lib.init_ui. Calling that function will
// start up the JSLint widget ui.

// option = {adsafe: true, fragment: false}

/*properties
	cookie, each, edition, forEach, get, getStyle, getTitle, getValue,
	indent, isArray, join, jslint, keys, klass, length, lib, maxerr, maxlen, on,
	predef, preventDefault, push, q, select, set, split, style, target, value
	*/

	// jQuery(document).ready(function($){
	// 	$('#JSLINT_BUTTON').click(function(e){
	// 		e.preventDefault();
	// 	})
	// });
	
	ADSAFE.id("JSLINT_");

	ADSAFE.lib("init_ui", function (lib) {
		'use strict';

		return function (dom) {
			var edition = dom.q('#JSLINT_EDITION'),
			errors = dom.q('#JSLINT_ERRORS'),
			errors_div = errors.q('>div'),
			jslint_dir = dom.q('#JSLINT_JSLINT'),
			jslint_str = jslint_dir.q('>textarea'),
			properties = dom.q('#JSLINT_PROPERTIES'),
			properties_str = properties.q('>textarea'),
			report = dom.q('#JSLINT_REPORT'),
			report_div = report.q('>div'),
			source = dom.q('#JSLINT_SOURCE'),
			source_str = source.q('>textarea'),
			options = {"sloppy":true,"predef":["jQuery"]};

			// Scan the code automatically when the page loads
			jQuery(document).ready(function(){
				jslint();
			});

			function jslint() {
				if (lib.jslint(source_str.getValue(), options,
					errors_div, report_div, properties_str, edition)
					&& source_str.getValue().length > 0) {
					console.log( 'wtf' );
					errors.style('display', 'block');
				}

				report.style('display', 'block');

				if (properties_str.getValue().length > 21) {
					properties.style('display', 'block');
				}

				source_str.select();
			}
		};
	});

ADSAFE.go("JSLINT_", function(dom, lib) {
	'use strict';
	lib.init_ui(dom);
});