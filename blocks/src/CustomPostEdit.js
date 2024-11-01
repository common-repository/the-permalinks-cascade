/**
 * Copyright 2022 Luigi Cavalieri.
 * @license GPL v3.0 (https://opensource.org/licenses/GPL-3.0).
 * *************************************************************** */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';

import { 
	PanelBody,
	TextControl, 
	SelectControl
} from '@wordpress/components';

import './CustomPostEdit.css';

export default function CustomPostEdit( { attributes, setAttributes } ) {
    let customPostsOpts = [];
    const postTypes     = useSelect(
        ( select ) => select( 'core' ).getPostTypes( { per_page: -1 } ),
        []
    );

    if ( Array.isArray( postTypes ) && postTypes.length ) {
        const builtinPostTypes = [ 'post', 'page', 'attachment' ];

        for ( const postType of postTypes ) {
            if ( postType.viewable && !builtinPostTypes.includes( postType.slug ) ) {
                customPostsOpts.push( { value: postType.slug, label: postType.name } );
            }
        }
    }

	return (
		<div { ...useBlockProps() }>
			{ attributes.post_slug && 'none' === attributes.post_slug ? (
				<div className='tpc-custom-post-notice'>
					<em>{ __( 'Please, select a Custom Post Type from the settings panel.', 'the-permalinks-cascade' ) }</em>
				</div>
			) : (
				<ServerSideRender
					block='the-permalinks-cascade/custom-post'
					attributes={ attributes }
				/>
			) }
			<InspectorControls key='settings'>
				<PanelBody
					title={ __( 'Settings', 'the-permalinks-cascade' ) }
					initialOpen={true}
				>
                    <SelectControl
						key='post_slug'
						label={ __( 'Post Type', 'the-permalinks-cascade' ) }
						value={ attributes.post_slug }
						options={ [
                            { value: 'none', label: '— ' + __( 'Select', 'the-permalinks-cascade' ) + ' —' }
                        ].concat( customPostsOpts ) }
						onChange={ value => setAttributes( { post_slug: value } ) }
					/>
					<TextControl
						key='title'
						label={ __( 'Title', 'the-permalinks-cascade' ) }
						value={ attributes.title }
						onChange={ value => setAttributes( { title: value } ) }
					/>
                    <SelectControl
						key='order_by'
						label={ __( 'Order by', 'the-permalinks-cascade' ) }
						value={ attributes.order_by }
						options={ [
                            { value: 'post_title',    label: __( 'Title', 'the-permalinks-cascade' ) },
                            { value: 'post_date',     label: __( 'Most recent', 'the-permalinks-cascade' ) },
                            { value: 'post_date_asc', label: __( 'Older', 'the-permalinks-cascade' ) }
                        ] }
						onChange={ value => setAttributes( { order_by: value } ) }
					/>
                    <SelectControl
						key='hierarchical'
						label={ __( 'Hyper-list style', 'the-permalinks-cascade' ) }
						value={ attributes.hierarchical }
						options={ [
                            { value: '1', label: __( 'Hierarchical', 'the-permalinks-cascade' ) },
                            { value: '0', label: __( 'Flat', 'the-permalinks-cascade' ) }
                        ] }
						onChange={ value => setAttributes( { hierarchical: value } ) }
					/>
                    <TextControl
						key='limit'
						label={ __( 'Max. number of items', 'the-permalinks-cascade' ) }
						value={ attributes.limit }
						onChange={ value => setAttributes( { limit: value } ) }
					/>
					<TextControl
						key='exclude'
						label={ __( 'Exclude (comma-separated IDs)', 'the-permalinks-cascade' ) }
						value={ attributes.exclude }
						onChange={ value => setAttributes( { exclude: value } ) }
					/>
					<TextControl
						key='include_only'
						label={ __( 'Include only (comma-separated IDs)', 'the-permalinks-cascade' ) }
						value={ attributes.include_only }
						onChange={ value => setAttributes( { include_only: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
}
