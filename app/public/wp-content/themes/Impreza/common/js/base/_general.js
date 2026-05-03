/**
 * Use passive listeners to improve scrolling performance.
 * @link https://github.com/WICG/EventListenerOptions/blob/gh-pages/explainer.md
 */
[ 'touchstart', 'touchmove', 'wheel', 'mousewheel' ].map( function( type ) {
	jQuery.event.special[ type ] = {
		setup: function( _, ns, handle ) {
			var self = this;
			if ( !! self['addEventListener'] ) {
				self.addEventListener( type, handle, {
					passive: type.indexOf( 'touch' ) === 0
						? ! ns.includes( 'noPreventDefault' )
						: true
				} );
			}
		}
	};
} );

/*!
 * imagesLoaded PACKAGED v4.1.4
 * JavaScript is all like "You images are done yet or what?"
 * MIT License
 */

!function(a,b){"function"==typeof define&&define.amd?define("ev-emitter/ev-emitter",b):"object"==typeof module&&module.exports?module.exports=b():a.EvEmitter=b()}("undefined"==typeof window?this:window,function(){function a(){}var b=a.prototype;return b.on=function(a,b){if(a&&b){var c=this._events=this._events||{},d=c[a]=c[a]||[];return-1==d.indexOf(b)&&d.push(b),this}},b.once=function(a,b){if(a&&b){this.on(a,b);var c=this._onceEvents=this._onceEvents||{},d=c[a]=c[a]||{};return d[b]=!0,this}},b.off=function(a,b){var c=this._events&&this._events[a];if(c&&c.length){var d=c.indexOf(b);return-1!=d&&c.splice(d,1),this}},b.emitEvent=function(a,b){var c=this._events&&this._events[a];if(c&&c.length){c=c.slice(0),b=b||[];for(var d=this._onceEvents&&this._onceEvents[a],e=0;e<c.length;e++){var f=c[e],g=d&&d[f];g&&(this.off(a,f),delete d[f]),f.apply(this,b)}return this}},b.allOff=function(){delete this._events,delete this._onceEvents},a}),function(a,b){"use strict";"function"==typeof define&&define.amd?define(["ev-emitter/ev-emitter"],function(c){return b(a,c)}):"object"==typeof module&&module.exports?module.exports=b(a,require("ev-emitter")):a.imagesLoaded=b(a,a.EvEmitter)}("undefined"==typeof window?this:window,function(b,c){function f(a,b){for(var c in b)a[c]=b[c];return a}function g(b){if(Array.isArray(b))return b;var c="object"==typeof b&&"number"==typeof b.length;return c?a.call(b):[b]}function j(a,b,c){if(!(this instanceof j))return new j(a,b,c);var d=a;return"string"==typeof a&&(d=document.querySelectorAll(a)),d?(this.elements=g(d),this.options=f({},this.options),"function"==typeof b?c=b:f(this.options,b),c&&this.on("always",c),this.getImages(),l&&(this.jqDeferred=new l.Deferred),void setTimeout(this.check.bind(this))):void m.error("Bad element for imagesLoaded "+(d||a))}function i(a){this.img=a}function k(a,b){this.url=a,this.element=b,this.img=new Image}var l=b.jQuery,m=b.console,a=Array.prototype.slice;j.prototype=Object.create(c.prototype),j.prototype.options={},j.prototype.getImages=function(){this.images=[],this.elements.forEach(this.addElementImages,this)},j.prototype.addElementImages=function(a){"IMG"==a.nodeName&&this.addImage(a),!0===this.options.background&&this.addElementBackgroundImages(a);var b=a.nodeType;if(b&&d[b]){for(var c,e=a.querySelectorAll("img"),f=0;f<e.length;f++)c=e[f],this.addImage(c);if("string"==typeof this.options.background){var g=a.querySelectorAll(this.options.background);for(f=0;f<g.length;f++){var h=g[f];this.addElementBackgroundImages(h)}}}};var d={1:!0,9:!0,11:!0};return j.prototype.addElementBackgroundImages=function(a){var b=getComputedStyle(a);if(b)for(var c,d=/url\((['"])?(.*?)\1\)/gi,e=d.exec(b.backgroundImage);null!==e;)c=e&&e[2],c&&this.addBackground(c,a),e=d.exec(b.backgroundImage)},j.prototype.addImage=function(a){var b=new i(a);this.images.push(b)},j.prototype.addBackground=function(a,b){var c=new k(a,b);this.images.push(c)},j.prototype.check=function(){function a(a,c,d){setTimeout(function(){b.progress(a,c,d)})}var b=this;return this.progressedCount=0,this.hasAnyBroken=!1,this.images.length?void this.images.forEach(function(b){b.once("progress",a),b.check()}):void this.complete()},j.prototype.progress=function(a,b,c){this.progressedCount++,this.hasAnyBroken=this.hasAnyBroken||!a.isLoaded,this.emitEvent("progress",[this,a,b]),this.jqDeferred&&this.jqDeferred.notify&&this.jqDeferred.notify(this,a),this.progressedCount==this.images.length&&this.complete(),this.options.debug&&m&&m.log("progress: "+c,a,b)},j.prototype.complete=function(){var a=this.hasAnyBroken?"fail":"done";if(this.isComplete=!0,this.emitEvent(a,[this]),this.emitEvent("always",[this]),this.jqDeferred){var b=this.hasAnyBroken?"reject":"resolve";this.jqDeferred[b](this)}},i.prototype=Object.create(c.prototype),i.prototype.check=function(){var a=this.getIsImageComplete();return a?void this.confirm(0!==this.img.naturalWidth,"naturalWidth"):(this.proxyImage=new Image,this.proxyImage.addEventListener("load",this),this.proxyImage.addEventListener("error",this),this.img.addEventListener("load",this),this.img.addEventListener("error",this),void(this.proxyImage.src=this.img.src))},i.prototype.getIsImageComplete=function(){return this.img.complete&&this.img.naturalWidth},i.prototype.confirm=function(a,b){this.isLoaded=a,this.emitEvent("progress",[this,this.img,b])},i.prototype.handleEvent=function(a){var b="on"+a.type;this[b]&&this[b](a)},i.prototype.onload=function(){this.confirm(!0,"onload"),this.unbindEvents()},i.prototype.onerror=function(){this.confirm(!1,"onerror"),this.unbindEvents()},i.prototype.unbindEvents=function(){this.proxyImage.removeEventListener("load",this),this.proxyImage.removeEventListener("error",this),this.img.removeEventListener("load",this),this.img.removeEventListener("error",this)},k.prototype=Object.create(i.prototype),k.prototype.check=function(){this.img.addEventListener("load",this),this.img.addEventListener("error",this),this.img.src=this.url;var a=this.getIsImageComplete();a&&(this.confirm(0!==this.img.naturalWidth,"naturalWidth"),this.unbindEvents())},k.prototype.unbindEvents=function(){this.img.removeEventListener("load",this),this.img.removeEventListener("error",this)},k.prototype.confirm=function(a,b){this.isLoaded=a,this.emitEvent("progress",[this,this.element,b])},j.makeJQueryPlugin=function(a){a=a||b.jQuery,a&&(l=a,l.fn.imagesLoaded=function(a,b){var c=new j(this,a,b);return c.jqDeferred.promise(l(this))})},j.makeJQueryPlugin(),j});

/*
 * jQuery Easing v1.4.1 - http://gsgd.co.uk/sandbox/jquery/easing/
 * Open source under the BSD License.
 * Copyright © 2008 George McGinley Smith
 * All rights reserved.
 * https://raw.github.com/gdsmith/jquery-easing/master/LICENSE
*/
jQuery.easing.jswing=jQuery.easing.swing;var pow=Math.pow;jQuery.extend(jQuery.easing,{def:"easeOutExpo",easeInExpo:function(a){return 0===a?0:pow(2,10*a-10)},easeOutExpo:function(a){return 1===a?1:1-pow(2,-10*a)},easeInOutExpo:function(a){return 0===a?0:1===a?1:.5>a?pow(2,20*a-10)/2:(2-pow(2,-20*a+10))/2}});

/**
 * UpSolution Theme Core JavaScript Code
 *
 * @requires jQuery
 */
if ( window.$us === undefined ) {
	window.$us = {};
}

// Note: The variable is needed for the page-scroll.js file which changes only in menu.js
$us.mobileNavOpened = 0;

// The parameters that are in the code but not applied in the absence of a header
// When connecting header, correct parameters will be loaded
$us.header = {};
[
	'getCurrentHeight',
	'getHeaderInitialPos',
	'getHeight',
	'getScrollDirection',
	'getScrollTop',
	'isFixed',
	'isHidden',
	'isHorizontal',
	'isStatic',
	'isSticky',
	'isStickyAutoHidden',
	'isStickyAutoHideEnabled',
	'isStickyEnabled',
	'isTransparent',
	'isVertical',
	'on'
].map( function( name ) {
	// External functions that can be called in other scripts
	$us.header[ name ] = jQuery.noop;
} );

/**
 * Retrieve/set/erase dom modificator class <mod>_<value> for UpSolution CSS Framework
 * @param {String} mod Modificator namespace
 * @param {String} [value] Value
 * @returns {String|self}
 */
jQuery.fn.usMod = function( mod, value ) {
	if ( this.length == 0 ) return this;
	// Retrieve modificator (The modifier will only be obtained from the first node)
	if ( value === undefined ) {
		var pcre = new RegExp( '^.*?' + mod + '\_([a-zA-Z0-9\_\-]+).*?$' );
		return ( pcre.exec( this.get( 0 ).className ) || [] )[ 1 ] || false;
	}
	// Set/Remove class modificator
	this.each( function( _, item ) {
		// Remove class modificator
		item.className = item.className.replace( new RegExp( '(^| )' + mod + '\_[a-zA-Z0-9\_\-]+( |$)' ), '$2' );
		if ( value !== false ) {
			item.className += ' ' + mod + '_' + value;
		}
	} );
	return this;
};

/**
 * Convert data from PHP to boolean the right way
 * @param {Mixed} value
 * @returns {Boolean}
 */
$us.toBool = function( value ) {
	if ( typeof value == 'boolean' ) {
		return value;
	}
	if ( typeof value == 'string' ) {
		value = value.trim();
		return ( value.toLocaleLowerCase() == 'true' || value == '1' );
	}
	return !! parseInt( value );
};

/**
 * The function parses a string argument and returns an integer of the specified
 * radix (the base in mathematical numeral systems)
 *
 * @param {mixed} value The value to parse
 * @return {number} An integer parsed from the given string
 */
$us.parseInt = function( value ) {
	return parseInt( value ) || 0;
};

/**
 * Thefunction parses an argument (converting it to a string first if needed)
 * and returns a floating point number
 *
 * @param {mixed} value The value to parse
 * @return {number} A floating point number parsed from the given string
 */
$us.parseFloat = function( value ) {
	return parseFloat( value ) || 0;
};

$us.getScript = function( url, callback ) {
	if ( ! $us.ajaxLoadJs ) {
		callback();
		return false;
	}

	if ( $us.loadedScripts === undefined ) {
		$us.loadedScripts = {};
		$us.loadedScriptsFunct = {};
	}

	if ( $us.loadedScripts[ url ] === 'loaded' ) {
		callback();
		return;
	} else if ( $us.loadedScripts[ url ] === 'loading' ) {
		$us.loadedScriptsFunct[ url ].push( callback );
		return;
	}

	$us.loadedScripts[ url ] = 'loading';
	$us.loadedScriptsFunct[ url ] = [];
	$us.loadedScriptsFunct[ url ].push( callback )

	var complete = function() {
		for ( var i = 0; i < $us.loadedScriptsFunct[ url ].length; i ++ ) {
			if ( typeof $us.loadedScriptsFunct[ url ][ i ] === 'function' ) {
				$us.loadedScriptsFunct[ url ][ i ]();
			}
		}
		$us.loadedScripts[ url ] = 'loaded';
	};

	var options = {
		dataType: "script",
		cache: true,
		url: url,
		complete: complete
	};

	return jQuery.ajax( options );
};

/**
 * Determines whether animation is available or not
 * @param {String} animationName The ease animation name
 * @param {String} defaultAnimationName The default animation name
 * @return {String}
 */
$us.getAnimationName = function( animationName, defaultAnimationName ) {
	if ( jQuery.easing.hasOwnProperty( animationName ) ) {
		return animationName;
	}
	return defaultAnimationName
		? defaultAnimationName
		: jQuery.easing._default;
};

/**
 * Behaves the same as setTimeout except uses requestAnimationFrame() where possible for better performance
 * @param {Function} fn The callback function
 * @param {Number} delay The delay in milliseconds
 */
$us.timeout = function( fn, delay ) {
	var start = new Date().getTime(),
		handle = new Object();

	function loop() {
		var current = new Date().getTime(),
			delta = current - start;
		delta >= delay
			? fn.call()
			: handle.value = window.requestAnimationFrame( loop );
	};
	handle.value = window.requestAnimationFrame( loop );
	return handle;
};

/**
 * Behaves the same as clearTimeout except uses cancelRequestAnimationFrame() where possible for better performance
 * @param {Number|{}} fn The callback function
 */
$us.clearTimeout = function( handle ) {
	if ( handle ) {
		window.cancelAnimationFrame( handle.value );
	}
};

/**
 * Returns a function, that, as long as it continues to be invoked, will not
 * be triggered. The function will be called after it stops being called for
 * N milliseconds. If `immediate` is passed, trigger the function on the
 * leading edge, instead of the trailing. The function also has a property 'clear'
 * that is a function which will clear the timer to prevent previously scheduled executions.
 *
 * @param {Function} function to wrap
 * @param {Number} timeout in ms (`100`)
 * @param {Boolean} whether to execute at the beginning (`false`)
 * @return {Function}
 */
$us.debounce = function( fn, wait, immediate ) {
	var timeout, args, context, timestamp, result;
	if ( null == wait ) wait = 100;
	function later() {
		var last = Date.now() - timestamp;
		if ( last < wait && last >= 0 ) {
			timeout = setTimeout( later, wait - last );
		} else {
			timeout = null;
			if ( ! immediate ) {
				result = fn.apply( context, args );
				context = args = null;
			}
		}
	}
	var debounced = function() {
		context = this;
		args = arguments;
		timestamp = Date.now();
		var callNow = immediate && ! timeout;
		if ( ! timeout ) timeout = setTimeout( later, wait );
		if ( callNow ) {
			result = fn.apply( context, args );
			context = args = null;
		}
		return result;
	};
	debounced.prototype = {
		clear: function() {
			if ( timeout ) {
				clearTimeout( timeout );
				timeout = null;
			}
		},
		flush: function() {
			if ( timeout ) {
				result = fn.apply( context, args );
				context = args = null;
				clearTimeout( timeout );
				timeout = null;
			}
		}
	};
	return debounced;
};

// Prototype mixin for all classes working with events
$us.mixins = {};
$us.mixins.Events = {
	/**
	 * Attach a handler to an event for the class instance
	 * @param {String} eventType A string containing event type, such as 'beforeShow' or 'change'
	 * @param {Function} handler A function to execute each time the event is triggered
	 */
	on: function( eventType, handler ) {
		if ( this.$$events === undefined ) {
			this.$$events = {};
		}
		if ( this.$$events[ eventType ] === undefined ) {
			this.$$events[ eventType ] = [];
		}
		this.$$events[ eventType ].push( handler );
		return this;
	},
	/**
	 * Remove a previously-attached event handler from the class instance
	 * @param {String} eventType A string containing event type, such as 'beforeShow' or 'change'
	 * @param {Function} [handler] The function that is to be no longer executed.
	 * @chainable
	 */
	off: function( eventType, handler ) {
		if ( this.$$events === undefined || this.$$events[ eventType ] === undefined ) {
			return this;
		}
		if ( handler !== undefined ) {
			var handlerPos = jQuery.inArray( handler, this.$$events[ eventType ] );
			if ( handlerPos != - 1 ) {
				this.$$events[ eventType ].splice( handlerPos, 1 );
			}
		} else {
			this.$$events[ eventType ] = [];
		}
		return this;
	},
	/**
	 * Execute all handlers and behaviours attached to the class instance for the given event type
	 * @param {String} eventType A string containing event type, such as 'beforeShow' or 'change'
	 * @param {Array} extraParameters Additional parameters to pass along to the event handler
	 * @chainable
	 */
	trigger: function( eventType, extraParameters ) {
		if ( this.$$events === undefined || this.$$events[ eventType ] === undefined || this.$$events[ eventType ].length == 0 ) {
			return this;
		}
		var params = ( arguments.length > 2 || ! jQuery.isArray( extraParameters ) )
			? Array.prototype.slice.call( arguments, 1 )
			: extraParameters;
		// First argument is the current class instance
		params.unshift( this );
		for ( var index = 0; index < this.$$events[ eventType ].length; index ++ ) {
			this.$$events[ eventType ][ index ].apply( this.$$events[ eventType ][ index ], params );
		}
		return this;
	}
};

// Fixing hovers for devices with both mouse and touch screen
if ( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent ) ) {
	jQuery.isMobile = true;
} else {
	jQuery.isMobile = ( navigator.platform == 'MacIntel' && navigator.maxTouchPoints > 1 );
	jQuery( 'html' ).toggleClass( 'ios-touch', !! jQuery.isMobile );

}
jQuery( 'html' ).toggleClass( 'no-touch', ! jQuery.isMobile );

/**
 * Commonly used jQuery objects
 */
! function( $ ) {
	$us.$window = $( window );
	$us.$document = $( document );
	$us.$html = $( 'html' );
	$us.$body = $( '.l-body:first' );
	$us.$htmlBody = $us.$html.add( $us.$body );
	$us.$canvas = $( '.l-canvas:first' );
	/**
	 * Definition this is the usbuilder preview page
	 * @var {Boolean} true if the page is open in the usbuilder, otherwise it is false
	 */
	$us.usbPreview = function() {
		return $us.$body.hasClass( 'usb_preview' );
	};
}( jQuery );

/**
 * Helper Global Methods
 */
! function( $ ) {
	/**
	 * Get the current state.
	 *
	 * @return {String} Returns the current state (default|laptops|tablets|mobiles).
	 */
	$us.getCurrentState = function() {
		return '' + $us.$body.usMod( 'state' );
	};

	/**
	 * Checks if given state is current state.
	 *
	 * @param {String|[]} state State to be compared with.
	 * @return {Boolean} True if the state matches, False otherwise.
	 */
	$us.currentStateIs = function( state ) {
		if ( ! state ) {
			return false;
		}
		if ( ! $.isArray( state ) ) {
			state = [ '' + state ];
		}
		return $.inArray( $us.getCurrentState(), state ) !== - 1;
	};

	/**
	 * Get the admin bar height.
	 *
	 * @return {Number} Returns the height of the admin bar if it exists.
	 */
	$us.getAdminBarHeight = function() {
		return ( document.getElementById('wpadminbar') || {} ).offsetHeight || 0;
	};
} ( jQuery );

/**
 * $us.canvas
 *
 * All the needed data and functions to work with overall canvas.
 *
 * TODO: Check all data that is linked from `$us.header` and use the functionality
 * of object `$us.header` and clear the current object.
 */
! function( $, undefined ) {
	"use strict";

	function USCanvas( options ) {
		var self = this;

		// Setting options
		var defaults = {
			disableEffectsWidth: 900,
			backToTopDisplay: 100
		};

		self.options = $.extend( {}, defaults, options || {} );

		// Commonly used dom elements
		self.$header = $( '.l-header', $us.$canvas );
		self.$main = $( '.l-main', $us.$canvas );
		// Content sections
		self.$sections = $( '> *:not(.l-header) .l-section', $us.$canvas );
		self.$firstSection = self.$sections.first();
		self.$secondSection = self.$sections.eq( 1 );
		self.$stickySections = self.$sections.filter( '.type_sticky:visible' );
		self.$fullscreenSections = self.$sections.filter( '.full_height' );
		self.$topLink = $( '.w-toplink' );

		// Canvas modificators
		self.type = $us.$canvas.usMod( 'type' );
		// Initial header position
		self._headerPos = self.$header.usMod( 'pos' );
		// Current header position
		self.headerPos = self._headerPos;
		self.headerBg = self.$header.usMod( 'bg' );
		self.rtl = $us.$body.hasClass( 'rtl' );

		// Used to prevent resize events on scroll for Android browsers
		self.isScrolling = false;
		self.isAndroid = /Android/i.test( navigator.userAgent );

		// If in iframe...
		if ( $us.$body.hasClass( 'us_iframe' ) ) {
			// change links so they lead to main window
			$( 'a:not([target])' ).each( function() {
				$( this ).attr( 'target', '_parent' )
			} );
			// hide preloader
			$( function( $ ) {
				var $framePreloader = $( '.l-popup-box-content .g-preloader', window.parent.document );
				$framePreloader.hide();
			} );
		}

		// Class assignments for adjusting header position via css.
		if ( self.hasStickyFirstSection() ) {
			$us.$body.addClass( 'sticky_first_section' );
		}

		// Events
		$us.$window
			.on( 'scroll', self._events.scroll.bind( self ) )
			.on( 'resize load', self._events.resize.bind( self ) )
			.on( 'scroll resize load', self._events.toggleClassIsSticky.bind( self ) );

		// Complex logics requires two initial renders: before inner elements render and after
		$us.timeout( self._events.resize.bind( self ), 25 );
		$us.timeout( self._events.resize.bind( self ), 75 );
	}

	USCanvas.prototype = {

		/**
		 * Get the offset top.
		 *
		 * @return {Number} The offset top.
		 */
		getOffsetTop: function() {
			var top = Math.ceil( $us.$canvas.offset().top );
			if ( $us.currentStateIs( 'mobiles' ) ) {
				top -= $us.getAdminBarHeight();
			}
			return top;
		},

		/**
		 * Determines if sticky section.
		 *
		 * @return {Boolean} True if sticky section, False otherwise.
		 */
		isStickySection: function() {
			var self = this;
			return !! self.$stickySections.length;
		},

		/**
		 * Determines if there is a sticky active section
		 *
		 * @return {Boolean} True if sticky section, False otherwise.
		 */
		hasStickySection: function () {
			var self = this;
			if ( self.isStickySection() ) {
				return self.$stickySections.hasClass( 'is_sticky' );
			}
			return false
		},

		/**
		 * Determines if position sticky sections.
		 *
		 * @return {Boolean} True if position sticky sections, False otherwise.
		 */
		hasPositionStickySections: function() {
			var self = this;
			if ( self.isStickySection() ) {
				return self.$stickySections
					.filter( function() { return $( this ).css( 'position' ) == 'sticky' } )
					.length;
			}
			return false;
		},

		/**
		 * Get the height first sticky section.
		 *
		 * @return {Number} The height first sticky section.
		 */
		getStickySectionHeight: function() {
			var self = this,
				stickySectionHeight = 0;
			if ( self.isStickySection() ) {
				var header = $us.header,
					$stickySection = self.$stickySections.first();
				stickySectionHeight = $stickySection.outerHeight( true );
				// If the first section on page is sticky, it has header height added to its padding
				// Subtracting the header height from function return value in this case
				if ( self.hasStickyFirstSection() && header.isHorizontal() && ! header.isStatic() ) {
					stickySectionHeight -= header.getCurrentHeight();
				}
			}
			return stickySectionHeight;
		},

		/**
		 * Determines if sticky first section.
		 *
		 * @return {boolean} True if first sticky section, False otherwise.
		 */
		hasStickyFirstSection: function() {
			var self = this,
				$first = self.$stickySections.first();
			return self.isStickySection() && $first.index() === 0 && $first.hasClass( 'is_sticky' );
		},

		/**
		 * Checks that the specified section is after the sticky section.
		 *
		 * @param {Mixed} node Start node
		 * @return {Boolean} True if after sticky section, False otherwise.
		 */
		isAfterStickySection: function( node ) {
			var $node = $( node );
			if ( ! $node.length ) {
				return false;
			}
			if ( ! $node.hasClass( 'l-section' ) ) {
				$node = $node.closest( '.l-section' );
			}
			return $node.index() > this.$stickySections.index();
		},

		/**
		 * Gets the height first section.
		 *
		 * @return {number} The height first section.
		 */
		getHeightFirstSection: function() {
			return this.$firstSection.length
				? parseFloat( this.$firstSection.outerHeight( true ) )
				: 0;
		},

		/**
		 * Event handlers
		 *
		 * @private
		 */
		_events: {
			/**
			 * Scroll-driven logics
			 *
			 * @event handler
			 */
			scroll: function() {
				var self = this,
					scrollTop = parseInt( $us.$window.scrollTop() );

				// Show/hide go to top link
				self.$topLink
					.toggleClass( 'active', ( scrollTop >= self.winHeight * self.options.backToTopDisplay / 100 ) );

				if ( self.isAndroid ) {
					if ( self.pid ) {
						$us.clearTimeout( self.pid );
					}
					self.isScrolling = true;
					self.pid = $us.timeout( function() {
						self.isScrolling = false;
					}, 100 );
				}
			},

			/**
			 * Resize-driven logics
			 *
			 * @event handler
			 */
			resize: function() {
				var self = this;

				// Window dimensions
				self.winHeight = parseInt( $us.$window.height() );
				self.winWidth = parseInt( $us.$window.width() );

				// Disabling animation on mobile devices
				$us.$body.toggleClass( 'disable_effects', ( self.winWidth < self.options.disableEffectsWidth ) );

				// If the page is loaded in iframe
				if ( $us.$body.hasClass( 'us_iframe' ) ) {
					var $frameContent = $( '.l-popup-box-content', window.parent.document ),
						outerHeight = $us.$body.outerHeight( true );
					if ( outerHeight > 0 && $( window.parent ).height() > outerHeight ) {
						$frameContent.css( 'height', outerHeight );
					} else {
						$frameContent.css( 'height', '' );
					}
				}

				// Fix scroll glitches that could occur after the resize
				self._events.scroll.call( self );
			},

			/**
			 * Determining whether a section is fixed sticky or not
			 *
			 * @event handler
			 */
			toggleClassIsSticky: function() {
				var self = this;
				if ( ! self.isStickySection() ) {
					return;
				}
				self.$stickySections.each( function( _, section ) {
					var $section = $( section ),
						offsetTop = section.getBoundingClientRect().top - parseInt( $section.css( 'top' ) );
					$section
						.toggleClass( 'is_sticky', (
							parseInt( offsetTop ) === 0
							&& $section.css( 'position' ) == 'sticky'
						) );
				} );
			}
		}
	};

	$us.canvas = new USCanvas( $us.canvasOptions || {} );

}( jQuery );

/**
 * CSS-analog of jQuery slideDown/slideUp/fadeIn/fadeOut functions (for better rendering)
 */
! function( $ ) {

	/**
	 * Remove the passed inline CSS attributes.
	 *
	 * Usage: `$node.resetInlineCSS( 'height', 'width' )` or `$node.resetInlineCSS( [ 'height', 'width' ] )`
	 */
	$.fn.resetInlineCSS = function() {
		var self = this,
			args = [].slice.call( arguments );
		if ( args.length && $.isArray( args[0] ) ) {
			args = args[0];
		}
		for ( var index = 0; index < args.length; index ++ ) {
			self.css( args[ index ], '' );
		}
		return self;
	};

	$.fn.clearPreviousTransitions = function() {
		var self = this,
			// Stopping previous events, if there were any
			prevTimers = ( self.data( 'animation-timers' ) || '' ).split( ',' );
		if ( prevTimers.length >= 2 ) {
			self.resetInlineCSS( 'transition' );
			prevTimers.map( clearTimeout );
			self.removeData( 'animation-timers' );
		}
		return self;
	};

	/**
	 *
	 * @param {Object} css key-value pairs of animated css
	 * @param {Number} duration in milliseconds
	 * @param {Function} onFinish
	 * @param {String} easing CSS easing name
	 * @param {Number} delay in milliseconds
	 */
	$.fn.performCSSTransition = function( css, duration, onFinish, easing, delay ) {
		duration = duration || 250;
		delay = delay || 25;
		easing = easing || 'ease';
		var self = this,
			transition = [];

		self.clearPreviousTransitions();

		for ( var attr in css ) {
			if ( ! css.hasOwnProperty( attr ) ) {
				continue;
			}
			transition.push( attr + ' ' + ( duration / 1000 ) + 's ' + easing );
		}
		transition = transition.join( ', ' );
		self.css( {
			transition: transition
		} );

		// Starting the transition with a slight delay for the proper application of CSS transition properties
		var timer1 = setTimeout( function() {
			self.css( css );
		}, delay );

		var timer2 = setTimeout( function() {
			self.resetInlineCSS( 'transition' );
			if ( $.isFunction( onFinish ) ) {
				onFinish();
			}
		}, duration + delay );

		self.data( 'animation-timers', timer1 + ',' + timer2 );
	};

	// Height animations
	$.fn.slideDownCSS = function( duration, onFinish, easing, delay ) {
		var self = this;
		if ( self.length == 0 ) {
			return;
		}
		self.clearPreviousTransitions();
		// Grabbing paddings
		self.resetInlineCSS( 'padding-top', 'padding-bottom' );
		var timer1 = setTimeout( function() {
			var paddingTop = parseInt( self.css( 'padding-top' ) ),
				paddingBottom = parseInt( self.css( 'padding-bottom' ) );
			// Grabbing the "auto" height in px
			self.css( {
				visibility: 'hidden',
				position: 'absolute',
				height: 'auto',
				'padding-top': 0,
				'padding-bottom': 0,
				display: 'block'
			} );
			var height = self.height();
			self.css( {
				overflow: 'hidden',
				height: '0px',
				opacity: 0,
				visibility: '',
				position: ''
			} );
			self.performCSSTransition( {
				opacity: 1,
				height: height + paddingTop + paddingBottom,
				'padding-top': paddingTop,
				'padding-bottom': paddingBottom
			}, duration, function() {
				self.resetInlineCSS( 'overflow' ).css( 'height', 'auto' );
				if ( typeof onFinish == 'function' ) {
					onFinish();
				}
			}, easing, delay );
		}, 25 );
		self.data( 'animation-timers', timer1 + ',null' );
	};

	$.fn.slideUpCSS = function( duration, onFinish, easing, delay ) {
		var self = this;
		if ( self.length == 0 ) {
			return;
		}
		self.clearPreviousTransitions();
		self.css( {
			height: self.outerHeight(),
			overflow: 'hidden',
			'padding-top': self.css( 'padding-top' ),
			'padding-bottom': self.css( 'padding-bottom' )
		} );
		self.performCSSTransition( {
			height: 0,
			opacity: 0,
			'padding-top': 0,
			'padding-bottom': 0
		}, duration, function() {
			self.resetInlineCSS( 'overflow', 'padding-top', 'padding-bottom' ).css( {
				display: 'none'
			} );
			if ( typeof onFinish == 'function' ) {
				onFinish();
			}
		}, easing, delay );
	};

	// Opacity animations
	$.fn.fadeInCSS = function( duration, onFinish, easing, delay ) {
		var self = this;
		if ( self.length == 0 ) {
			return;
		}
		self.clearPreviousTransitions();
		self.css( { opacity: 0, display: 'block' } );
		self.performCSSTransition( { opacity: 1 }, duration, onFinish, easing, delay );
	};

	$.fn.fadeOutCSS = function( duration, onFinish, easing, delay ) {
		var self = this;
		if ( self.length == 0 ) {
			return;
		}
		self.performCSSTransition( {
			opacity: 0
		}, duration, function() {
			self.css( 'display', 'none' );
			if ( $.isFunction( onFinish ) ) {
				onFinish();
			}
		}, easing, delay );
	};
}( jQuery );

jQuery( function( $ ) {
	"use strict";

	var _window = window,
		_document = document;

	if ( _document.cookie.indexOf( 'us_cookie_notice_accepted=true' ) !== -1 ) {
		$( '.l-cookie' ).remove();
	} else {
		$us.$document
			.on( 'click', '#us-set-cookie', function( e ) {
				e.preventDefault();
				e.stopPropagation();
				var d = new Date();
				d.setFullYear( d.getFullYear() + 1 );
				_document.cookie = 'us_cookie_notice_accepted=true; expires=' + d.toUTCString() + '; path=/;';
				if ( location.protocol === 'https:' ) {
					_document.cookie += ' secure;';
				}
				$( '.l-cookie' ).remove();
			} );
	}

	// Force popup opening on links with ref
	var USPopupLink = function( context, options ) {
		var $links = $( 'a[ref=magnificPopup][class!=direct-link]:not(.inited)', context || _document ),
			defaultOptions = {
				fixedContentPos: true,
				mainClass: 'mfp-fade',
				removalDelay: 300,
				type: 'image'
			};
		if ( $links.length ) {
			$us.getScript( $us.templateDirectoryUri + '/common/js/vendor/magnific-popup.js', function() {
				$links
					.addClass( 'inited' )
					.magnificPopup( $.extend( {}, defaultOptions, options || {} ) );
			} );
		}
	};
	$.fn.wPopupLink = function( options ) {
		return this.each( function() {
			$( this ).data( 'wPopupLink', new USPopupLink( this, options ) );
		} );
	};

	// Init wPopupLink
	$us.$document.wPopupLink();

	( function() {
		// Footer Reveal handler
		var $footer = $( '.l-footer' );

		if ( $us.$body.hasClass( 'footer_reveal' ) && $footer.length && $footer.html().trim().length ) {
			var usFooterReveal = function() {
				var footerHeight = $footer.innerHeight();
				if ( _window.innerWidth > parseInt( $us.canvasOptions.columnsStackingWidth ) - 1 ) {
					$us.$canvas.css( 'margin-bottom', Math.round( footerHeight ) - 1 );
				} else {
					$us.$canvas.css( 'margin-bottom', '' );
				}
			};

			usFooterReveal();
			$us.$window.on( 'resize load', usFooterReveal );
		}
	} )();

	/* YouTube/Vimeo background */
	var $usYTVimeoVideoContainer = $( '.with_youtube, .with_vimeo' );
	if ( $usYTVimeoVideoContainer.length ) {
		$us.$window.on( 'resize load', function() {
			$usYTVimeoVideoContainer.each( function() {
				var $container = $( this ),
					$frame = $container.find( 'iframe' ).first(),
					cHeight = $container.innerHeight(),
					cWidth = $container.innerWidth(),
					fWidth = '',
					fHeight = '';

				if ( cWidth / cHeight < 16 / 9 ) {
					fWidth = cHeight * ( 16 / 9 );
					fHeight = cHeight;
				} else {
					fWidth = cWidth;
					fHeight = fWidth * ( 9 / 16 );
				}

				$frame.css( {
					'width': Math.round( fWidth ),
					'height': Math.round( fHeight ),
				} );
			} );
		} );
	}
} );

/**
 * $us.waypoints
 */
;(function( $, undefined ) {
	"use strict";
	function USWaypoints() {
		// Waypoints that will be called at certain scroll position
		this.waypoints = [];

		// Recount scroll waypoints on any content changes
		$us.$canvas
			.on( 'contentChange', this._countAll.bind( this ) );
		$us.$window
			.on( 'resize load', this._events.resize.bind( this ) )
			.on( 'scroll scroll.waypoints', this._events.scroll.bind( this ) );
		$us.timeout( this._events.resize.bind( this ), 75 );
		$us.timeout( this._events.scroll.bind( this ), 75 );
	}
	USWaypoints.prototype = {
		// Handler's
		_events: {
			/**
			 * Scroll handler
			 */
			scroll: function() {
				var scrollTop = parseInt( $us.$window.scrollTop() );

				// Safari negative scroller fix
				scrollTop = ( scrollTop >= 0 ) ? scrollTop : 0;

				// Handling waypoints
				for ( var i = 0; i < this.waypoints.length; i ++ ) {
					if ( this.waypoints[ i ].scrollPos < scrollTop ) {
						this.waypoints[ i ].fn( this.waypoints[ i ].$elm );
						this.waypoints.splice( i, 1 );
						i --;
					}
				}
			},
			/**
			 * Resize handler
			 */
			resize: function() {
				// Delaying the resize event to prevent glitches
				$us.timeout( function() {
					this._countAll.call( this );
					this._events.scroll.call( this );
				}.bind( this ), 150 );
				this._countAll.call( this );
				this._events.scroll.call( this );
			}
		},
		/**
		 * Add new waypoint
		 *
		 * @param {jQuery} $elm object with the element
		 * @param {mixed} offset Offset from bottom of screen in pixels ('100') or percents ('20%')
		 * @param {Function} fn The function that will be called
		 */
		add: function( $elm, offset, fn ) {
			$elm = ( $elm instanceof $ ) ? $elm : $( $elm );
			if ( $elm.length == 0 ) {
				return;
			}
			if ( typeof offset != 'string' || offset.indexOf( '%' ) == - 1 ) {
				// Not percent: using pixels
				offset = parseInt( offset );
			}
			// Determining whether an element is already in the scope,
			// if it is visible, reset offset
			if ( $elm.offset().top < ( $us.$window.height() + $us.$window.scrollTop() ) ) {
				offset = 0;
			}
			var waypoint = {
				$elm: $elm, offset: offset, fn: fn
			};
			this._count( waypoint );
			this.waypoints.push( waypoint );
		},
		/**
		 *
		 * @param {{}} waypoint
		 * @private
		 */
		_count: function( waypoint ) {
			var elmTop = waypoint.$elm.offset().top, winHeight = $us.$window.height();
			if ( typeof waypoint.offset == 'number' ) {
				// Offset is defined in pixels
				waypoint.scrollPos = elmTop - winHeight + waypoint.offset;
			} else {
				// Offset is defined in percents
				waypoint.scrollPos = elmTop - winHeight + winHeight * parseInt( waypoint.offset ) / 100;
			}
		},
		/**
		 * Count all targets for proper scrolling
		 *
		 * @private
		 */
		_countAll: function() {
			// Counting waypoints
			for ( var i = 0; i < this.waypoints.length; i ++ ) {
				this._count( this.waypoints[ i ] );
			}
		}
	};
	$us.waypoints = new USWaypoints;
})( jQuery );

;( function() {
	var lastTime = 0,
		vendors = ['ms', 'moz', 'webkit', 'o'];
	for ( var x = 0; x < vendors.length && ! window.requestAnimationFrame; ++ x ) {
		window.requestAnimationFrame = window[ vendors[ x ] + 'RequestAnimationFrame' ];
		window.cancelAnimationFrame = window[ vendors[ x ] + 'CancelAnimationFrame' ] || window[ vendors[ x ] + 'CancelRequestAnimationFrame' ];
	}
	if ( ! window.requestAnimationFrame ) {
		window.requestAnimationFrame = function( callback, element ) {
			var currTime = new Date().getTime(),
				timeToCall = Math.max( 0, 16 - ( currTime - lastTime ) ),
				id = window.setTimeout( function() {
					callback( currTime + timeToCall );
				}, timeToCall );
			lastTime = currTime + timeToCall;
			return id;
		};
	}
	if ( ! window.cancelAnimationFrame ) {
		window.cancelAnimationFrame = function( id ) {
			clearTimeout( id );
		};
	}
}() );

/*
 * Remove empty space before content for video post type with active preview
 */
if ( $us.$body.hasClass( 'single-format-video' ) ) {
	figure = $us.$body.find( 'figure.wp-block-embed div.wp-block-embed__wrapper' );
	if ( figure.length ) {
		figure.each( function() {
			if ( this.firstElementChild === null ) {
				this.remove();
			}
		} );
	}
}

/*
 * With "Show More" link, used in Text Block and Post Content elements
 */
! function( $, undefined ) {
	"use strict";

	$us.collapsibleContent = function( container ) {
		this.init( container );
	};
	$us.collapsibleContent.prototype = {
		init: function( container ) {
			// Elements
			this.$container = $( container );
			this.$firstElm = $( '> *:first', this.$container );
			this.contentHeight = this.$container.data( 'content-height' ) || 200;

			// Events
			this.$container
				.on( 'click', '.collapsible-content-more, .collapsible-content-less', this._events.toggleContent.bind( this ) );

			// Init
			if ( ! this.$container.closest( '.owl-carousel' ).length ) {
				this.initHeightCheck.call( this );
			}
		},
		initHeightCheck: function() {
			// Set the height to the element in any unit of measurement and get the height in pixels
			var height = this.$firstElm.css( 'height', this.contentHeight ).height();
			this.$firstElm.css( 'height', '' );
			var elmHeight = this.$firstElm.height();

			if ( elmHeight && elmHeight <= height ) {
				$( '.toggle-links', this.$container ).hide();
				this.$firstElm.css( 'height', '' );
				this.$container.removeClass( 'with_collapsible_content' );
			} else {
				$( '.toggle-links', this.$container ).show();
				this.$firstElm.css( 'height', this.contentHeight );
			}
		},
		/**
		 * Determines if post container is fully visible
		 *
		 * @private This private method is not intended to be called by other classes
		 * @return {Boolean} True if visible, False otherwise
		 */
		_isVisible: function() {
			if ( ! this.$container.length ) {
				return false;
			}
			var w = window,
				d = document,
				rect = this.$container[0].getBoundingClientRect(),
				containerPosition = {
					top: w.pageYOffset + rect.top,
					left: w.pageXOffset + rect.left,
					right: w.pageXOffset + rect.right,
					bottom: w.pageYOffset + rect.bottom
				},
				windowPosition = {
					top: w.pageYOffset,
					left: w.pageXOffset,
					right: w.pageXOffset + d.documentElement.clientWidth,
					bottom: w.pageYOffset + d.documentElement.clientHeight
				};
			return (
				containerPosition.bottom > windowPosition.top
				&& containerPosition.top < windowPosition.bottom
				&& containerPosition.right > windowPosition.left
				&& containerPosition.left < windowPosition.right
			);
		},
		/**
		 * Event handlers
		 *
		 * @private
		 */
		_events: {
			/**
			 * Toggle show or hide post content
			 *
			 * @private
			 * @param {Event} e The Event interface represents an event which takes place in the DOM
			 */
			toggleContent: function( e ) {
				e.preventDefault();
				e.stopPropagation();
				this.$container
					.toggleClass( 'show_content', $( e.target ).hasClass( 'collapsible-content-more' ) )
					.trigger( 'toggleContent' );
				$us.timeout( function() {
					$us.$canvas
						.trigger( 'contentChange' );
					if ( $.isMobile && ! this._isVisible() ) {
						$us.$htmlBody
							.stop( true, false )
							.scrollTop( this.$container.offset().top - $us.header.getCurrentHeight( /* adminBar */true ) );
					}
				}.bind( this ), 1 );
			}
		}
	};
	$.fn.usCollapsibleContent = function() {
		return this.each( function() {
			$( this ).data( 'usCollapsibleContent', new $us.collapsibleContent( this ) );
		} );
	};

	$( '[data-content-height]', $us.$canvas )
		.usCollapsibleContent();

	/**
	 * Additional event validation for scanned carousel items
	 */
	if ( $( '.owl-carousel', $us.$canvas ).length ) {
		$us.$canvas.on( 'click', '.collapsible-content-more, .collapsible-content-less', function( e ) {
			var $target = $( e.target ),
				$container = $target.closest( '[data-content-height]' );
			if ( ! $container.data( 'usCollapsibleContent' ) ) {
				$container.usCollapsibleContent();
				$target.trigger( 'click' );
			}
		} );
	}

}( jQuery );

/**
 * Will fire the original resize event so that the video element
 * recalculates the width and height of the player in WPopup
 */
! function( $, undefined ) {
	$us.$window.on( 'us.wpopup.afterShow', function( _, WPopup ) {
		if ( WPopup instanceof $us.WPopup && $( 'video.wp-video-shortcode', WPopup.$box ).length ) {
			var handle = $us.timeout( function() {
				$us.clearTimeout( handle );
				window.dispatchEvent( new Event( 'resize' ) );
			}, 1 );
		}
	} );
}( jQuery );
