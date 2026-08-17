( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var PlainText = wp.blockEditor.PlainText;
	var TextControl = wp.components.TextControl;
	var PanelBody = wp.components.PanelBody;

	/**
	 * Resets browser textarea chrome (border, background, resize handle)
	 * so a PlainText field sits inline and looks like plain text instead
	 * of a form field. Font, size, color and spacing still come from the
	 * matching .rdb-* class below — same class the live site uses — so
	 * a styling change on the settings page updates both automatically.
	 * This object only resets things the browser adds that the design
	 * system never defined in the first place.
	 */
	var inlineReset = {
		display: 'block',
		width: '100%',
		border: 'none',
		background: 'transparent',
		padding: 0,
		margin: 0,
		resize: 'none',
		overflow: 'hidden',
		font: 'inherit',
		color: 'inherit',
		lineHeight: 'inherit',
		outline: 'none',
	};

	function stopEnter( e ) {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
		}
	}

	registerBlockType( 'rdb/buy-card', {
		edit: function ( props ) {
			var a = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( { className: 'rdb-buy-card' } );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Tag, link & specs', initialOpen: true },
						el( TextControl, {
							label: 'Yellow tag (leave blank for none)',
							help: 'e.g. "Best value" or "Soft Bristles" — shown above the name.',
							value: a.pickTag,
							onChange: function ( v ) { setAttributes( { pickTag: v } ); },
						} ),
						el( TextControl, {
							label: 'Image alt text',
							value: a.imageAlt,
							onChange: function ( v ) { setAttributes( { imageAlt: v } ); },
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
							label: 'CTA button URL',
							value: a.ctaUrl,
							onChange: function ( v ) { setAttributes( { ctaUrl: v } ); },
						} )
					)
				),
				el(
					'article',
					blockProps,
					el(
						MediaUpload,
						{
							onSelect: function ( media ) {
								setAttributes( { imageUrl: media.url, imageAlt: media.alt || a.imageAlt || a.name } );
							},
							allowedTypes: [ 'image' ],
							render: function ( obj ) {
								return el(
									'div',
									{ className: 'rdb-buy-media', onClick: obj.open, style: { cursor: 'pointer' } },
									a.imageUrl
										? el( 'img', { src: a.imageUrl, alt: a.imageAlt } )
										: el(
												'div',
												{
													style: {
														padding: '32px 16px',
														textAlign: 'center',
														fontSize: '12px',
														border: '1px dashed var(--rdb-line-strong, #ccc)',
														color: 'var(--rdb-ink-soft, #6b6b6b)',
													},
												},
												'Click to add photo'
										  )
								);
							},
						}
					),
					el(
						'div',
						{ className: 'rdb-buy-body' },
						a.pickTag ? el( 'span', { className: 'rdb-buy-pick-tag' }, a.pickTag ) : null,
						el( PlainText, {
							className: 'rdb-buy-name',
							value: a.name,
							placeholder: 'Product name',
							'aria-label': 'Product name',
							onChange: function ( v ) { setAttributes( { name: v } ); },
							onKeyDown: stopEnter,
							style: inlineReset,
						} ),
						el( PlainText, {
							className: 'rdb-buy-type',
							value: a.productType,
							placeholder: 'Product type / subtitle',
							'aria-label': 'Product type / subtitle',
							onChange: function ( v ) { setAttributes( { productType: v } ); },
							onKeyDown: stopEnter,
							style: inlineReset,
						} ),
						a.specs && a.specs.length
							? el(
									'div',
									{ className: 'rdb-buy-specs' },
									a.specs.map( function ( spec, i ) {
										return el(
											Fragment,
											{ key: i },
											i > 0 ? el( 'span', { className: 'rdb-sep' }, '/' ) : null,
											el( 'span', {}, el( 'strong', {}, spec.value ), ' ', spec.label )
										);
									} )
							  )
							: null,
						el( PlainText, {
							className: 'rdb-fine-print',
							value: a.finePrint,
							placeholder: 'Fine print (optional)',
							'aria-label': 'Fine print',
							onChange: function ( v ) { setAttributes( { finePrint: v } ); },
							style: inlineReset,
						} ),
						el(
							'div',
							{ className: 'rdb-buy-cta' },
							el( PlainText, {
								value: a.ctaLabel,
								placeholder: 'View product',
								'aria-label': 'CTA button label',
								onChange: function ( v ) { setAttributes( { ctaLabel: v } ); },
								onKeyDown: stopEnter,
								style: Object.assign( {}, inlineReset, { textAlign: 'center' } ),
							} )
						)
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
