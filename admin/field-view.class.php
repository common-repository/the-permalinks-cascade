<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
abstract class View {
    /**
     * @since 1.0
     * @var object
     */
    protected $viewData;

    /**
     * @since 1.0
     *
     * @param object $viewData
     * @return object
     */
    public static function makeView( $viewData ) {
        $base_class     = __CLASS__;
        $view_class     = __NAMESPACE__ . '\\' . $viewData->viewClass();
        $view           = new $view_class;
        $view->viewData = $viewData;

        if ( $view instanceof $base_class ) {
            return $view;
        }

        $message = __METHOD__ . '() cannot create objects of class ' . $view_class;
        
        trigger_error( $message, E_USER_ERROR );
    }

    /**
     * @since 1.0
     */
    private function __construct() {}

    /**
     * @since 1.0
     */
    abstract public function display();
}


/**
 * @since 1.0
 */
abstract class FieldView extends View {
	/**
	 * @since 1.0
     * @var string
	 */
	protected $id = 'tpc-';
    
	/**
	 * @since 1.0
     * @var string
	 */
	protected $name = 'tpc';
	
	/**
	 * @since 1.0
     * @var string
	 */
	protected $value;
	
	/**
	 * @since 1.0
     *
     * @param mixed $value
     * @param string $section_id
	 */
	public function init( $value, $section_id = '' ) {
        $raw_id = $this->viewData->id();

        $this->value = $value;
        
        if ( $section_id ) {
            $this->name .= '[' . $section_id . ']';
            $this->id   .= $section_id . '-' . $raw_id;
        }
        else {
            $this->id .= $raw_id;
        }
            
        $this->name .= '[' . $raw_id . ']';
        $this->id    = str_replace( '_', '-', $this->id );
    }
	
	/**
	 * @since 1.0
	 */
	public function display() {
		$this->displayField();
		$this->displayTooltip();
	}

	/**
	 * @since 1.0
	 */
	abstract protected function displayField();

	/**
	 * @since 1.0
	 */
	protected function displayTooltip() {
        if (! $this->viewData->tooltip ) {
            return false;
        }

        echo "\n";

        if ( 
            ( $this->viewData->viewClass() != 'Checkbox' ) && 
            preg_match( '/^\p{Lu}/u',  $this->viewData->tooltip )
        ) {
            echo '<span class="description">', wp_kses_data( $this->viewData->tooltip ), '</span>';
        }
        else {
            echo '<label for="', esc_attr( $this->id ), '">', wp_kses_data( $this->viewData->tooltip ), '</label>';
        }
    }
}


/**
 * @since 1.0
 */
class Checkbox extends FieldView {
	/**
	 * @see parent::displayField()
     * @since 1.0
	 */
	protected function displayField() {
        echo '<input type="checkbox" id="', esc_attr( $this->id ), '" name="', esc_attr( $this->name ), 
             '" value="1"', checked( true, $this->value, false ), '>';
    }
}


/**
 * @since 1.0
 */
class MetaCheckbox extends Checkbox {
    /**
     * @since 1.0
     */
    public function display() {
        echo '<label>';

        $this->displayField();

        echo '&nbsp;', wp_kses_data( $this->viewData->tooltip ), '</label>';
    }
}


/**
 * @since 1.0
 */
class Dropdown extends FieldView {
	/**
	 * @see parent::displayField()
     * @since 1.0
	 */
	protected function displayField() {
        $selected_value = ( is_bool( $this->value ) ? (string)(int) $this->value : $this->value );

        echo '<select id="', esc_attr( $this->id ), '" name="', esc_attr( $this->name ), '">';
        
        foreach ( $this->viewData->moreData as $value => $label ) {
            echo '<option value="', esc_attr( $value ), '"', selected( $value, $selected_value, false ), 
                 '>', esc_html( $label ), '</option>';
        }
        
        echo '</select>';
    }
}


/**
 * @since 1.0
 */
class TextField extends FieldView {
	/**
	 * @see parent::displayField()
     * @since 1.0
	 */
	protected function displayField() {
        echo '<input type="text" id="', esc_attr( $this->id ), '" name="', esc_attr( $this->name ),
             '" value="', esc_attr( $this->value ), '" class="regular-text">';
    }
}


/**
 * @since 1.0
 */
class NumberField extends FieldView {
	/**
	 * @see parent::displayField()
     * @since 1.0
	 */
	protected function displayField() {
        echo '<input type="number" id="', esc_attr( $this->id ), '" name="', esc_attr( $this->name ), 
             '" value="', esc_attr( $this->value ), '"';

        if ( isset( $this->viewData->conditions['min_value'] ) ) {
            echo ' min="', esc_attr( $this->viewData->conditions['min_value'] ), '"';
        }

        if ( isset( $this->viewData->conditions['max_value'] ) ) {
            echo ' max="', esc_attr( $this->viewData->conditions['max_value'] ), '"';
        }
        
        echo ' class="small-text">';
    }
}