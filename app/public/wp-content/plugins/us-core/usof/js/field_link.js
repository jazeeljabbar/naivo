/**
 * USOF Field: Link
 */
! function( $, undefined ) {
	var _window = window,
		_document = document,
		_undefined = undefined;

	if ( _window.$usof === _undefined ) {
		return;
	}

	$usof.field[ 'link' ] = {
		init: function( options ) {
			var self = this;
			self.parentInit( options );

			// Elements
			self.$url = $( 'input[type="text"]:first', self.$row );
			self.$target = $( 'input[type="checkbox"]:first', self.$row );

			// Get current format
			self.format = ( '' + self.$input.data( 'format' ) );
			// Format validation
			if ( [ /* object */'json', /* string */'jsons', /* string */'serialized' ].indexOf( self.format ) === -1 ) {
				self.format = 'jsons'; // Default JSON String (The line will be written everywhere)
			}

			// The checkboxes within the form must be unique, otherwise there may be problems
			// with the display of values set through the JS
			if ( ! self.$target.is( '[name]' ) ) {
				self.$target.attr( 'name', $usof.uniqid() );
			}

			/**
			 * Bondable events
			 *
			 * @private
			 * @var {{}}
			 */
			self._events = {
				applyChange: self._applyChange.bind( self ),
				exampleClick: self._exampleClick.bind( self ),
			};

			// Events
			self.$row.on( 'click', '.usof-example', self._events.exampleClick );
			self.$url.on( 'change', self._events.applyChange );
			self.$target.on( 'change', self._events.applyChange );
		},

		/**
		 * Link field has 2 different formats to store its value depending on where it is used
		 * Note: Saving data only in string format.
		 * @event handler
		 */
		_applyChange: function() {
			var self = this,
				value = self.getValue();
			// Save value
			self.$input.val( ( typeof value !== 'string' ) ? JSON.stringify( value ) : value );
			self.trigger( 'change', [ value ] );
		},

		/**
		 * Add an example link to a field
		 *
		 * @private
		 * @event handler
		 * @param {Event} e The Event interface represents an event which takes place in the DOM.
		 */
		_exampleClick: function( e ) {
			var $example = $( e.target ).closest( '.usof-example' );
			if ( ! $example.length ) return;
			this.$url.val( $example.text() );
		},

		/**
		 * Get the value
		 *
		 * @return {{}|String} Returns a value in the given format
		 */
		getValue: function() {
			var self = this;
			if ( ! self.inited ) return;
			// Get current value
			var value = {
				url: usof_rawurlencode( self.$url.val() ),
				target: self.$target.is( ':checked' ) ? '_blank' : ''
			};
			// In case the field is used for a shortcode - use serialized format
			if ( self.format === 'serialized' ) {
				var result = '';
				for ( var k in value ) {
					if ( value.hasOwnProperty( k ) && value[ k ] ) {
						result += k + ':' + value[ k ] + '|';
					}
				}
				if ( result.length > 0 ) {
					result = result.substring( 0, result.length - 1 );
				}
				// Return serialize value
				return result;

			} else if ( self.format === 'jsons' && $.isPlainObject( value ) ) {
				// Return JSON string
				return JSON.stringify( value );
			}
			// Return JSON object
			return value;
		},

		/**
		 * Set the value
		 *
		 * @param {{}|String} value The value
		 * @param {String} quiet The quiet
		 */
		setValue: function( value, quiet ) {
			var self = this;
			if ( ! self.inited ) return;
			var newValue = {
				url: '',
				target: ''
			};
			// Applying changes to the field according to its format
			if (
				self.format === 'serialized'
				&& (
					( '' + value ).substr( 0, 4 ) === 'url:'
					|| ( '' + value ).substr( 0, 7 ) === 'target:'
					|| ( '' + value ).indexOf( '|' ) !== -1
				)
			) {
				var pairs = value.trim().split( '|' );
				for ( var i = 0; i < pairs.length; i ++ ) {
					var param = pairs[ i ].split( ':' );
					if ( param[0] && param[1] ) {
						// Note: There is no need to use `usof_rawurldecode` here,
						// but for backwards compatibility we will leave it
						newValue[ param[0] ] = usof_rawurldecode( param[1] );
					}
				}

				// JSON string
			} else if ( value && self.format === 'jsons' ) {
				newValue = JSON.parse( value );

				// JSON object
			} else if ( $.isPlainObject( value ) ) {
				newValue = $.extend( newValue, value || {} );
			}
			// Decode URL-encoded strings
			if ( !! newValue.url ) {
				newValue.url = usof_rawurldecode( newValue.url );
			}
			// Save value to fields
			self.$url
				.val( newValue.url );
			self.$target
				.prop( 'checked', ( newValue.target === '_blank' ) );
			// Save value to main field
			if ( typeof value !== 'string' ) {
				value = JSON.stringify( value );
			}
			self.$input.val( value );
		}
	};
}( jQuery );
