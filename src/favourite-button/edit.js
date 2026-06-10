import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import './editor.scss';

/**
 * Static editor preview. A live preview would need /state, which is
 * per-user and pointless inside the editor — show the shell instead.
 *
 * @param {Object}   props               Component properties.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute updater.
 * @return {Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { showCount } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'favourite-button' ) }>
					<ToggleControl
						label={ __(
							'Show favourite count',
							'favourite-button'
						) }
						checked={ showCount }
						onChange={ ( value ) =>
							setAttributes( { showCount: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<button type="button" className="fav-btn-button" disabled>
					<span className="fav-btn-label">
						{ __( 'Add to favourites', 'favourite-button' ) }
					</span>
					{ showCount && <span className="fav-btn-count">12</span> }
				</button>
			</div>
		</>
	);
}
