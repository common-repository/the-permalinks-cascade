/**
 * Copyright 2022 Luigi Cavalieri.
 * @license GPL v3.0 (https://opensource.org/licenses/GPL-3.0).
 * *************************************************************** */

import { registerBlockType } from '@wordpress/blocks';

import PageEdit from './PageEdit';
import PostEdit from './PostEdit';
import CustomPostEdit from './CustomPostEdit';
import TaxonomyEdit from './TaxonomyEdit';

const save = _ => {};

registerBlockType( 'the-permalinks-cascade/page', { edit: PageEdit, save } );
registerBlockType( 'the-permalinks-cascade/post', { edit: PostEdit, save } );

if ( TPC_Globals.showCustomPostBlock ) {
    registerBlockType( 'the-permalinks-cascade/custom-post', { edit: CustomPostEdit, save } );
}

registerBlockType( 'the-permalinks-cascade/taxonomy', { edit: TaxonomyEdit, save } );