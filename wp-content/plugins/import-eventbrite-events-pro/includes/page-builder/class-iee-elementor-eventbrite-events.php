<?php
/**
 * Class for Custom Visual Composer Element
 *
 * @link       http://xylusthemes.com/
 * @since      1.0.0
 *
 * @package    Import_Eventbrite_Events_Pro
 * @subpackage Import_Eventbrite_Events_Pro/includes
 */

namespace ElementorIEEWidget\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

// Exit if accessed directly
defined( 'ABSPATH' ) || die();

/**
 * IEE Elementor widget class.
 *
 * @since 1.0.0
 */
class IEE_Elementor_Eventbrite_Events extends Widget_Base {
	/**
	 * Class constructor.
	 *
	 * @param array $data Widget data.
	 * @param array $args Widget arguments.
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
	}

	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return __( 'UpcomingEventGridview', 'import-eventbrite-events-pro' );
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Eventbrite Event Grid View', 'import-eventbrite-events-pro' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		//return 'eicon-gallery-grid';
        return 'eicon-calendar';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'basic' );
	}
	
	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Eventbrite Event Content', 'import-eventbrite-events-pro' ),
			)
		);

		$event_cats = get_terms( 'eventbrite_category', array( 'hide_empty' => false ) );
        $categories = array();
		if( !empty( $event_cats ) ){
			$categories[] = 'All Events';
			foreach ( $event_cats as $event_cat ) {
				$categories[$event_cat->name] = $event_cat->name;
			}
        }

		$this->add_control(
			'event_categories',
			[
				'label'   => __( 'Event Categories', 'import-eventbrite-events-pro' ),
				'type' 	  => Controls_Manager::SELECT,
				'default' => '0',
				'options' => $categories,
			]
		);

		$this->add_control(
			'event_column',
			[
				'label'   => __( 'Event Column', 'import-eventbrite-events-pro' ),
				'type' 	  => Controls_Manager::SELECT,
				'default' => '2',
				'options' => [
					__( '1', 'import-eventbrite-events-pro' ) => '1',
					__( '2', 'import-eventbrite-events-pro' ) => '2',
					__( '3', 'import-eventbrite-events-pro' ) => '3',
					__( '4', 'import-eventbrite-events-pro' ) => '4',
					],
			]
		);

		$this->add_control(
			'past_events',
			[
				'label'   => __( 'Past Events', 'import-eventbrite-events-pro' ),
				'type' 	  => Controls_Manager::SELECT,
				'options' => [
							__( '', 'import-eventbrite-events-pro' ) => 'Default',
							__( 'no', 'import-eventbrite-events-pro' ) => 'no',
							__( 'yes', 'import-eventbrite-events-pro' ) => 'yes'
					],
			]
		);

		$this->add_control(
			'layout_style',
			[
				'label'   => __( 'Event Layout Style', 'import-eventbrite-events-pro' ),
				'type' 	  => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					__( 'default', 'import-eventbrite-events-pro' ) => 'Default',
					__( 'style1', 'import-eventbrite-events-pro' ) => 'Style 1',
					__( 'style2', 'import-eventbrite-events-pro' ) => 'Style 2',
					],
			]
		);

		$this->add_control(
			'start_date',
			array(
				'label'   => __( 'Start Date', 'import-eventbrite-events-pro' ),
				'type'    => Controls_Manager::DATE_TIME,
			)
		);

		$this->add_control(
			'end_date',
			array(
				'label'   => __( 'End Date', 'import-eventbrite-events-pro' ),
				'type'    => Controls_Manager::DATE_TIME,
			)
		);

		$this->add_control(
			'order',
			[
				'label'   => __( 'Order', 'import-eventbrite-events-pro' ),
				'type' 	  => Controls_Manager::SELECT,
				'options' => [
							__( '', 'import-eventbrite-events-pro' ) => 'Default',
							__( 'ASC', 'import-eventbrite-events-pro' ) => 'ASC',
							__( 'DESC', 'import-eventbrite-events-pro' ) => 'DESC'
					],
			]
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'Events per Page', 'import-eventbrite-events-pro' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => __( '12', 'import-eventbrite-events-pro' ),
				'min' 	  => 1,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		
		$event_categories	= !empty( $settings['event_categories'] ) ? $settings['event_categories'] : '';
		$posts_per_page		= !empty( $settings['posts_per_page'] ) ? $settings['posts_per_page'] : '';
		$event_column		= !empty( $settings['event_column'] ) ? $settings['event_column'] : '';
		$past_events		= !empty( $settings['past_events'] ) ? $settings['past_events'] : '';
		$start_date			= !empty( $settings['start_date'] ) ? $settings['start_date'] : '';
		$end_date			= !empty( $settings['end_date'] ) ? $settings['end_date'] : '';
		$order				= !empty( $settings['order'] ) ? $settings['order'] : '';
		$layout_style       = !empty( $settings['layout_style'] ) ? $settings['layout_style'] : '';

		$style2 = '';
		if( $layout_style == 'style2' ){
			$style2 = 'layout="style2"';
		}
		echo do_shortcode('[eventbrite_events '. $style2 .' col="'.$event_column.'" category="'.$event_categories.'" past_events="'.$past_events.'" posts_per_page="'.$posts_per_page.'" order="'.$order.'" start_date="'.$start_date.'" end_date="'.$end_date.'"]');
	}

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function content_template() {
		do_shortcode('[eventbrite_events col="{{ settings.event_column }}" category="{{ settings.event_categories }}" past_events="{{ settings.past_events }}" posts_per_page="{{ settings.posts_per_page }}" order="{{ settings.order }}" start_date="{{ settings.start_date }}" end_date="{{ settings.end_date }}"]');
	}
}
