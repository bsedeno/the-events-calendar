<?php
class Tribe__Events__Views__Day_View extends Tribe__Events__Views__Base_View {
	/**
	 * The path to the template file used for the view.
	 * This value is used in Shortcodes/Tribe_Events.php to
	 * locate the correct template file for each shortcode
	 * view.
	 *
	 * @var string
	 */
	public $view_path = 'day/content';

	/**
	 * Set up hooks for this template.
	 */
	public function hook() {
		parent::hook();
		add_filter( 'tribe_get_ical_link', array( $this, 'ical_link' ), 20, 1 );
		add_filter( 'tribe_events_header_attributes', array( $this, 'header_attributes' ) );
	}

	/**
	 * Add header attributes for day view
	 *
	 * @param array $attrs
	 *
	 * @return array
	 **/
	public function header_attributes( $attrs ) {

		global $wp_query;
		$current_day = $wp_query->get( 'start_date' );

		$attrs['data-view']    = 'day';
		$attrs['data-baseurl'] = tribe_get_day_link( $current_day );
		$attrs['data-date']    = date( 'Y-m-d', strtotime( $current_day ) );
		$attrs['data-header']  = date( tribe_get_date_format( true ), strtotime( $current_day ) );

		return $attrs;
	}

	/**
	 * Get the title for day view.
	 *
	 * @param string      $original_title
	 * @param string|null $sep
	 *
	 * @return string
	 */
	protected function get_title( $original_title, $sep = null ) {
		$new_title = parent::get_title( $original_title, $sep );
		if ( has_filter( 'tribe_events_day_view_title' ) ) {
			_deprecated_function( "The 'tribe_events_day_view_title' filter", '3.8', " the 'tribe_get_events_title' filter" );
			$title_date = date_i18n( tribe_get_date_format( true ), strtotime( get_query_var( 'eventDate' ) ) );
			$new_title  = apply_filters( 'tribe_events_day_view_title', $new_title, $sep, $title_date );
		}
		return $new_title;
	}


	/**
	 * Get the link to download the ical version of day view
	 *
	 * @param string $link
	 *
	 * @return string
	 */
	public function ical_link( $link ) {
		global $wp_query;
		$day = $wp_query->get( 'start_date' );

		return trailingslashit( esc_url( trailingslashit( tribe_get_day_link( $day ) ) . '?ical=1' ) );
	}

	/**
	 * Organize and reorder the events posts according to time slot
	 *
	 **/
	public function setup_view() {

		global $wp_query;

		$time_format = apply_filters( 'tribe_events_day_timeslot_format', get_option( 'time_format', Tribe__Date_Utils::TIMEFORMAT ) );

		if ( $wp_query->have_posts() ) {
			$unsorted_posts = $wp_query->posts;
			foreach ( $unsorted_posts as &$post ) {
				if ( tribe_event_is_all_day( $post->ID ) ) {
					$post->timeslot = esc_html__( 'All Day', 'the-events-calendar' );
				} else {
					if ( strtotime( tribe_get_start_date( $post->ID, true, Tribe__Date_Utils::DBDATETIMEFORMAT ) ) < strtotime( $wp_query->get( 'start_date' ) ) ) {
						$post->timeslot = esc_html__( 'Ongoing', 'the-events-calendar' );
					} else {
						$post->timeslot = tribe_get_start_date( $post, false, $time_format );
					}
				}
			}
			unset( $post );

			// Make sure All Day events come first
			$all_day = array();
			$ongoing = array();
			$hourly  = array();
			foreach ( $unsorted_posts as $i => $post ) {
				if ( $post->timeslot == esc_html__( 'All Day', 'the-events-calendar' ) ) {
					$all_day[ $i ] = $post;
				} else {
					if ( $post->timeslot == esc_html__( 'Ongoing', 'the-events-calendar' ) ) {
						$ongoing[ $i ] = $post;
					} else {
						$hourly[ $i ] = $post;
					}
				}
			}

			$wp_query->posts = array_values( $all_day + $ongoing + $hourly );
			$wp_query->rewind_posts();
		}
	}

	protected function nothing_found_notice() {
		$events_label_plural_lowercase = tribe_get_event_label_plural_lowercase();
		list( $search_term, $tax_term, $geographic_term ) = $this->get_search_terms();

		if ( empty( $search_term ) && empty( $geographic_term ) && ! empty( $tax_term ) ) {
			Tribe__Notices::set_notice( 'events-not-found', sprintf( esc_html__( 'No matching %1$s listed under %2$s scheduled for %3$s. Please try another day.', 'the-events-calendar' ), $events_label_plural_lowercase, $tax_term, '<strong>' . date_i18n( tribe_get_date_format( true ), strtotime( get_query_var( 'eventDate' ) ) ) . '</strong>' ) );
		} elseif ( empty( $search_term ) && empty( $geographic_term ) ) {
			Tribe__Notices::set_notice( 'events-not-found', sprintf( esc_html__( 'No %1$s scheduled for %2$s. Please try another day.', 'the-events-calendar' ), $events_label_plural_lowercase, '<strong>' . date_i18n( tribe_get_date_format( true ), strtotime( get_query_var( 'eventDate' ) ) ) . '</strong>' ) );
		} else {
			parent::nothing_found_notice();
		}
	}
}