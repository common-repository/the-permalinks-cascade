<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 * ************************************************************ */


if (! defined( 'ABSPATH' ) ) {
    exit;
}

// Collection of messages used more than once.
// The elements of type Array contain the title of the field at index 0 and its description/tooltip at index 1.
$common_l10n = array(
	'title'		 => __( 'Hyper-list title', 'the-permalinks-cascade' ),
    'style'      => __( 'Hyper-list style', 'the-permalinks-cascade' ),
	'show_count' => __( 'Posts count', 'the-permalinks-cascade' ),
	'exclude'	 => array(
        __( 'Exclude', 'the-permalinks-cascade' ),
        __( 'Comma-separated list of IDs.', 'the-permalinks-cascade' )
    ),
	'order_by'	 => __( 'Order by', 'the-permalinks-cascade' ),
    'limit'      => array(
        __( 'Max. number of items', 'the-permalinks-cascade' ),
        __( 'Set to -1 to list all the items.', 'the-permalinks-cascade' )
    )
);

// --- Common values.

$list_style_options = array(
	'1' => __( 'Hierarchical', 'the-permalinks-cascade' ),
	'0' => __( 'Flat', 'the-permalinks-cascade' )
);

$orderby_options = array(
	'name'	=> __( 'Name', 'the-permalinks-cascade' ),
	'count' => __( 'Most used', 'the-permalinks-cascade' )
);

$post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
$taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ), 'objects' );

/* ************************************************************ */

$this->registerSection( new Section( __( 'Pages', 'the-permalinks-cascade' ), 'page', array(
    new Field( 'title', 'TextField', 'inline_html', $common_l10n['title'], '', __( 'Pages', 'the-permalinks-cascade' ) ),
    new Field( 'hierarchical', 'Dropdown', 'choice', $common_l10n['style'], '', '1', $list_style_options ),
    new Field( 'order_by', 'Dropdown', 'choice', $common_l10n['order_by'], '', 'menu_order', array(
        'menu_order' => __( 'Menu order &amp; Title', 'the-permalinks-cascade' ),
        'title'      => __( 'Title', 'the-permalinks-cascade' )
    )),
    new Field( 'show_home', 'Checkbox', 'bool', __( 'Home page', 'the-permalinks-cascade' ), 
               __( 'Show a &lsquo;Home&rsquo; link on top of the hyper-list.', 'the-permalinks-cascade' ) ),
    new Field( 'exclude_children', 'Checkbox', 'bool', __( 'Only primary pages', 'the-permalinks-cascade' ), 
               __( 'Exclude all the child pages.', 'the-permalinks-cascade' ) ),
    new Fieldset( __( 'De-hyperlink parent pages', 'the-permalinks-cascade' ), '', 'inline', array(
        new Field( 'dehyperlink_parents', 'Checkbox', 'bool', '', 
                   __( 'Disable the hyperlinking of parent pages up to the', 'the-permalinks-cascade' ) ),
        new Field( 'dehyperlinking_level', 'Dropdown', 'choice', '', __( 'level.', 'the-permalinks-cascade' ), '0', array(
            '0' => __( 'first', 'the-permalinks-cascade' ),
            '1' => __( 'second', 'the-permalinks-cascade' ),
            '2' => __( 'third', 'the-permalinks-cascade' )
        )),
    )),
    new Fieldset( __( 'Group by Topic', 'the-permalinks-cascade' ), '', 'inline', array(
        new Field( 'group_by_topic', 'Checkbox', 'bool', '', __( 'Group Pages by Topic and', 'the-permalinks-cascade' ) ),
        new Field( 'show_topicless', 'Dropdown', 'boolean_choice', '', __( 'those without one.', 'the-permalinks-cascade' ), '1',
                   array(
                        '1' => __( 'show', 'the-permalinks-cascade' ),
                        '0' => __( 'hide', 'the-permalinks-cascade' )
                   )
        )
    ))
) ));

