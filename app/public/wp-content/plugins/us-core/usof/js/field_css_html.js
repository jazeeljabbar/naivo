/**
 * USOF Field: Css / Html
 */
! function( $, undefined ) {

	var _window = window,
		_undefined = undefined;

	if ( _window.$usof === _undefined ) {
		return;
	}

	$usof.field[ 'css' ] = $usof.field[ 'html' ] = {

		/**
		 * Initializes the object.
		 */
		init: function() {
			var self = this;

			// Variables
			self._params = {};
			self.editor = null;
			self.editorDoc = null;

			/**
			 * Handlers
			 *
			 * @private
			 * @var {{}}
			 */
			self._events = {
				/**
				 * Editor change.
				 *
				 * @event handler
				 */
				editorChange: function() {
					self.parentSetValue( self.getValue() );
				},

				/**
				 * Focus state class delegation.
				 *
				 * @event handler
				 * @param {Object} _
				 * @param {Event} e The Event interface represents an event which takes place in the DOM.
				 */
				editorFocused: function( _, e ) {
					self.$row.toggleClass( 'focused', e.type === 'focus' );
				}
			};

			// Init CodeEditor
			if ( wp.hasOwnProperty( 'codeEditor' ) ) {
				var $params = $( '.usof-form-row-control-params', self.$row );
				if ( $params.is( '[onclick]' ) ) {
					self._params = $params[0].onclick() || {};
					$params.removeAttr( 'onclick' );
				}
				if ( self._params.editor !== false ) {
					self.editor = wp.codeEditor.initialize( self.$input[ 0 ], self._params.editor || {} );
					self.editorDoc = self.editor.codemirror.getDoc();
					self.setValue( self.$input.val() );
					// Events
					self.editor.codemirror.on( 'focus', self._events.editorFocused );
					self.editor.codemirror.on( 'blur', self._events.editorFocused );
				}

			} else {
				self.$input.on( 'keyup', function() {
					self.parentSetValue( self.getValue() );
					self.setValue( self.$input.val() );
				} );
			}
		},

		/**
		 * Set the value.
		 *
		 * @param {String} value The value.
		 */
		setValue: function( value ) {
			var self = this;
			if ( !! self._params && self._params.hasOwnProperty( 'encoded' ) && self._params.encoded ) {
				value = usof_rawurldecode( usof_base64_decode( value ) );
			}
			if ( self.editor !== _undefined && wp.hasOwnProperty( 'codeEditor' ) ) {
				self.editorDoc.off( 'change', self._events.editorChange );
				if ( !! self.pid ) {
					clearTimeout( self.pid );
				}
				self.pid = setTimeout( function() {
					self.editorDoc.cm.refresh();
					self.editorDoc.setValue( value );
					self.editorDoc.on( 'change', self._events.editorChange );
					clearTimeout( self.pid );
				}, 1 );
			}
		},

		/**
		 * Get the value.
		 *
		 * @return {String} The value.
		 */
		getValue: function() {
			var self = this,
				value = self.editor !== _undefined && wp.hasOwnProperty( 'codeEditor' )
					? self.editorDoc.getValue()
					: self.$input.val();
			if ( self._params !== _undefined && self._params.encoded ) {
				value = usof_base64_encode( usof_rawurlencode( value ) );
			}
			return value;
		}
	};
}( jQuery );
