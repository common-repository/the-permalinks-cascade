/**
 * Copyright 2022 Luigi Cavalieri.
 * @license GPL v3.0 (https://opensource.org/licenses/GPL-3.0).
 * *************************************************************** */

( ( $ ) => {
    let wp_inline_edit = inlineEditPost.edit;

    inlineEditPost.edit = function ( id ) {
        wp_inline_edit.apply( this, arguments );

        const topic_select = document.getElementById( 'tpc-topic-id' );

        if (! topic_select ) {
            return null;
        }

        let post_id = 0;

        if ( 'object' == typeof( id ) ) {
            post_id = parseInt( this.getId( id ), 10 );
        }

        if ( post_id <= 0 ) {
            return null;
        }
        
        let option_selected = false;
        const post_row      = document.getElementById( 'post-' + post_id );
        const topic_name    = post_row.getElementsByClassName( 'column-taxonomy-tpc_topic' )[0].innerText;

        for ( let option of topic_select.options ) {
            if ( option.innerText == topic_name ) {
                option_selected = true;

                option.setAttribute( 'selected', 'selected' );
            }
            else {
                option.removeAttribute( 'selected' );
            }
        }

        if (! option_selected ) {
            topic_select.options[0].setAttribute( 'selected', 'selected' );
        }
    };

    $( document ).on( 'click', '#bulk_edit', () => {
        let post_ids      = [];
        const bulk_titles = document.getElementById( 'bulk-titles' );
        
        for ( let title of bulk_titles.childNodes ) {
            post_ids.push( title.id.replace( /^(ttle)/i, '' ) );
        }

        const nonce        = document.getElementById( 'tpc-nonce' ).value;
        const data         = $( '#tpc-bulk-edit-box select' ).serializeArray();
        const ajax_request = {
                 action: 'handleTPCAdminAjaxRequest',
             tpc_action: 'tpc_bulk_edit',
              tpc_nonce: nonce,
               post_ids,
                   data
        };

        jQuery.post( ajaxurl, ajax_request );
    });
})( jQuery );