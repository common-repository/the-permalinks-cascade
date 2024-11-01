/**
 * Copyright 2022 Luigi Cavalieri.
 * @license GPL v3.0 (https://opensource.org/licenses/GPL-3.0).
 * *************************************************************** */

class ThePermalinksCascadeSetting {
    constructor( id ) {
        this.id		   = 'tpc-' + id;
        this._target   = document.getElementById( this.id );
        this._jqTarget = null;
        this._row	   = null;
    }

    value() {
        if ( this._target ) {
            return this._target.value;
        }
        
        return null;
    }

    disable( disable ) {
        if (! this._target ) {
            return null;
        }
            
        if ( 'undefined' == typeof disable ) {
            disable = true;
        }
        
        this._target.disabled = disable;
    }

    bindEvent( event, handler ) {
        if (! this._jqTarget ) {
            this._jqTarget = jQuery( this._target );
        }
            
        this._jqTarget.on( event, handler );
    }

    isChecked() {
        if ( this._target ) {
            return this._target.checked;
        }
            
        return null;
    }

    hide( hide ) {
        if (! this._target ) {
            return null;
        }
            
        if (! this._row ) {
            this._row = this._target.parentNode.parentNode;
        }
        
        if ( hide || ( 'undefined' == typeof hide ) ) {
            this._row.style.display = 'none';
        }
        else {
            this._row.style.display = 'table-row';
        }
    }

    toggle() {
        if (! this._target )
            return null;
            
        if (! this._row )
            this._row = this._target.parentNode.parentNode;
            
        if ( 'none' == this._row.style.display ) {
            this._row.style.display = 'table-row';
        }
        else {
            this._row.style.display = 'none';
        }
    }
}

class ThePermalinksCascadePingUI {
    constructor( node ) {
        this.mouseIsOn      = false;
        this.isVisible      = false;
        
        this.pingingUI      = node;
        this.pingingBubble  = node.getElementsByClassName( 'tpc-ap-bubble')[0];
        this.statusMessages = {};
        
        jQuery( node ).hover( () => this.show(), () => this.hide() );
    
        this.bindOnChangeEventToSwitchControl();
    }

    show() {
        this.mouseIsOn = true;
    
        if (! this.isVisible ) {
            this.isVisible = true;
            this.pingingBubble.style.display = 'block';
        }
    }

    hide() {
        this.mouseIsOn = false;
            
        setTimeout( ( that ) => {
            if (! that.mouseIsOn ) {
                that.isVisible = false;
                that.pingingBubble.style.display = 'none';
            }
        }, 800, this );
    }

    bindOnChangeEventToSwitchControl() {
        const switchNodes = this.pingingUI.getElementsByClassName( 'tpc-ap-switch-control' );
        
        if (! switchNodes.length ) {
            return null;
        }

        const switchControl = switchNodes[0];

        switchControl.onchange = () => {
            const data = {
                    action: 'handleTPCAdminAjaxRequest',
                tpc_action: 'enable_automatic_pinging',
                enable_ap: Number( switchControl.checked )
            };

            const request = jQuery.post( ajaxurl, data, ( response ) => {
                if ( 'ok' != response ) {
                    return null;
                }

                if ( !( 'on' in this.statusMessages ) ) {
                    this.statusMessages = {
                         on: this.pingingBubble.getElementsByClassName( 'tpc-ap-on-status-msg' )[0],
                        off: this.pingingBubble.getElementsByClassName( 'tpc-ap-off-status-msg' )[0]
                    }; 
                }

                if ( data.enable_ap ) {
                    this.toggleStatus( 'off' );
                }
                else {
                    this.toggleStatus( 'on' );
                }
            } );

            request.fail( () => {
                switchControl.checked = false;
            } );
        };
    }

    toggleStatus( status ) {
        const alternate_status  = ( ( status == 'on' ) ? 'off' : 'on' );
        const class_prefix      = 'tpc-automatic-pinging-';
        const target_class      = class_prefix + status;
        const replacement_class = class_prefix + alternate_status;
    
        this.pingingUI.classList.replace( target_class, replacement_class );
        this.pingingBubble.classList.replace( target_class, replacement_class );
    
        this.statusMessages[status].style.display = 'none';
        this.statusMessages[alternate_status].style.display = 'inline-block';
    }
}

