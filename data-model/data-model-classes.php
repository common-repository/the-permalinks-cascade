<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
class Page {
    /**
     * @since 1.0
     */
    protected static $namespace;

	/**
	 * @since 1.0
	 * @var string
	 */
	protected $id;
	
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $title;
	
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $menuTitle;
	
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $viewClass;
    
    /**
     * @since 1.0
     * @var string
     */
    protected $controllerClass;

    /**
     * @since 1.0
     * @var string
     */
    protected $parentSlug;
	
    /**
     * @since 1.0
     * @param string $namespace
     */
    public static function setNamespace( $namespace ) {
        self::$namespace = $namespace;
    }

	/**
	 * @since 1.0
     *
     * @param string $id
     * @param string $parent_slug
     * @param string $title
     * @param string $menu_title
     * @param string $view_class      
     * @param string $controller_class
	 */
	public function __construct( $id, $parent_slug, $title, 
                                 $menu_title, $view_class, $controller_class )
    {
        $this->id              = $id;
		$this->title	       = $title;
		$this->menuTitle       = $menu_title;
        $this->viewClass       = $view_class;
        $this->controllerClass = $controller_class;

        /**
         * @since 2.0.3
         */
        $this->parentSlug = apply_filters( 'tpc_admin_page_parent_slug', $parent_slug, $id );
	}
	
    /**
     * @since 1.0
     * @return string
     */
    public function id() {
    	return $this->id; 
    }

    /**
     * @since 1.0
     * @return string
     */
    public function namespacedID() {
        return ( self::$namespace . '-' . $this->id ); 
    }

    /**
     * @since 1.0
     * @return string
     */
    public function title() {
    	return $this->title; 
    }

    /**
     * @since 1.0
     * @return string
     */
    public function menuTitle() {
    	return $this->menuTitle; 
    }

    /**
     * @since 1.0
     * @return string
     */
    public function viewClass() {
    	return $this->viewClass; 
    }

    /**
     * @since 1.0
     * @return string
     */
    public function controllerClass() {
    	return $this->controllerClass; 
    }

    /**
     * @since 1.0
     * @return string
     */
    public function parentSlug() {
        return $this->parentSlug;
    }
}

/**
 * @since 1.0
 */
class Section {
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $id;
	
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $title;

    /**
     * @since 1.0
     * @var string
     */
    protected $description = '';
	
	/**
	 * @since 1.0
	 * @var array
	 */
	protected $fields = array();

    /**
     * @since 1.0
     * @var array
     */
    protected $fieldsDictionary = array();
	
	/**
	 * @since 1.0
     *
     * @param string $title 
     * @param string $id    
     * @param array  $fields
	 */
	public function __construct( $title = '', $id = '', $fields = array() ) {
        $this->id     = $id;
        $this->title  = $title;

        foreach ( $fields as $field ) {
            $this->addField( $field );
        }
    }

