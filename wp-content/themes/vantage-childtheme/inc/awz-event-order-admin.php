<?php
/**
 * AWZ: Admin page for managing event display order on /weiterbildung.
 * Accessible at: WP Admin > Werkzeuge > Lehrgang-Reihenfolge
 *
 * Stores sort order in post meta _awz_sort_order.
 * 0 = default chronological order. 1–99 = pinned to top, ascending.
 */

add_action( 'admin_menu', function () {
	add_submenu_page(
		'tools.php',
		'Lehrgang-Reihenfolge',
		'Lehrgang-Reihenfolge',
		'manage_options',
		'awz-event-order',
		'awz_render_event_order_page'
	);
} );

function awz_render_event_order_page() {
	if ( isset( $_POST['awz_save_order'] ) && check_admin_referer( 'awz_event_order' ) ) {
		$orders = ( isset( $_POST['awz_order'] ) && is_array( $_POST['awz_order'] ) )
			? $_POST['awz_order']
			: array();
		foreach ( $orders as $post_id => $value ) {
			$post_id = (int) $post_id;
			if ( 'stec_event' === get_post_type( $post_id ) ) {
				update_post_meta( $post_id, '_awz_sort_order', absint( $value ) );
			}
		}
		echo '<div class="notice notice-success is-dismissible"><p><strong>Reihenfolge gespeichert.</strong></p></div>';
	}

	$events = get_posts(
		array(
			'post_type'      => 'stec_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	?>
	<div class="wrap">
		<h1>Lehrgang-Anzeigereihenfolge</h1>
		<p>
			Legt fest, welche Lehrgänge oben in der Liste auf <em>/weiterbildung</em> erscheinen.<br>
			<strong>0</strong> = normale chronologische Reihenfolge &nbsp;|&nbsp;
			<strong>1–99</strong> = oben gepinnt, aufsteigend nach Wert
		</p>
		<form method="post">
			<?php wp_nonce_field( 'awz_event_order' ); ?>
			<table class="wp-list-table widefat fixed striped" style="max-width:700px">
				<thead>
					<tr>
						<th>Lehrgang</th>
						<th style="width:110px">Reihenfolge</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $events as $event ) :
					$order = (int) get_post_meta( $event->ID, '_awz_sort_order', true );
					?>
					<tr>
						<td><?php echo esc_html( $event->post_title ); ?></td>
						<td>
							<input type="number"
								   name="awz_order[<?php echo (int) $event->ID; ?>]"
								   value="<?php echo esc_attr( $order ); ?>"
								   min="0" max="99"
								   style="width:65px">
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="awz_save_order" class="button button-primary" value="Reihenfolge speichern">
			</p>
		</form>
	</div>
	<?php
}
