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
	var Button = wp.components.Button;
	var PanelBody = wp.components.PanelBody;

	/**
	 * Resets ONLY the browser's own textarea/input chrome (border,
	 * background, resize handle). Deliberately does NOT touch font,
	 * color, line-height, margin or padding — those come from the
	 * matching .rdb-* class on the same element, exactly like the live
	 * site. An earlier version of this file also reset those via
	 * `font: inherit` etc., which — because inline styles always beat a
	 * stylesheet class on the same element — silently overrode the real
	 * design-system typography with the browser default. That was the
	 * "fonts look wrong" bug.
	 */
	var textFieldChrome = {
		display: 'block',
		width: '100%',
		border: 'none',
		background: 'transparent',
		resize: 'none',
		overflow: 'hidden',
		outline: 'none',
		boxSizing: 'border-box',
	};

	// This field has no .rdb-* class of its own (it sits inside the
	// .rdb-buy-cta button div), so unlike the fields above, it genuinely
	// needs to inherit color/font/alignment from that wrapper — there's
	// no class on the field itself for an inline style to clobber here.
	var ctaFieldStyle = Object.assign( {}, textFieldChrome, {
		padding: 0,
		margin: 0,
		font: 'inherit',
		color: 'inherit',
		textAlign: 'inherit',
		textTransform: 'inherit',
		letterSpacing: 'inherit',
	} );

	var inputChrome = {
		border: 'none',
		background: 'transparent',
		outline: 'none',
		padding: 0,
		margin: 0,
		font: 'inherit',
		color: 'inherit',
		boxSizing: 'content-box',
	};

	function stopEnter( e ) {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
		}
	}

	// Rough "hug the text" width so a short field like "$4" or "handle"
	// doesn't render as a full-width box — grows/shrinks as you type.
	function chWidth( value, min ) {
		return Math.max( min, ( value || '' ).length + 1 ) + 'ch';
	}

	registerBlockType( 'rdb/buy-card', {
		edit: function ( props ) {
			var a = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( { className: 'rdb-buy-card' } );
			var specs = a.specs || [];

			function updateSpec( index, key, value ) {
				var next = specs.slice();
				next[ index ] = Object.assign( {}, next[ index ] );
				next[ index ][ key ] = value;
				setAttributes( { specs: next } );
			}
			function removeSpec( index ) {
				var next = specs.slice();
				next.splice( index, 1 );
				setAttributes( { specs: next } );
			}
			function addSpec() {
				setAttributes( { specs: specs.concat( [ { value: '', label: '' } ] ) } );
			}

			var specNodes = [];
			specs.forEach( function ( spec, i ) {
				if ( i > 0 ) {
					specNodes.push( el( 'span', { className: 'rdb-sep', key: 'sep-' + i }, '/' ) );
				}
				specNodes.push(
					el(
						'span',
						{ key: 'spec-' + i, style: { display: 'inline-flex', alignItems: 'center', gap: '3px' } },
						el(
							'strong',
							{ style: { display: 'inline-flex' } },
							el( 'input', {
								type: 'text',
								value: spec.value,
								placeholder: '$0',
								'aria-label': 'Spec value',
								onChange: function ( e ) { updateSpec( i, 'value', e.target.value ); },
								style: Object.assign( {}, inputChrome, { width: chWidth( spec.value, 2 ) } ),
							} )
						),
						el( 'input', {
							type: 'text',
							value: spec.label,
							placeholder: 'label',
							'aria-label': 'Spec label',
							onChange: function ( e ) { updateSpec( i, 'label', e.target.value ); },
							style: Object.assign( {}, inputChrome, { width: chWidth( spec.label, 3 ) } ),
						} ),
						el( Button, {
							icon: 'no-alt',
							label: 'Remove spec',
							size: 'small',
							onClick: function () { removeSpec( i ); },
							style: { padding: 0, minWidth: '16px', height: '16px' },
						} )
					)
				);
			} );
			specNodes.push(
				el( Button, {
					key: 'add-spec',
					variant: 'link',
					size: 'small',
					onClick: addSpec,
				}, '+ add spec' )
			);

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Tag & link', initialOpen: true },
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
						el(
							'span',
							{
								className: 'rdb-buy-pick-tag',
								style: a.pickTag
									? {}
									: {
											background: 'transparent',
											border: '1px dashed var(--rdb-line-strong, #ccc)',
											color: 'var(--rdb-ink-soft, #6b6b6b)',
									  },
							},
							a.pickTag || 'Add tag →'
						),
						el( PlainText, {
							className: 'rdb-buy-name',
							value: a.name,
							placeholder: 'Product name',
							'aria-label': 'Product name',
							onChange: function ( v ) { setAttributes( { name: v } ); },
							onKeyDown: stopEnter,
							style: textFieldChrome,
						} ),
						el( PlainText, {
							className: 'rdb-buy-type',
							value: a.productType,
							placeholder: 'Product type / subtitle',
							'aria-label': 'Product type / subtitle',
							onChange: function ( v ) { setAttributes( { productType: v } ); },
							onKeyDown: stopEnter,
							style: textFieldChrome,
						} ),
						el( 'div', { className: 'rdb-buy-specs' }, specNodes ),
						el( PlainText, {
							className: 'rdb-fine-print',
							value: a.finePrint,
							placeholder: 'Fine print (optional)',
							'aria-label': 'Fine print',
							onChange: function ( v ) { setAttributes( { finePrint: v } ); },
							style: textFieldChrome,
						} ),
						el(
							// Forced to full width here regardless of screen size —
							// on the live site this button correctly shrinks to hug
							// its text at wider widths (see the CSS media query),
							// but an editable field can't safely size itself against
							// a parent whose own width is "however wide my content
							// is" without the two fighting each other, which is what
							// caused the button to balloon past the card edge. This
							// keeps editing predictable; the published post still
							// uses the real CSS untouched, so it isn't affected.
							'div',
							{ className: 'rdb-buy-cta', style: { width: '100%', display: 'block' } },
							el( PlainText, {
								value: a.ctaLabel,
								placeholder: 'View product',
								'aria-label': 'CTA button label',
								onChange: function ( v ) { setAttributes( { ctaLabel: v } ); },
								onKeyDown: stopEnter,
								style: ctaFieldStyle,
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
