/**
 * Copyright 2022 Luigi Cavalieri.
 * @license GPL v3.0 (https://opensource.org/licenses/GPL-3.0).
 * *************************************************************** */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

import { 
	PanelBody,
	TextControl, 
	SelectControl, 
	ToggleControl
} from '@wordpress/components';

export default function PostEdit( { attributes, setAttributes } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerSideRender
				block='the-permalinks-cascade/post'
				attributes={ attributes }
			/>
			<InspectorControls key='settings'>
				<PanelBody
					title={ __( 'Settings', 'the-permalinks-cascade' ) }
					initialOpen={true}
				>
					<TextControl
						key='title'
						label={ __( 'Title', 'the-permalinks-cascade' ) }
						value={ attributes.title }
						onChange={ value => setAttributes( { title: value } ) }
					/>
					<SelectControl
						key='group_by'
						label={ __( 'Group by', 'the-permalinks-cascade' ) }
						value={ attributes.group_by }
						options={ [
							{ value: 'none',     label: '-' }, 
							{ value: 'date',     label: __( 'Date', 'the-permalinks-cascade' ) },
							{ value: 'category', label: __( 'Category', 'the-permalinks-cascade' ) },
							{ value: 'author',   label: __( 'Author', 'the-permalinks-cascade' ) }
						] }
						onChange={ value => setAttributes( { group_by: value } ) }
					/>
					<ToggleControl
						key='hyperlink_group_title'
						label={ __( "Hyperlink group's title", 'the-permalinks-cascade' ) }
						checked={ attributes.hyperlink_group_title }
						onChange={ value => setAttributes( { hyperlink_group_title: value } ) }
					/>
					<SelectControl
						key='order_by'
						label={ __( 'Order by', 'the-permalinks-cascade' ) }
						value={ attributes.order_by }
						options={ [
							{ value: 'post_date',     label: __( 'Most recent', 'the-permalinks-cascade' ) },
							{ value: 'comment_count', label: __( 'Most popular', 'the-permalinks-cascade' ) },
							{ value: 'post_title',    label: __( 'Title', 'the-permalinks-cascade' ) },
							{ value: 'post_date_asc', label: __( 'Older', 'the-permalinks-cascade' ) }
						] }
						onChange={ value => setAttributes( { order_by: value } ) }
					/>
					<ToggleControl
						key='pop_stickies'
						label={ __( 'Keep Featured Posts at the top', 'the-permalinks-cascade' ) }
						checked={ attributes.pop_stickies }
						onChange={ value => setAttributes( { pop_stickies: value } ) }
					/>
					<ToggleControl
						key='show_excerpt'
						label={ __( 'Show a short excerpt', 'the-permalinks-cascade' ) }
						checked={ attributes.show_excerpt }
						onChange={ value => setAttributes( { show_excerpt: value } ) }
					/>
					<TextControl
						key='excerpt_length'
						label={ __( 'Excerpt length (num. of characters)', 'the-permalinks-cascade' ) }
						value={ attributes.excerpt_length }
						onChange={ value => setAttributes( { excerpt_length: value } ) }
					/>
					<ToggleControl
						key='show_comments_count'
						label={ __( 'Show the number of comments', 'the-permalinks-cascade' ) }
						checked={ attributes.show_comments_count }
						onChange={ value => setAttributes( { show_comments_count: value } ) }
					/>
					<ToggleControl
						key='show_date'
						label={ __( 'Show the date of publication', 'the-permalinks-cascade' ) }
						checked={ attributes.show_date }
						onChange={ value => setAttributes( { show_date: value } ) }
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
