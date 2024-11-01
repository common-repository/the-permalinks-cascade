<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
interface PageViewDelegateProtocol {
    /**
     * @since 1.0
     * @param object $pageView
     */
    public function pageViewWillDisplayForm( $pageView );

    /**
     * @since 1.0
     *
     * @param object $field
     * @param string $section_id
     * @return mixed
     */
    public function pageViewFieldValue( $field, $section_id );

    /**
     * @since 1.0
     *
     * @param object $pageView
     * @return string
     */
    public function pageViewFormAction( $pageView );
}


/**
 * @since 1.0
 */
interface DashboardDelegateProtocol {
    /**
     * @since 1.0
     *
     * @param object $dashboardPageView
     * @param string $form_id
     */
    public function dashboardWillDisplayToolbarButtons( $dashboardPageView, $form_id );

    /**
     * @since 1.0
     *
     * @param object $dashboardPageView
     * @param string $form_id
     * @return bool
     */
    public function dashboardCanDisplayMetrics( $dashboardPageView, $form_id );

    /**
     * @since 1.0
     *
     * @param object $dashboardPageView
     * @param string $form_id
     */
    public function dashboardDidDisplayMetrics( $dashboardPageView, $form_id );

    /**
     * @since 1.0
     * @param string $form_id
     */
    public function dashboardDidDisplaySingleForm( $form_id );

    /**
     * @since 1.0
     * @param object $dashboardPageView
     */
    public function dashboardDidDisplayForms( $dashboardPageView );
}