const ThePermalinksCascade = {
    init: ( page_id, l10n ) => {
        switch ( page_id ) {
            case 'tpc-dashboard':
                const site_tree_page_select = document.getElementById( 'tpc-page-for-site-tree' );
                
                if ( site_tree_page_select ) {
                    if ( '0' === site_tree_page_select.value ) {
                        const primary_tb_btn = document.getElementById( 'tpc-primary-site_tree-form-btn' );

                        primary_tb_btn.disabled = true;

                        site_tree_page_select.onchange = () => {
                            primary_tb_btn.disabled = ( site_tree_page_select.value === '0' );
                        };
                    }

                    let sft_save_btn                   = null;
                    let sortable_fieldset_tooltip      = null;
                    let fieldset_sortability_enabled   = false;
                    const sortable_fieldset            = document.getElementById( 'site-tree-content-types-fieldset' );
                    const sortable_fieldset_container  = sortable_fieldset.parentElement;
                    const sortable_fieldset_toolbar    = document.createElement( 'div' );
                    const sft_toggle_sortability_btn   = document.createElement( 'a' );

                    sft_toggle_sortability_btn.innerHTML = l10n.sftEnableBtnTitle;
                    sft_toggle_sortability_btn.id        = 'tpc-sft-enable-btn';
                    sft_toggle_sortability_btn.setAttribute( 'href', '#' );

                    sft_toggle_sortability_btn.onclick = () => {
                        if ( fieldset_sortability_enabled ) {
                            fieldset_sortability_enabled = false;

                            jQuery( sortable_fieldset ).sortable( 'destroy' );

                            sft_toggle_sortability_btn.id        = 'tpc-sft-enable-btn';
                            sft_toggle_sortability_btn.innerHTML = l10n.sftEnableBtnTitle;
                            
                            sortable_fieldset_toolbar.removeChild( sft_save_btn );
                            sortable_fieldset_container.classList.remove( 'tpc-sortable' );
                            sortable_fieldset_container.parentElement.removeChild( sortable_fieldset_tooltip );
                        }
                        else {
                            fieldset_sortability_enabled = true;

                            if (! sft_save_btn ) {
                                sft_save_btn          = document.createElement( 'input' );
                                sft_save_btn.id       = 'tpc-sft-save-btn';
                                sft_save_btn.setAttribute( 'type', 'submit' );
                                sft_save_btn.setAttribute( 'name', 'save_order' );
                                sft_save_btn.setAttribute( 'value', l10n.sftSaveBtnTitle );
                                
                                sortable_fieldset_tooltip           = document.createElement( 'p' );
                                sortable_fieldset_tooltip.innerHTML = '<small>' + l10n.sortableFieldsetTooltip + '</small>';
                            }

                            jQuery( sortable_fieldset ).sortable( {
                                change: () => { sft_save_btn.disabled = false; }
                            } );
                            
                            sft_toggle_sortability_btn.id        = 'tpc-sft-cancel-btn';
                            sft_toggle_sortability_btn.innerHTML = l10n.sftCancelBtnTitle;

                            sft_save_btn.disabled = true;

                            sortable_fieldset_toolbar.appendChild( sft_save_btn );
                            sortable_fieldset_container.classList.add( 'tpc-sortable' );
                            sortable_fieldset_container.parentElement.appendChild( sortable_fieldset_tooltip );
                        }

                        sft_toggle_sortability_btn.blur();

                        return false;
                    };

                    sortable_fieldset_toolbar.id = 'tpc-sortable-fieldset-toolbar';
                    sortable_fieldset_toolbar.appendChild( sft_toggle_sortability_btn );

                    sortable_fieldset_container.parentElement.insertBefore( sortable_fieldset_toolbar, 
                                                                            sortable_fieldset_container );
                }

                let ping_ui_objects = [];
                const ping_ui_nodes = document.getElementsByClassName( 'tpc-automatic-pinging-ui' );

                if ( ping_ui_nodes.length ) {
                    for ( let node of ping_ui_nodes ) {
                        ping_ui_objects.push( new ThePermalinksCascadePingUI( node ) );
                    }
                }
                break;
                
            case 'tpc-site_tree':
                let settings = {};
                const ids	 = ['page-exclude-children', 'page-hierarchical', 'post-group-by', 
                                'post-order-by', 'authors-show-avatar', 'authors-avatar-size'];
                
                for ( let id of ids ) {
                    settings[ id.replace( /-/g, '_' ) ] = new ThePermalinksCascadeSetting( id );
                }
                
                // --- Initialise states
                if ( settings.page_exclude_children.isChecked() ) {
                    settings.page_hierarchical.hide();
                }

                if (! settings.authors_show_avatar.isChecked() ) {
                    settings.authors_avatar_size.hide();
                }
                
                if ( 'date' === settings.post_group_by.value() ) {
                    settings.post_order_by.hide();
                }
                
                // --- Events binding
                settings.page_exclude_children.bindEvent( 'click', () => {
                    settings.page_hierarchical.toggle();
                });
                settings.authors_show_avatar.bindEvent( 'click', () => {
                    settings.authors_avatar_size.toggle();
                });
                
                settings.post_group_by.bindEvent( 'change', () => {
                    settings.post_order_by.hide( 'date' === settings.post_group_by.value() );
                });
                break;
        }	
    }
};