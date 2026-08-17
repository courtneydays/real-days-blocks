/**
 * Real Days Blocks — shared editor helpers.
 *
 * Small vanilla-JS utilities so every block's edit.js can build a
 * simple "repeater" field (add/remove/edit rows of text) without
 * duplicating the same boilerplate in every file. No build step —
 * this runs as-is in the browser, using the wp.element API.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var Button = wp.components.Button;
	var TextControl = wp.components.TextControl;

	/**
	 * Render a repeatable list of simple text rows.
	 *
	 * @param {Object}   args
	 * @param {string}   args.label     Field group label.
	 * @param {Array}    args.items     Current array of strings.
	 * @param {Function} args.onChange  Called with the new array.
	 * @param {string}   [args.placeholder]
	 */
	function textRepeater( args ) {
		var items = args.items || [];

		var rows = items.map( function ( value, index ) {
			return el(
				'div',
				{ key: index, style: { display: 'flex', gap: '6px', marginBottom: '6px' } },
				el( TextControl, {
					value: value,
					placeholder: args.placeholder || '',
					onChange: function ( newValue ) {
						var next = items.slice();
						next[ index ] = newValue;
						args.onChange( next );
					},
					__nextHasNoMarginBottom: true,
				} ),
				el( Button, {
					isDestructive: true,
					variant: 'secondary',
					size: 'small',
					onClick: function () {
						var next = items.slice();
						next.splice( index, 1 );
						args.onChange( next );
					},
				}, 'Remove' )
			);
		} );

		return el(
			'div',
			{ style: { marginBottom: '16px' } },
			el( 'strong', {}, args.label ),
			el( 'div', { style: { marginTop: '6px' } }, rows ),
			el( Button, {
				variant: 'secondary',
				size: 'small',
				onClick: function () {
					args.onChange( items.concat( [ '' ] ) );
				},
			}, '+ Add ' + ( args.itemLabel || 'item' ) )
		);
	}

	/**
	 * Render a repeatable list of multi-field object rows.
	 *
	 * @param {Object}   args
	 * @param {string}   args.label
	 * @param {Array}    args.items      Array of plain objects.
	 * @param {Array}    args.fields     [{ key, label, placeholder }]
	 * @param {Object}   args.emptyItem  Shape of a new blank row.
	 * @param {Function} args.onChange
	 */
	function objectRepeater( args ) {
		var items = args.items || [];

		var rows = items.map( function ( item, index ) {
			var fieldEls = args.fields.map( function ( field ) {
				return el( TextControl, {
					key: field.key,
					label: field.label,
					value: item[ field.key ] || '',
					placeholder: field.placeholder || '',
					onChange: function ( newValue ) {
						var next = items.slice();
						next[ index ] = Object.assign( {}, next[ index ], {} );
						next[ index ][ field.key ] = newValue;
						args.onChange( next );
					},
					__nextHasNoMarginBottom: true,
				} );
			} );

			return el(
				'div',
				{
					key: index,
					style: {
						border: '1px solid #ddd',
						borderRadius: '4px',
						padding: '10px',
						marginBottom: '8px',
					},
				},
				fieldEls,
				el( Button, {
					isDestructive: true,
					variant: 'secondary',
					size: 'small',
					style: { marginTop: '6px' },
					onClick: function () {
						var next = items.slice();
						next.splice( index, 1 );
						args.onChange( next );
					},
				}, 'Remove' )
			);
		} );

		return el(
			'div',
			{ style: { marginBottom: '16px' } },
			el( 'strong', {}, args.label ),
			el( 'div', { style: { marginTop: '6px' } }, rows ),
			el( Button, {
				variant: 'primary',
				size: 'small',
				onClick: function () {
					args.onChange( items.concat( [ Object.assign( {}, args.emptyItem ) ] ) );
				},
			}, '+ Add ' + ( args.itemLabel || 'item' ) )
		);
	}

	window.RDB = {
		el: el,
		Fragment: Fragment,
		textRepeater: textRepeater,
		objectRepeater: objectRepeater,
	};
} )( window.wp );
