<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
class PageView extends View {
	/**
	 * @since 1.0
     * @v`ar object
	 */
	protected $delegate;
	
	/**
	 * @since 1.0
     * @var array
	 */
	protected $sections;
	
	/**
	 * @since 1.0
     * @var object
	 */
	protected $displayingSection;
	
	/**
	 * @since 1.0
     * @var object
	 */
	protected $field;

    /**
     * @since 1.0
     * @var object
     */
    protected $fieldView;

    /**
     * @since 1.0
     * @return object
     */
    public function getDisplayingSection() {
        return $this->displayingSection;
    }

    /**
     * @since 1.0
     * @param array $sections
     */
    public function setSections( $sections ) {
        $this->sections = $sections;
    }

    /**
     * @since 1.0
     * @param PageViewDelegateProtocol $delegate
     */
    public function setDelegate( PageViewDelegateProtocol $delegate ) {
        $this->delegate = $delegate;
    }
	
	/**
	 * @since 1.0
	 */
	public function display() {
		ob_start();
		
        echo '<div class="wrap">',
			 '<h1>', esc_html( $this->viewData->title() ), '</h1>';

		$this->delegate->pageViewWillDisplayForm( $this );
		$this->displayForm();

        echo '</div>';
		
		ob_end_flush();
	}
	
	/**
	 * @since 1.0
     * @param string $form_id
	 */
	protected function displayForm() {

        echo '<form method="post">';

        $action = $this->delegate->pageViewFormAction( $this );
        
        $this->hiddenFields( $action );
		$this->displayFormContent();
		
		echo '</form>';
	}

    /**
     * @since 2.0
     *
     * @param string $action
     * @return string
     */
    public function hiddenFields( $action ) {
        $action = sanitize_key( $action );
        
        echo wp_nonce_field( $action, 'tpc_nonce', true, false );
        echo '<input type="hidden" name="action" value="', esc_attr( $action ), '">',
             '<input type="hidden" name="tpc_page" value="', esc_attr( $this->viewData->id() ), '">';
    }
	
	/**
	 * @since 1.0
	 */
	protected function displayFormContent() {
        foreach ( $this->sections as $this->displayingSection ) {
            $this->displaySection();
        }

        submit_button();
    }

    /**
     * @since 1.0
     */
    protected function displaySection() {
        $fields        = $this->displayingSection->fields();
        $section_title = $this->displayingSection->title();
        $section_id    = $this->displayingSection->id();

        if ( $section_title ) {
            echo '<h2 class="title">', esc_html( $section_title ), '</h2>';
        }
            
        echo '<table class="form-table"><tbody>';
        
        foreach ( $fields as $this->field ) {
            echo '<tr valign="top"><th scope="row">';
            echo esc_html( $this->field->title() ), '</th><td>';
            
            if ( $this->field instanceof Fieldset ) {
                $fieldset = $this->field;
                
                echo '<div class="tpc-fieldset-container"><fieldset';

                if ( $fieldset->id() ) {
                    $fieldset_id = $fieldset->id();

                    echo ' id="', esc_attr( str_replace( '_', '-', $fieldset_id ) ), '-fieldset"';
                }
                else {
                    $fieldset_id = $section_id;
                }

                echo '>';

                $grouped_fields = $fieldset->fields();
                $print_newline  = ( $fieldset->isSortable() || $fieldset->isInline() );
                
                foreach ( $grouped_fields as $this->field ) {
                    $this->loadFieldView( $fieldset_id );
                    $this->fieldView->display();

                    echo ( $print_newline ? "\n" : '<br>' );
                }

                echo '</fieldset></div>';

                $description = $fieldset->description();

                if ( $description ) {
                    echo '<p><small>', esc_html( $description ), '</small></p>';
                }
            }
            else {
                $this->loadFieldView( $section_id );
		        $this->fieldView->display();
            }

            echo '</td></tr>';
        }
        
        echo '</tbody></table>';
	}

	/**
	 * @since 1.0
	 */
	protected function loadFieldView( $section_id ) {
		$value = $this->delegate->pageViewFieldValue( $this->field, $section_id );
		
        $this->fieldView = FieldView::makeView( $this->field );
		$this->fieldView->init( $value, $section_id );
	}
}