( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var TextControl = wp.components.TextControl;
	var Button = wp.components.Button;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'rdb/buy-card', {
		edit: function ( props ) {
			var a = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				'div',
				blockProps,
				el( TextControl, {
					label: 'Yellow tag (leave blank for none)',
					help: 'e.g. "Best value" or "Soft Bristles"',
					value: a.pickTag,
					onChange: function ( v ) { setAttributes( { pickTag: v } ); },
				} ),
				el( MediaUpload, {
					onSelect: function ( media ) {
						setAttributes( { imageUrl: media.url, imageAlt: media.alt || a.name } );
					},
					allowedTypes: [ 'image' ],
					render: function ( obj ) {
						return el(
							'div',
							{ style: { marginBottom: '10px' } },
							a.imageUrl
								? el( 'img', { src: a.imageUrl, style: { maxWidth: '160px', display: 'block', marginBottom: '6px' } } )
								: null,
							el( Button, { variant: 'secondary', onClick: obj.open }, a.imageUrl ? 'Replace image' : 'Select image' )
						);
					},
				} ),
				el( TextControl, {
					label: 'Image alt text',
					value: a.imageAlt,
					onChange: function ( v ) { setAttributes( { imageAlt: v } ); },
				} ),
				el( TextControl, {
					label: 'Product name',
					value: a.name,
					onChange: function ( v ) { setAttributes( { name: v } ); },
				} ),
				el( TextControl, {
					label: 'Product type / subtitle',
					value: a.productType,
					onChange: function ( v ) { setAttributes( { productType: v } ); },
				} ),
				window.RDB.objectRepeater( {
					label: 'Price specs (shown separated by "/")',
					items: a.specs,
					itemLabel: 'spec',
					fields: [
						{ key: 'value', label: 'Value', placeholder: '$4' },
						{ key: 'label', label: 'Label', placeholder: 'handle' },
					],
					emptyItem: { value: '', label: '' },
					onChange: function ( next ) { setAttributes( { specs: next } ); },
				} ),
				el( TextControl, {
					label: 'Fine print (optional)',
					value: a.finePrint,
					onChange: function ( v ) { setAttributes( { finePrint: v } ); },
				} ),
				el( TextControl, {
					label: 'CTA button label',
					value: a.ctaLabel,
					onChange: function ( v ) { setAttributes( { ctaLabel: v } ); },
				} ),
				el( TextControl, {
					label: 'CTA button URL',
					value: a.ctaUrl,
					onChange: function ( v ) { setAttributes( { ctaUrl: v } ); },
				} ),
				el( ServerSideRender, { block: 'rdb/buy-card', attributes: a } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
