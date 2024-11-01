<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class DashboardPageView extends PageView {
    /**
     * @since 1.0
     * @var array
     */
    private $metrics = array();
    
    /**
     * @since 1.0
     * @var int
     */
    private $numOfMetrics = 0;

    /**
     * @since 1.0
     * @var array
     */
    private $toolbarConfig = array(
        'view_url'        => '',
        'config_mode_url' => '',
        'settings_url'    => '',
        'submit_title'    => ''
    );

    /**
     * @since 1.0
     * @var string
     */
    private $metricsFreshnessMsg;

    /**
     * @since 1.0
     * @param string $time_since
     */
    public function setMetricsFreshness( $time_since ) {
        $this->metricsFreshnessMsg = sprintf( __( 'Info updated %s.', 'the-permalinks-cascade' ), $time_since );
    }
    
    /**
     * @since 1.0
     *
     * @param string $title
     * @param int|string $value
     */
    public function registerMetric( $title, $value ) {
        $metric = array(
            'title' => $title,
            'value' => $value
        );

        $metric['can_display'] = ( ( $value >= 0 ) && ( $value != '0s' ) );

        $this->metrics[]     = $metric;
        $this->numOfMetrics += 1;
    }

    /**
     * @since 1.0
     */
    private function resetMetrics() {
        $this->metrics      = array();
        $this->numOfMetrics = 0;
    }
    
    /**
     * @since 1.0
     * @param array $config
     */
    public function configureToolbar( $config ) {
        $this->toolbarConfig = array_merge( $this->toolbarConfig, $config );
    }

    /**
     * @since 1.0
     */
    public function formID() {
        return $this->displayingSection->id();
    }
    
    /**
     * @see parent::displayForm()
     * @since 1.0
     */
    protected function displayForm() {
        echo '<div id="tpc-dashboard-wrapper" class="tpc-self-clear"><div id="tpc-dashboard">';
        
        foreach ( $this->sections as $this->displayingSection ) {
            $form_id = $this->formID();

            echo '<div id="tpc-', esc_attr( $form_id ), '-dashform-area" class="tpc-dashform-area">';

            parent::displayForm();

            $this->delegate->dashboardDidDisplaySingleForm( $form_id );

            echo '</div>';
        }
        
        echo '</div>';

        $this->delegate->dashboardDidDisplayForms( $this );

        echo '</div>';
    }
    
    /**
     * @see parent::displayFormContent()
     * @since 1.0
     */
    protected function displayFormContent() {
        $form_id = $this->formID();
        
        echo '<input type="hidden" name="tpc_form_id" value="', esc_attr( $form_id ), '">',
             '<div class="tpc-toolbar"><span class="tpc-tb-form-title">', esc_html( $this->displayingSection->title() ), '</span>';

        $this->delegate->dashboardWillDisplayToolbarButtons( $this, $form_id );

        if ( $this->delegate->dashboardCanDisplayMetrics( $this, $form_id ) ) {
            $last_metric_index = $this->numOfMetrics - 1;
            
            if ( $this->toolbarConfig['settings_url'] ) {
                echo '<a href="', esc_url( $this->toolbarConfig['settings_url'] ), '" class="tpc-tb-btn tpc-corner-tb-btn">', 
                     esc_html__( 'Settings', 'the-permalinks-cascade' ), '</a>';
            }
            
            echo '<a href="', esc_url( $this->toolbarConfig['config_mode_url'] ), '" class="tpc-tb-btn';

            if (! $this->toolbarConfig['settings_url'] ) {
                echo ' tpc-corner-tb-btn';  
            }
            
            echo '">', __( 'Configure', 'the-permalinks-cascade' ), '</a>',
                 '<a href="', esc_url( $this->toolbarConfig['view_url'] ), '" class="tpc-tb-btn" target="tpc_', esc_attr( $form_id ), '">',
                 esc_html__( 'View', 'the-permalinks-cascade' ), '</a>',
                 '</div><div class="tpc-metrics"><ul class="tpc-metrics-list tpc-self-clear';
            
            if ( $this->numOfMetrics != 4 ) {
                echo ' tpc-', esc_attr( $this->numOfMetrics ), '-metrics';
            }
                
            echo '">';

            $show_freshness_message = false;
            
            for ( $i = 0; $i < $this->numOfMetrics; $i++ ) {
                echo '<li><div class="tpc-metric-container';
                
                if ( $i == $last_metric_index ) {
                    echo ' tpc-last-metric';
                }
                
                echo '">', esc_html( $this->metrics[$i]['title'] ), '<div class="tpc-metric">';

                if ( $this->metrics[$i]['can_display'] ) {
                    $show_freshness_message = true;
                    
                    echo esc_html( $this->metrics[$i]['value'] );
                }
                else {
                    echo '-';
                }

                echo '</div></div></li>';
            }
            
            echo '</ul>';

            if ( $show_freshness_message ) {
                echo '<p class="tpc-metrics-freshness">', esc_html( $this->metricsFreshnessMsg ), '</p>';
            }

            echo '</div>';

            $this->delegate->dashboardDidDisplayMetrics( $this, $form_id );    
            $this->resetMetrics();
        }
        else {
            echo '<input type="submit" id="tpc-primary-', esc_attr( $form_id ), 
                 '-form-btn" class="tpc-tb-btn tpc-corner-tb-btn tpc-primary-tb-btn" name="submit" value="',
                 esc_attr( $this->toolbarConfig['submit_title'] ), '"></div>';

            $this->displayingSection->setID( '' );
            $this->displayingSection->setTitle( '' );
            
            $this->displaySection();
        }
    }
}