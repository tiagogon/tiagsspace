<?php
/**
 * Theme Settings: Appearance → Theme Settings.
 *
 * One autoloaded option, `tiagsspace_settings`, holding an array. Every setting is
 * declared once in tiagsspace_settings_schema(); the page, the defaults and the
 * sanitizer are all built from that list, so a new setting is one array entry.
 * Read a value anywhere with tiagsspace_setting( 'key' ).
 *
 * Field types implemented: checkbox. Add a case to tiagsspace_settings_render_field()
 * and tiagsspace_settings_sanitize() for another type.
 *
 * @package tiagsspace
 */

defined( 'ABSPATH' ) || exit;

const TIAGSSPACE_SETTINGS_OPTION = 'tiagsspace_settings';
const TIAGSSPACE_SETTINGS_PAGE   = 'tiagsspace-settings';

/**
 * Sections and their fields, in page order.
 *
 * Field keys: id, type, label (left column), checkbox_label, description, default.
 */
function tiagsspace_settings_schema() {
	return array(
		'gallery' => array(
			'title'  => __( 'Gallery', 'tiagsspace' ),
			'fields' => array(
				array(
					'id'             => 'gallery_edit_published',
					'type'           => 'checkbox',
					'default'        => false,
					'label'          => __( 'Edit galleries on published posts', 'tiagsspace' ),
					'checkbox_label' => __( 'Open published posts in gallery edit mode', 'tiagsspace' ),
					'description'    => __( 'When you are logged in, every published post you can edit opens with the gallery editor on, as a preview does. Changes (order, hide, size, margins, delete, move) are saved to the live page at once. A “Gallery edit” link next to “Edit” switches it off or on for a single post. Visitors never see the editor.', 'tiagsspace' ),
				),
			),
		),
	);
}

/** Every field's default, keyed by id. */
function tiagsspace_settings_defaults() {
	$defaults = array();
	foreach ( tiagsspace_settings_schema() as $section ) {
		foreach ( $section['fields'] as $field ) {
			$defaults[ $field['id'] ] = $field['default'];
		}
	}
	return $defaults;
}

/**
 * One setting's value, falling back to its default.
 *
 * @param string $key Field id from the schema.
 * @return mixed
 */
function tiagsspace_setting( $key ) {
	$saved  = get_option( TIAGSSPACE_SETTINGS_OPTION, array() );
	$values = array_merge( tiagsspace_settings_defaults(), is_array( $saved ) ? $saved : array() );
	return isset( $values[ $key ] ) ? $values[ $key ] : null;
}

/** Keeps only schema keys and casts each to its type. */
function tiagsspace_settings_sanitize( $input ) {
	$input = is_array( $input ) ? $input : array();
	$clean = array();
	foreach ( tiagsspace_settings_schema() as $section ) {
		foreach ( $section['fields'] as $field ) {
			$id = $field['id'];
			switch ( $field['type'] ) {
				case 'checkbox':
					$clean[ $id ] = ! empty( $input[ $id ] );
					break;
			}
		}
	}
	return $clean;
}

add_action( 'admin_init', 'tiagsspace_settings_register' );
function tiagsspace_settings_register() {
	register_setting(
		TIAGSSPACE_SETTINGS_OPTION,
		TIAGSSPACE_SETTINGS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'tiagsspace_settings_sanitize',
			'default'           => tiagsspace_settings_defaults(),
			'show_in_rest'      => false,
		)
	);

	foreach ( tiagsspace_settings_schema() as $section_id => $section ) {
		$section_key = 'tiagsspace_settings_' . $section_id;
		add_settings_section( $section_key, $section['title'], '__return_false', TIAGSSPACE_SETTINGS_PAGE );

		foreach ( $section['fields'] as $field ) {
			add_settings_field(
				$field['id'],
				esc_html( $field['label'] ),
				'tiagsspace_settings_render_field',
				TIAGSSPACE_SETTINGS_PAGE,
				$section_key,
				array_merge( $field, array( 'label_for' => 'tiagsspace-setting-' . $field['id'] ) )
			);
		}
	}
}

/** Renders one field from its schema entry. */
function tiagsspace_settings_render_field( $field ) {
	$id    = $field['id'];
	$name  = TIAGSSPACE_SETTINGS_OPTION . '[' . $id . ']';
	$dom   = 'tiagsspace-setting-' . $id;
	$value = tiagsspace_setting( $id );

	switch ( $field['type'] ) {
		case 'checkbox':
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>',
				esc_attr( $dom ),
				esc_attr( $name ),
				checked( (bool) $value, true, false ),
				esc_html( $field['checkbox_label'] )
			);
			break;
	}

	if ( ! empty( $field['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $field['description'] ) );
	}
}

add_action( 'admin_menu', 'tiagsspace_settings_menu' );
function tiagsspace_settings_menu() {
	add_theme_page(
		__( 'Theme Settings', 'tiagsspace' ),
		__( 'Theme Settings', 'tiagsspace' ),
		'manage_options',
		TIAGSSPACE_SETTINGS_PAGE,
		'tiagsspace_settings_page'
	);
}

function tiagsspace_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<?php settings_errors(); // Core prints "Settings saved." only on Settings screens. ?>
		<form action="options.php" method="post">
			<?php
			settings_fields( TIAGSSPACE_SETTINGS_OPTION );
			do_settings_sections( TIAGSSPACE_SETTINGS_PAGE );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
