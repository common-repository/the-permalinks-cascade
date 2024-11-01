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

export default function PageEdit( { attributes, setAttributes } ) {
	return (
		<div { ...useBlockProps() }>
			<ServerSideRender
				key='server-side-render'
				block='the-permalinks-cascade/page'
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
						key='hierarchical'
						label={ __( 'Hyper-list style', 'the-permalinks-cascade' ) }
						value={ attributes.hierarchical }
						options={ [
							{ value: '1', label: __( 'Hierarchical', 'the-permalinks-cascade' ) },
							{ value: '0', label: __( 'Flat', 'the-permalinks-cascade' ) }
						] }
						onChange={ value => setAttributes( { hierarchical: value } ) }
					/>
					<SelectControl
						key='order_by'
						label={ __( 'Order by', 'the-permalinks-cascade' ) }
						value={ attributes.order_by }
						options={ [
							{ value: 'menu_order', label: __( 'Menu order & Title', 'the-permalinks-cascade' ) },
							{ value: 'title',      label: __( 'Title', 'the-permalinks-cascade' ) }
						] }
						onChange={ value => setAttributes( { order_by: value } ) }
					/>
					<ToggleControl
						key='exclude_children'
						label={ __( 'Only primary pages', 'the-permalinks-cascade' ) }
						checked={ attributes.exclude_children }
						onChange={ value => setAttributes( { exclude_children: value } ) }
					/>
					<ToggleControl
						key='dehyperlink_parents'
						label={ __( 'De-hyperlink parent pages', 'the-permalinks-cascade' ) }
						checked={ attributes.dehyperlink_parents }
						onChange={ value => setAttributes( { dehyperlink_parents: value } ) }
					/>
					<SelectControl
						key='dehyperlinking_level'
						label={ __( 'De-hyperlink parent pages up to...', 'the-permalinks-cascade' ) }
						value={ attributes.dehyperlinking_level }
						options={ [
							{ value: '0', label: __( 'First level', 'the-permalinks-cascade' ) },
							{ value: '1', label: __( 'Second level', 'the-permalinks-cascade' ) },
							{ value: '2', label: __( 'Third level', 'the-permalinks-cascade' ) }
						] }
						onChange={ value => setAttributes( { dehyperlinking_level: value } ) }
					/>
					<ToggleControl
						key='group_by_topic'
						label={ __( 'Group by Topic', 'the-permalinks-cascade' ) }
						checked={ attributes.group_by_topic }
						onChange={ value => setAttributes( { group_by_topic: value } ) }
					/>
					<ToggleControl
						key='show_topicless'
						label={ __( 'Show pages without Topic', 'the-permalinks-cascade' ) }
						checked={ attributes.show_topicless }
						onChange={ value => setAttributes( { show_topicless: value } ) }
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