    /**
     * @since 1.0
     * @param string $id
     */
    public function setID( $id ) {
        $this->id = $id;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function id() {
    	return $this->id; 
    }

    /**
     * @since 1.0
     * @param string $title
     */
    public function setTitle( $title ) {
        $this->title = $title;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function title() {
    	return $this->title; 
    }

    /**
     * @since 1.0
     * @param string $description
     */
    public function setDescription( $description ) {
        $this->description = $description;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function description() {
        return $this->description;
    }

    /**
     * @since 1.0
     * @param object $new_field
     */
    public function addField( $new_field ) {
        $this->fields[] = $new_field;

        $this->updateFieldsDictionary( $new_field );
    }

    /**
     * @since 1.0
     *
     * @param string $id
     * @return object|bool
     */
    public function getField( $id ) {
        if ( isset( $this->fieldsDictionary[$id] ) ) {
            return $this->fieldsDictionary[$id];
        }

        return false;
    }

    /**
     * @since 1.0
     * @return array
     */
    public function fields() {
    	return $this->fields; 
    }

    /**
     * @since 1.0
     * @return array
     */
    public function getFieldsFromDictionary() {
        return $this->fieldsDictionary;
    }

    /**
     * @since 1.0
     *
     * @param object $new_field
     * @return bool Always True.
     */
    protected function updateFieldsDictionary( $new_field ) {
        $class_name = static::class;

        if ( $new_field instanceof $class_name ) {
            $field_group    = $new_field->fields();
            $field_group_id = $new_field->id();

            if (! $field_group_id ) {
                foreach ( $field_group as $field ) {
                    $this->fieldsDictionary[$field->id()] = $field;
                }

                return true;
            }
        }
        
        $this->fieldsDictionary[$new_field->id()] = $new_field;

        return true;
    }

    /**
     * @since 1.0
     * @return bool
     */
    public function hasFields() {
        return !empty( $this->fields );
    }
}

/**
 * @since 1.0
 */
class Fieldset extends Section {
	/**
     * @since 1.0
     * @var string
     */
    protected $style;

    /**
     * @since 1.0
     *
     * @param string $title
     * @param string $id
     * @param string $style
     * @param array  $fields
     */
    public function __construct( $title = '', $id = '', $style = '', $fields = array() ) {
        parent::__construct( $title, $id, $fields );

        $this->style = $style;
    }

    /**
     * @since 1.0
     * @return bool
     */
    public function isInline() { 
        return ( $this->style === 'inline' );
    }

    /**
     * @since 1.0
     * @return bool
     */
    public function isSortable() { 
        return ( $this->style === 'sortable' );
    }

    /**
     * @since 1.0
     * @param array $ordered_keys
     */
    public function reorderFields( $ordered_keys ) {
        $ordered_fields = (array) $ordered_keys;

        foreach ( $this->fieldsDictionary as $field_id => $field ) {
            $ordered_fields[$field_id] = $field;
        }

        $this->fields = array();

        foreach ( $ordered_fields as $field_id => $field ) {
            if ( is_object( $field ) ) {
                $this->fields[] = $field;
            }
            else {
                unset( $ordered_fields[$field_id] );
            }
        }

        $this->fieldsDictionary = $ordered_fields;
    }
}

/**
 * @since 1.0
 */
class Field {
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $id;
	
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $viewClass;
	
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $dataType;
	
	/**
	 * @since 1.0
	 * @var string
	 */
	protected $title;

	/**
	 * @since 1.0
	 * @var string
	 */
	public $tooltip;
	
	/**
	 * @since 1.0
	 * @var mixed
	 */
	protected $defaultValue;
	
	/**
	 * @since 1.0
	 * @var mixed
	 */
	public $config;
	
	/**
	 * @since 1.0
	 * @var mixed
	 */
	public $conditions;
	
	/**
	 * @since 1.0
	 *
     * @param string $id
     * @param string $view_class
     * @param string $data_type
     * @param string $title
     * @param string $tooltip
     * @param mixed  $default_value
     */
    public function __construct( $id, $view_class, $data_type, $title, $tooltip = '', 
                                 $default_value = false, $more_data = null, $conditions = null )
    {
        $this->id           = $id;
        $this->viewClass    = $view_class;
        $this->dataType     = $data_type;
        $this->defaultValue = $default_value;
        $this->title        = $title;
        $this->tooltip      = $tooltip;
        $this->moreData     = $more_data;
        
        if ( null === $conditions ) {
            $this->conditions = &$this->moreData;
        }
        else {
            $this->conditions = $conditions;
        }
    }

    /**
     * @since 1.0
     * @return string
     */
    public function id() { 
        return $this->id;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function viewClass() { 
        return $this->viewClass;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function dataType() { 
        return $this->dataType;
    }
    
    /**
     * @since 1.0
     * @return string
     */
    public function title() { 
        return $this->title;
    }

    /**
     * @since 1.0
     * @param string $tooltip
     */
    public function setTooltip( $tooltip ) { 
        $this->tooltip = $tooltip;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function tooltip() { 
        return $this->tooltip;
    }

    /**
     * @since 1.0
     * @param mixed $value
     */
    public function setDefaultValue( $value ) { 
        $this->defaultValue = $value;
    }

    /**
     * @since 1.0
     * @return mixed
     */
    public function defaultValue() { 
        return $this->defaultValue;
    }

    /**
     * @since 1.0
     * @param mixed $data
     */
    public function setAdditionalData( $data ) { 
        $this->moreData = $data;
    }

    /**
     * @since 1.0
     * @return mixed
     */
    public function additionalData() { 
        return $this->moreData;
    }

    /**
     * @since 1.0
     * @param mixed $conditions
     */
    public function setConditions( $conditions ) { 
        $this->conditions = $conditions;
    }

    /**
     * @since 1.0
     * @return mixed
     */
    public function conditions() { 
        return $this->conditions;
    }
}

/**
 * @since 1.0
 */
class OptionsFilter {
    /**
     * @since 1.0
     * @var mixed
     */
    protected $value;

    /**
     * @since 1.0
     * @var object
     */
    protected $field;
    
    /**
     * @since 1.0
     *
     * @param mixed $value
     * @param object $field
     */
    public function __construct( $value, $field ) {
        $this->value = $value;
        $this->field = $field;
    }
    
    /**
     * @since 1.0
     * @return mixed
     */
    public function filterOption() {
        $method_name = 'filter_' . $this->field->dataType();

        if ( method_exists( $this, $method_name ) && $this->{$method_name}() ) {
            return $this->value;
        }

        return $this->field->defaultValue();
    }
    
    /**
     * Validates a limited positive number.
     *
     * @since 1.0
     * @return bool
     */
    private function filter_positive_number() {
        if ( $this->value <= 0 ) {
            return false;
        }
        
        if ( isset( $this->field->conditions['min_value'] ) ) {
            return ( $this->value >= $this->field->conditions['min_value'] );
        }
        
        if ( isset( $this->field->conditions['max_value'] ) ) {
            return ( $this->value <= $this->field->conditions['max_value'] );
        }
    }
    
    /**
     * Validates an option of a Select field by checking whether or not the 
     * received value exists in the list of choices.
     *
     * @since 1.0
     * @return bool
     */
    private function filter_choice() {
        return isset( $this->field->conditions[$this->value] );
    }
    
    /**
     * Validates a boolean value collected by a dropdown control.
     *
     * @since 1.0
     * @return bool
     */
    private function filter_boolean_choice() {
        if ( 
            is_bool( $this->value ) || 
            ( '1' === $this->value ) || 
            ( '0' === $this->value )
        ) {
            $this->value = (bool)(int) $this->value;
        }
        else {
            $this->value = (bool)(int) $this->field->defaultValue();
        }

        return true;
    }

    /**
     * Validates a boolean value.
     *
     * @since 1.0
     * @return bool
     */
    private function filter_bool() {
        if ( 
            is_bool( $this->value ) || 
            ( '1' === $this->value ) || 
            ( '0' === $this->value ) || 
            ( '' === $this->value )
        ) {
            $this->value = (bool)(int) $this->value;

            return true;
        }

        return false;
    }
    
    /**
     * Filters a comma-separated list of numeric ids.
     *
     * @since 1.0
     * @return bool
     */
    private function filter_list_of_ids() {
        if (! $this->value ) {
            return true;
        }

        $_nums   = array();
        $numbers = explode( ',', $this->value );
        
        if (! $numbers ) {
            return false;
        }
            
        foreach ( $numbers as $number ) {
            if ( 
                is_numeric( $number ) && 
                ( $number > 0 ) && 
                !isset( $_nums[$number] ) 
            ) {
                $number         = (int) $number;
                $_nums[$number] = $number;
            }
        }

        if (! $_nums ) {
            return false;
        }

        sort( $_nums, SORT_NUMERIC );
        
        $this->value = implode( ', ', $_nums );

        return true;
    }

    /**
     * Filters a comma-separated list of nicknames.
     *
     * @since 1.0
     * @return bool
     */
    private function filter_list_of_nicknames() {
        if (! $this->value ) {
            return true;
        }

        $valid_nicknames = array();
        $nicknames       = explode( ',', $this->value );
        
        if (! $nicknames ) {
            return false;
        }
            
        foreach ( $nicknames as $nickname ) {
            $nickname = trim( $nickname );

            if ( 
                ( 0 === preg_match( '/[^0-9a-zA-Z_-]/', $nickname ) ) && 
                !isset( $valid_nicknames[$nickname] ) 
            ) {
                $valid_nicknames[$nickname] = $nickname;
            }
        }

        if (! $valid_nicknames ) {
            return false;
        }

        $this->value = implode( ', ', $valid_nicknames );

        return true;
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function filter_inline_html() {
        $allowed_html = array(
            'a'       => array(
                'href'  => array(),
                'title' => array(),
                'id'    => array(),
                'class' => array()
            ),
            'span'    => array(
                'id'    => array(),
                'class' => array()
            ),
            'em'      => array(),
            'strong'  => array(),
            'small'   => array(),
            'abbr'    => array(),
            'acronym' => array(),
            'code'    => array(),
            'sub'     => array(),
            'sup'     => array()
        );

        $this->value = wp_kses( $this->value, $allowed_html );

        return true;
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function filter_plain_text() {
        $this->value = wp_kses( $this->value, array() );

        return true;
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function filter_key() {
        return ( preg_match( '/[^0-9a-zA-Z-]/', $this->value ) === 0 );
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function filter_url() {
        $pattern = '#https?://(?:[^\s/?\#-.]+\.?)+(?:/[^\s]*)?$#i';

        return ( preg_match( $pattern, $this->value ) === 1 );
    }
}