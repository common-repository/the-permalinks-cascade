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
	SelectControl,
    ToggleControl
} from '@wordpress/components';

export default function TaxonomyEdit( { attributes, setAttributes } ) {
    let taxonomiesOpts = [];
    const taxonomies   = useSelect(
        ( select ) => select( 'core' ).getTaxonomies( { per_page: -1 } ),
        []
    );

    if ( Array.isArray( taxonomies ) && taxonomies.length ) {
        for ( const taxonomy of taxonomies ) {
            if ( taxonomy.visibility.public ) {
                taxonomiesOpts.push( { value: taxonomy.slug, label: taxonomy.name } );
            }
        }
    }

	return (
		<div { ...useBlockProps() }>
			<ServerSideRender
                block='the-permalinks-cascade/taxonomy'
                attributes={ attributes }
            />
			<InspectorControls key='settings'>
				<PanelBody
					title={ __( 'Settings', 'the-permalinks-cascade' ) }
					initialOpen={true}
				>
                    <SelectControl
						key='taxonomy_slug'
						label={ __( 'Taxonomy', 'the-permalinks-cascade' ) }
						value={ attributes.taxonomy_slug }
						options={ taxonomiesOpts }
						onChange={ value => setAttributes( { taxonomy_slug: value } ) }
					/>
					<TextControl
						key='title'
						label={ __( 'Title', 'the-permalinks-cascade' ) }
						value={ attributes.title }
						onChange={ value => setAttributes( { title: value } ) }
					/>
                    <ToggleControl
                        key='show_count'
                        label={ __( 'Show count of posts', 'the-permalinks-cascade' ) }
                        checked={ attributes.show_count }
                        onChange={ value => setAttributes( { show_count: value } ) }
                    />
                    <SelectControl
						key='order_by'
						label={ __( 'Order by', 'the-permalinks-cascade' ) }
						value={ attributes.order_by }
						options={ [
                            { value: 'name',  label: __( 'Name', 'the-permalinks-cascade' ) },
                            { value: 'count', label: __( 'Most used', 'the-permalinks-cascade' ) }
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
