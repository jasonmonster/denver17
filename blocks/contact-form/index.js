( function ( blocks, blockEditor, element, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;
	var RichText = blockEditor.RichText;

	blocks.registerBlockType( 'denver17/contact-form', {
		edit: function ( props ) {
			var attrs = props.attributes;
			var set = props.setAttributes;

			return el(
				'div',
				useBlockProps( { className: 'denver17-contact-form-editor' } ),

				el( RichText, {
					tagName: 'h2',
					value: attrs.heading,
					allowedFormats: [],
					placeholder: __( 'Get in touch', 'denver17' ),
					onChange: function ( value ) {
						set( { heading: value } );
					}
				} ),

				el( RichText, {
					tagName: 'p',
					value: attrs.intro,
					allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ],
					placeholder: __( 'Optional intro line…', 'denver17' ),
					onChange: function ( value ) {
						set( { intro: value } );
					}
				} ),

				el(
					'div',
					{
						style: {
							border: '1px dashed #AFA9EC',
							borderRadius: '8px',
							padding: '18px',
							color: '#534AB7',
							fontSize: '13px'
						}
					},
					__(
						'Name, email, phone, topic and message fields render here on the live site. Messages email the lodge and are saved under Contact Messages.',
						'denver17'
					)
				)
			);
		},

		save: function () {
			return null; // Rendered in PHP.
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element, window.wp.i18n );