$this->registerSection( new Section( __( 'Posts', 'the-permalinks-cascade' ), 'post', array(
    new Field( 'title', 'TextField', 'inline_html', $common_l10n['title'], '', __( 'Posts', 'the-permalinks-cascade' ) ),
    new Fieldset( __( 'Group by', 'the-permalinks-cascade' ), '', 'inline', array(
        new Field( 'group_by', 'Dropdown', 'choice', '', '&amp;', 'none', 
                   array(
                       'none'      => '-', 
                       'date'      => __( 'Date', 'the-permalinks-cascade' ),
                       'category'  => __( 'Category', 'the-permalinks-cascade' ),
                       'author'    => __( 'Author', 'the-permalinks-cascade' )
                   )
        ),
        new Field( 'hyperlink_group_title', 'Dropdown', 'boolean_choice', '',
                   __( 'the title of each group.', 'the-permalinks-cascade' ), '1', 
                   array(
                        '1' => __( 'Hyperlink', 'the-permalinks-cascade' ), 
                        '0' => __( 'De-hyperlink', 'the-permalinks-cascade' )
                   )
        ),
    )),
    new Field( 'order_by', 'Dropdown', 'choice', $common_l10n['order_by'], '', 'post_date', array(
        'post_date'     => __( 'Most recent', 'the-permalinks-cascade' ),
        'comment_count' => __( 'Most popular', 'the-permalinks-cascade' ),
        'post_title'    => __( 'Title', 'the-permalinks-cascade' ),
        'post_date_asc' => __( 'Older', 'the-permalinks-cascade' )
    )),
    new Field( 'pop_stickies', 'Checkbox', 'bool', __( 'Sticky posts', 'the-permalinks-cascade' ), 
        __( 'Keep Featured Posts at the top of the hyper-list.', 'the-permalinks-cascade' )
    ),
    new Fieldset( __( 'Show excerpt', 'the-permalinks-cascade' ), '', 'inline', array(
        new Field( 'show_excerpt', 'Checkbox', 'bool', '', 
            __( 'Show for each post a short excerpt of', 'the-permalinks-cascade' )
        ),
        new Field( 'excerpt_length', 'NumberField', 'positive_number', '', 
            __( 'characters.', 'the-permalinks-cascade' ), 100, array( 'min_value' => 50, 'max_value' => 300 )
        ),
    )),
    new Field( 'show_comments_count', 'Checkbox', 'bool', __( 'Comments count', 'the-permalinks-cascade' ), 
        __( 'Show for each post the number of comments received.', 'the-permalinks-cascade' )
    ),
    new Field( 'show_date', 'Checkbox', 'bool', __( 'Publication date', 'the-permalinks-cascade' ), 
        __( 'Show for each post the date of publication.', 'the-permalinks-cascade' )
    ),
    new Field( 'limit', 'NumberField', 'positive_number', $common_l10n['limit'][0], 
               $common_l10n['limit'][1], -1, array( 'min_value' => -1, 'max_value' => 500 )
    )     
) ));

foreach ( $post_types as $post_type ) {
    $post_type_section = new Section( $post_type->label, $post_type->name );

    $post_type_section->addField( new Field( 'title', 'TextField', 
                                             'inline_html', $common_l10n['title'], '', $post_type->label ) );
    $post_type_section->addField( new Field( 'order_by', 'Dropdown', 
                                             'choice', $common_l10n['order_by'], '', 'post_title', 
                                             array(
                                	            'post_title'    => __( 'Title', 'the-permalinks-cascade' ),
                                	            'post_date'     => __( 'Most recent', 'the-permalinks-cascade' ),
                                	            'post_date_asc' => __( 'Older', 'the-permalinks-cascade' )
                                	         ) ));      

    if ( $post_type->hierarchical ) {
        $post_type_section->addField( new Field( 'hierarchical', 'Dropdown', 'boolean_choice', $common_l10n['style'], 
                                                 '', '1', $list_style_options ) );
    }

    $post_type_section->addField( new Field( 'limit', 'NumberField', 
                                             'positive_number', $common_l10n['limit'][0], $common_l10n['limit'][1], -1,
                                              array( 'min_value' => -1, 'max_value' => 500 ) ));

    $this->registerSection( $post_type_section );
}

$this->registerSection( new Section( __( 'Categories', 'the-permalinks-cascade' ), 'category', array(
    new Field( 'title', 'TextField', 'inline_html', $common_l10n['title'], '', __( 'Categories', 'the-permalinks-cascade' ) ),
    new Field( 'show_count', 'Checkbox', 'bool', $common_l10n['show_count'], 
        __( 'Show for each category the number of published posts.', 'the-permalinks-cascade' )
    ),
    new Field( 'feed_text', 'TextField', 'plain_text', __("Text of the link to each category's RSS feed", 'the-permalinks-cascade' ), 
        __( 'Leave empty to hide the link.', 'the-permalinks-cascade' ), '', 'small-text'
    ),
    new Field( 'hierarchical', 'Dropdown', 'choice', $common_l10n['style'], '', '1', $list_style_options ),
    new Field( 'order_by', 'Dropdown', 'choice', $common_l10n['order_by'], '', 'name', $orderby_options ),
    new Field( 'exclude', 'TextField', 'list_of_ids', $common_l10n['exclude'][0], $common_l10n['exclude'][1], '' )
) ));

$this->registerSection( new Section( __( 'Tags', 'the-permalinks-cascade' ), 'post_tag', array(
    new Field( 'title', 'TextField', 'inline_html', $common_l10n['title'], '', __( 'Tags', 'the-permalinks-cascade' ) ),
    new Field( 'show_count', 'Checkbox', 'bool', $common_l10n['show_count'], 
                       __( 'Show the number of posts published under each tag.', 'the-permalinks-cascade' )
    ),
    new Field( 'order_by', 'Dropdown', 'choice', $common_l10n['order_by'], '', 'name', $orderby_options ),
    new Field( 'exclude', 'TextField', 
                       'list_of_ids', $common_l10n['exclude'][0], $common_l10n['exclude'][1], '' )
) ));

foreach ( $taxonomies as $taxonomy ) {
    $taxonomy_section = new Section( $taxonomy->label, $taxonomy->name );
    $taxonomy_section->addField( new Field( 'title', 'TextField', 
                                            'inline_html', $common_l10n['title'], '', $taxonomy->label ) );
    $taxonomy_section->addField( new Field( 'order_by', 'Dropdown', 
                                            'choice', $common_l10n['order_by'], '', 'name', $orderby_options ) );

    if ( $taxonomy->hierarchical ) {
        $taxonomy_section->addField( new Field( 'hierarchical', 'Dropdown', 'choice', $common_l10n['style'], 
                                                '', '1', $list_style_options ) );
    }

    $taxonomy_section->addField( new Field( 'exclude', 'TextField', 
                                            'list_of_ids', $common_l10n['exclude'][0], $common_l10n['exclude'][1], 
                                            '' ) );
    $this->registerSection( $taxonomy_section );
}

$this->registerSection( new Section( __( "Authors' Pages", 'the-permalinks-cascade' ), 'authors', array(
    new Field( 'title', 'TextField', 'inline_html', $common_l10n['title'], '', __( 'Authors', 'the-permalinks-cascade' ) ),
    new Field( 'show_count', 'Checkbox', 'bool', $common_l10n['show_count'],
        __( 'Show the number of posts published by each author.', 'the-permalinks-cascade' ), true
    ),
    new Field( 'show_avatar', 'Checkbox', 'bool', __( 'Avatar', 'the-permalinks-cascade' ), __("Show the author's avatar.", 'the-permalinks-cascade' ) ),
    new Field( 'avatar_size', 'NumberField', 'positive_number', __( 'Avatar size', 'the-permalinks-cascade' ), 
        __( 'Choose a value between 20px and 512px.', 'the-permalinks-cascade' ), 60, array( 'min_value' => 20, 'max_value' => 512 )
    ),
    new Field( 'show_bio', 'Checkbox', 'bool', __( 'Biographical info', 'the-permalinks-cascade' ), 
        sprintf( __('Show the biographical information set in the author&apos;s %1$sprofile page%2$s.', 'the-permalinks-cascade' ), 
                 '<a href="' . admin_url( 'users.php' ) . '">', '</a>' )
    ),
    new Field( 'order_by', 'Dropdown', 'choice', $common_l10n['order_by'], '', 'display_name', array(
        'display_name'  => __( 'Name', 'the-permalinks-cascade' ),
        'posts_count'   => __( 'Published posts', 'the-permalinks-cascade' )
    )),
    new Field( 'exclude', 'TextField', 'list_of_nicknames', $common_l10n['exclude'][0], 
        __( 'Comma-separated list of nicknames.', 'the-permalinks-cascade' ), ''
    )
) ));