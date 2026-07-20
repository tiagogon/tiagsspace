<?php
/**
 * ACF Field Groups (registered via PHP)
 *
 * This file registers all ACF field groups in code instead of the database,
 * so they are version-controlled and deploy automatically with the theme.
 *
 * To add new field groups:
 * 1. Define them here using acf_add_local_field_group()
 * 2. Use unique keys prefixed with 'group_' (groups) and 'field_' (fields)
 * 3. Never change keys of existing fields — it breaks saved data
 *
 * Field groups:
 *  1. Attachment settings        — per-attachment gallery/display options
 *  2. Background & Around        — background image, hide nav, hide from archives
 *  3. Gallery                    — gallery layout, ordering, animation, columns
 *  4. Index thumbnail options    — video/animated thumbnails, column size
 *  5. Taxonomy Options           — is_archived flag for log-branch
 *  6. Video Player Options       — embed URL, self-host, player config (films/hyper)
 *  7. Video player per attachment — per-video player overrides
 *  8. Video player(s) per post   — per-post video player overrides
 *  9. Re-attach images           — move attachments between posts from editor
 *
 * @see https://www.advancedcustomfields.com/resources/register-fields-via-php/
 */

add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// =========================================================================
	// 1. Attachment settings
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_55d328d31d76a',
		'title' => 'Attachment settings',
		'fields' => array(
			array(
				'key' => 'field_5589d79cafa95',
				'label' => 'Remove from default gallery',
				'name' => 'remove_from_default_gallery',
				'type' => 'true_false',
			),
			array(
				'key' => 'field_5589dec273634',
				'label' => 'Diferent size on Gallery',
				'name' => 'diferent_size_on_gallery',
				'type' => 'select',
				'instructions' => 'Values also hardcoded on functions.php',
				'choices' => array(
					'0.25' => '4x',
					'0.3333333' => '3x',
					'0.5' => '2x',
					'0.75' => '1.5x',
					1 => 'same',
					2 => '1/2',
					3 => '1/3',
					4 => '1/4',
					'5: 1/5' => '5: 1/5',
				),
				'default_value' => 1,
				'allow_null' => 1,
			),
			array(
				'key' => 'field_5e2c521aa3db0',
				'label' => 'Margin',
				'name' => 'attachment_margin',
				'type' => 'range',
				'min' => -100,
			),
			array(
				'key' => 'field_5e2b8ddb5f771',
				'label' => 'Margin-left',
				'name' => 'attachment_margin_left',
				'type' => 'range',
				'min' => -100,
			),
			array(
				'key' => 'field_5e2b8dc05f76f',
				'label' => 'Margin-right',
				'name' => 'attachment_margin_right',
				'type' => 'range',
				'min' => -100,
			),
			array(
				'key' => 'field_5e2b8c1129125',
				'label' => 'Margin-top',
				'name' => 'attachment_margin_top',
				'type' => 'range',
				'min' => -100,
			),
			array(
				'key' => 'field_5e2b8dd15f770',
				'label' => 'Margin-bottom',
				'name' => 'attachment_margin_bottom',
				'type' => 'range',
				'min' => -100,
			),
			array(
				'key' => 'field_5f580d9a91b01',
				'label' => 'Z Index',
				'name' => 'attachment_z_index',
				'type' => 'range',
				'min' => -100,
			),
			array(
				'key' => 'field_57d5bbad206e7',
				'label' => 'Insert SRC of higher resolution',
				'name' => 'insert_src_of_higher_resolution',
				'type' => 'true_false',
				'instructions' => 'Use this for animated GIFs',
			),
			array(
				'key' => 'field_5a2dc2ca742ed',
				'label' => 'Skip random order',
				'name' => 'skip_random',
				'type' => 'true_false',
			),
		),
		'location' => array(
			array( array( 'param' => 'attachment', 'operator' => '==', 'value' => 'all' ) ),
		),
		'position' => 'acf_after_title',
		'active' => true,
	) );

	// =========================================================================
	// 2. Background & Around
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_576838826520e',
		'title' => 'Background & Around',
		'fields' => array(
			array(
				'key' => 'field_6004a64a5142b',
				'label' => 'Background Image',
				'name' => 'background_image',
				'type' => 'image',
				'return_format' => 'array',
				'preview_size' => 'thumbnail',
			),
			array(
				'key' => 'field_6921f6b4a85a1',
				'label' => 'Hide Previouse, Next & Related Posts',
				'name' => 'disable_previouse_next_&_related_posts',
				'type' => 'true_false',
				'message' => 'Hide all to share films and content for external submissions',
			),
			array(
				'key' => 'field_6929d49564ca2',
				'label' => 'Hide post from main page archives and feed',
				'name' => 'hide_post_from_main_page_archives_and_feed',
				'type' => 'true_false',
			),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => '4k-lento' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'films' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'hyper' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'dusk' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ),
		),
		'active' => true,
	) );

	// =========================================================================
	// 3. Gallery
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_55d328d2d8225',
		'title' => 'Gallery',
		'fields' => array(

			// --- Ordering ---
			array(
				'key' => 'field_5a2dbd936ecca',
				'label' => 'Order media attachments',
				'name' => 'order_media_attachments',
				'type' => 'radio',
				'instructions' => 'Save post to reorder media attachments.',
				'wrapper' => array( 'width' => '51' ),
				'choices' => array(
					'chronological' => 'Chronological',
					'capture_time'  => 'Capture time (EXIF)',
					'random'        => 'Random',
				),
				'allow_null' => 1,
				'layout' => 'horizontal',
			),
			array(
				'key' => 'field_5ae9f4c3fd95d',
				'label' => 'Order just the new added pictures',
				'name' => 'order_just_the_new_added_pictures',
				'type' => 'true_false',
				'instructions' => "This will order just the new added images ('menu_order' => 0) after the current order.",
				'conditional_logic' => array(
					array( array( 'field' => 'field_5a2dbd936ecca', 'operator' => '==', 'value' => 'chronological' ) ),
					array( array( 'field' => 'field_5a2dbd936ecca', 'operator' => '==', 'value' => 'capture_time' ) ),
				),
				'wrapper' => array( 'width' => '49' ),
			),

			// --- Deletion ---
			array(
				'key' => 'field_5929a3e121b2d',
				'label' => 'Delete non selected images',
				'name' => 'delete_not_selected_image',
				'type' => 'true_false',
				'instructions' => 'Will delete all the media attachments to this post marked as remove from gallery.',
				'wrapper' => array( 'width' => '51' ),
				'message' => 'Delete non selected images',
			),
			array(
				'key' => 'field_5ae9fe0947140',
				'label' => 'Delete all the attached media!',
				'name' => 'delete_all_the_attached_media',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_5929a3e121b2d', 'operator' => '==', 'value' => '1' ) ) ),
				'wrapper' => array( 'width' => '49' ),
			),

			// --- Gallery toggle ---
			array(
				'key' => 'field_544eab85fc4e1',
				'label' => 'Deactivate gallery',
				'name' => 'deactivate_gallery',
				'type' => 'true_false',
			),

			// --- Removed Images (repeater) ---
			array(
				'key' => 'field_5526e975589e8',
				'label' => 'Removed Images',
				'name' => 'removed_images',
				'type' => 'repeater',
				'conditional_logic' => array( array( array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ) ) ),
				'layout' => 'table',
				'button_label' => 'Remove Image',
				'sub_fields' => array(
					array(
						'key' => 'field_5526e975589e9',
						'label' => 'Image Removed',
						'name' => 'image_removed',
						'type' => 'image',
						'preview_size' => 'thumbnail',
						'library' => 'uploadedTo',
						'return_format' => 'id',
						'parent_repeater' => 'field_5526e975589e8',
					),
				),
			),

			// --- Animation ---
			array(
				'key' => 'field_5aa46b0b71393',
				'label' => 'Animation',
				'name' => 'animation',
				'type' => 'radio',
				'conditional_logic' => array( array( array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ) ) ),
				'wrapper' => array( 'width' => '33' ),
				'choices' => array(
					'none' => 'None',
					'swap-randomly-positions-constant-frequency' => 'Swap randomly positions on constant frequency',
				),
				'default_value' => 'none',
				'layout' => 'vertical',
			),
			array(
				'key' => 'field_5aa46d3eb1704',
				'label' => 'Animation BPM',
				'name' => 'animation_bpm',
				'type' => 'number',
				'conditional_logic' => array( array( array( 'field' => 'field_5aa46b0b71393', 'operator' => '==', 'value' => 'swap-randomly-positions-constant-frequency' ) ) ),
				'wrapper' => array( 'width' => '33' ),
				'default_value' => 135,
			),
			array(
				'key' => 'field_6396369f3ca45',
				'label' => 'Animation Number of Attachment Shown',
				'name' => 'animation_number_of_attachment_shown',
				'type' => 'number',
				'conditional_logic' => array( array( array( 'field' => 'field_5aa46b0b71393', 'operator' => '==', 'value' => 'swap-randomly-positions-constant-frequency' ) ) ),
				'wrapper' => array( 'width' => '33' ),
			),

			// --- Horizontal gallery ---
			array(
				'key' => 'field_57e18ba7f5f27',
				'label' => 'Horizontal Gallery',
				'name' => 'horizontal_gallery',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ) ) ),
			),
			array(
				'key' => 'field_6481d1e5c4c43',
				'label' => 'Horizontal Gallery Style',
				'name' => 'horizontal_gallery_style',
				'type' => 'select',
				'conditional_logic' => array( array( array( 'field' => 'field_57e18ba7f5f27', 'operator' => '==', 'value' => '1' ) ) ),
				'choices' => array(
					'still' => 'still',
					'insta' => 'insta',
					'classic' => 'classic',
				),
				'default_value' => 'still',
			),
			array(
				'key' => 'field_6481ed7621099',
				'label' => 'Duration (+/-100ms)',
				'name' => 'still_duration',
				'type' => 'number',
				'conditional_logic' => array(
					array( array( 'field' => 'field_6481d1e5c4c43', 'operator' => '==', 'value' => 'still' ) ),
					array( array( 'field' => 'field_6481d1e5c4c43', 'operator' => '==', 'value' => 'insta' ) ),
				),
				'min' => 400,
				'placeholder' => '1800 when empety - min should be 400',
			),

			// --- Alternative gallery ---
			array(
				'key' => 'field_544eabf7fc4e2',
				'label' => 'Alternative gallery',
				'name' => 'alternative_gallery',
				'type' => 'true_false',
				'instructions' => 'Set for an alternative way to display the images',
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
					array( 'field' => 'field_57e18ba7f5f27', 'operator' => '!=', 'value' => '1' ),
				) ),
			),
			array(
				'key' => 'field_546e7e6146bab',
				'label' => 'Container',
				'name' => 'container',
				'type' => 'radio',
				'required' => 1,
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
				) ),
				'choices' => array(
					'container' => 'Container',
					'container-fluid' => 'Full Width',
				),
				'default_value' => 'container',
				'layout' => 'horizontal',
			),
			array(
				'key' => 'field_65d0dd039eee6',
				'label' => 'Item Max heigh',
				'name' => 'item_max_heigh',
				'type' => 'select',
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
					array( 'field' => 'field_546e7e6146bab', 'operator' => '==', 'value' => 'container-fluid' ),
				) ),
				'choices' => array(
					'max-heigh-90vh-left' => 'Left',
					'max-heigh-90vh-right' => 'Right',
				),
				'default_value' => false,
				'allow_null' => 1,
			),
			array(
				'key' => 'field_5687f3d7ba1c4',
				'label' => 'Deactivat Masonry',
				'name' => 'deactivat_masonry',
				'type' => 'true_false',
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
				) ),
			),
			array(
				'key' => 'field_5470962b25c59',
				'label' => 'No space',
				'name' => 'no_space',
				'type' => 'true_false',
				'instructions' => 'Check it to remove the space between images.',
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
				) ),
			),
			array(
				'key' => 'field_558b40147ee22',
				'label' => 'Spacement',
				'name' => 'spacement',
				'type' => 'radio',
				'instructions' => 'Not applied to Container Fluid',
				'conditional_logic' => array( array(
					array( 'field' => 'field_5470962b25c59', 'operator' => '!=', 'value' => '1' ),
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
				) ),
				'choices' => array(
					': 40px (Theme Gallery)' => ': 40px (Theme Gallery)',
					'spacement-2' => '2px',
					'spacement-4' => '4px',
					'spacement-8' => '8px',
					'spacement-16' => '16px',
					'spacement-20' => '20px (Bootstrap framework)',
				),
				'layout' => 'horizontal',
			),
			array(
				'key' => 'field_547097ad6d13c',
				'label' => 'Link to a image Light Box',
				'name' => 'light_box',
				'type' => 'radio',
				'instructions' => 'Set none to remove link.',
				'required' => 1,
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
				) ),
				'choices' => array(
					'none' => 'None',
					'magnific_popup' => 'Magnific Popup',
					'intense-images' => 'Intense Images',
				),
				'default_value' => 'magnific_popup',
				'layout' => 'horizontal',
			),

			// --- Responsive columns ---
			array(
				'key' => 'field_547093d2c0863',
				'label' => 'Columns in phones',
				'name' => 'columns_xs',
				'type' => 'number',
				'instructions' => 'If not defined it will have be 1.',
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
				) ),
			),
			array(
				'key' => 'field_5470942bc0867',
				'label' => 'Columns in tablets',
				'name' => 'columns_sm',
				'type' => 'number',
				'instructions' => 'If not defined it will be like in phones.',
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
				) ),
			),
			array(
				'key' => 'field_54709429c0866',
				'label' => 'Columns in desktops',
				'name' => 'columns_md',
				'type' => 'number',
				'instructions' => 'If not defined it will be like in tablets.',
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
				) ),
			),
			array(
				'key' => 'field_54709427c0865',
				'label' => 'Columns in large desktops',
				'name' => 'columns_lg',
				'type' => 'number',
				'instructions' => 'If not defined it will be like in normal desktops',
				'conditional_logic' => array( array(
					array( 'field' => 'field_544eabf7fc4e2', 'operator' => '==', 'value' => '1' ),
					array( 'field' => 'field_544eab85fc4e1', 'operator' => '!=', 'value' => '1' ),
				) ),
			),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'dusk' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'hyper' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'log' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'films' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cityburns' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => '4k-lento' ) ),
		),
		'instruction_placement' => 'label',
		'active' => true,
	) );

	// =========================================================================
	// 4. Index thumbnail options
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_5a161dd5bb6a4',
		'title' => 'Index thumbnail options',
		'fields' => array(
			array(
				'key' => 'field_5a1765b2a6200',
				'label' => 'Video thumbnail',
				'name' => 'video_thumbnail',
				'type' => 'file',
				'return_format' => 'id',
				'instructions' => "Upload MP4 (H.264); .mov won't play in all browsers.",
			),
			array(
				'key' => 'field_5a161de1ff4c4',
				'label' => 'Animated thumbnail',
				'name' => 'animated_thumbnail',
				'type' => 'image',
				'return_format' => 'array',
				'preview_size' => 'thumbnail',
				'library' => 'uploadedTo',
				'mime_types' => '.gif',
			),
			array(
				'key' => 'field_5a161e35ff4c5',
				'label' => 'Column Size Increment',
				'name' => 'column_size_increment',
				'type' => 'number',
				'placeholder' => 'Random(-1.0,1)',
				'min' => -2,
				'max' => 2,
				'step' => 1,
			),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'films' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'hyper' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'log' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'emulsion' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'dusk' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => '4k-lento' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cityburns' ) ),
		),
		'position' => 'side',
		'active' => true,
	) );

	// =========================================================================
	// 5. Taxonomy Options (log-branch archived flag)
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_648b215832720',
		'title' => 'Taxonomy Options',
		'fields' => array(
			array(
				'key' => 'field_648b215886690',
				'label' => 'Is archived',
				'name' => 'is_archived',
				'type' => 'true_false',
			),
		),
		'location' => array(
			array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'log-branch' ) ),
		),
		'active' => true,
	) );

	// =========================================================================
	// 6. Video Player Options (films & hyper)
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_58247bb8c1dbd',
		'title' => 'Video Player Options',
		'fields' => array(
			array(
				'key' => 'field_687d30e9f178d',
				'label' => 'Self Host Film',
				'name' => 'self_host_film',
				'type' => 'file',
				'return_format' => 'id',
				'instructions' => "Select a video (MP4 / H.264 - .mov won't play in all browsers) for the progressive quality ladder, OR an HLS bundle (the .m3u8 attachment created when you upload a .hlspack.zip) for adaptive 4K streaming. The file type is the mode switch: .m3u8 = HLS, any other video = MP4.",
			),
			array(
				'key' => 'field_6318db3971842',
				'label' => 'Film Player Options',
				'name' => 'film_player_options',
				'type' => 'checkbox',
				'instructions' => "Per-film overrides for the video player.\n\n* Background will have the following effect: 1) All player toggles and elements will be turned off (including the play/pause button), 2) The video will automatically loop. 3) The video will be set to autoplay. 4) The video will be muted",
				'choices' => array(
					'loop' => 'Loop',
					'autoplay' => 'Autoplay',
					'muted' => 'Muted',
					'controls' => 'Display controls',
					'keyboard' => 'Keyboard',
				),
				'default_value' => array( 'autopause', 'dnt', 'controls', 'keyboard', 'playsinline' ),
				'layout' => 'vertical',
			),
			array(
				'key' => 'field_69a1c4e2b7f31',
				'label' => 'Caption tracks',
				'name' => 'film_caption_tracks',
				'type' => 'repeater',
				'instructions' => "WebVTT (.vtt) subtitle tracks for this film. Works for both HLS and MP4 films.\n\nVideopack's own captions panel never appears for an .m3u8 attachment, so this is the place to manage them. Tick Default on the track that should be pre-selected — if none is ticked, the first row wins.",
				'conditional_logic' => array( array( array( 'field' => 'field_687d30e9f178d', 'operator' => '!=empty' ) ) ),
				'layout' => 'table',
				'button_label' => 'Add caption track',
				'sub_fields' => array(
					array(
						'key' => 'field_69a1c4e2b7f35',
						'label' => 'File',
						'name' => 'caption_file',
						'type' => 'file',
						'return_format' => 'url',
						'required' => 1,
						'mime_types' => 'vtt',
						'instructions' => 'A .vtt file from the Media Library.',
					),
					array(
						'key' => 'field_69a1c4e2b7f39',
						'label' => 'Language',
						'name' => 'caption_srclang',
						'type' => 'text',
						'required' => 1,
						'maxlength' => 5,
						'placeholder' => 'pt',
						'instructions' => 'Language code, e.g. pt or en.',
					),
					array(
						'key' => 'field_69a1c4e2b7f3d',
						'label' => 'Label',
						'name' => 'caption_label',
						'type' => 'text',
						'placeholder' => 'Português',
						'instructions' => 'Shown in the player captions menu.',
					),
					array(
						'key' => 'field_69a1c4e2b7f41',
						'label' => 'Default',
						'name' => 'caption_default',
						'type' => 'true_false',
						'ui' => 1,
					),
				),
			),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'films' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'hyper' ) ),
		),
		'active' => true,
	) );

	// =========================================================================
	// 7. Video player per attachment
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_69696da459a63',
		'title' => 'Video player per attachment',
		'fields' => array(
			array(
				'key' => 'field_69696da4d1811',
				'label' => 'Alternative video player',
				'name' => 'alternative_video_player_options',
				'type' => 'true_false',
				'instructions' => "Define different player options for all videos on the gallery, unless it's defined on the video attachment.",
				'message' => 'Define player options',
			),
			array(
				'key' => 'field_69696da4d1815',
				'label' => 'Controls',
				'name' => 'video_controls',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_69696da4d1811', 'operator' => '==', 'value' => '1' ) ) ),
			),
			array(
				'key' => 'field_69696da4d1818',
				'label' => 'Mute',
				'name' => 'video_mute',
				'type' => 'true_false',
				'instructions' => 'It will also hide the sound button.',
				'conditional_logic' => array( array( array( 'field' => 'field_69696da4d1811', 'operator' => '==', 'value' => '1' ) ) ),
				'message' => 'Mute the video',
			),
			array(
				'key' => 'field_69696da4d181b',
				'label' => 'Loop',
				'name' => 'video_loop',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_69696da4d1811', 'operator' => '==', 'value' => '1' ) ) ),
				'message' => 'Play it in loop',
			),
			array(
				'key' => 'field_69696da4d181e',
				'label' => 'Autoplay',
				'name' => 'video_autoplay',
				'type' => 'true_false',
				'instructions' => 'Autoplays will just play on iOS devices if the video is also mute.',
				'conditional_logic' => array( array( array( 'field' => 'field_69696da4d1811', 'operator' => '==', 'value' => '1' ) ) ),
				'message' => 'Start to play the video on page load (does not work on iOS)',
			),
			array(
				'key' => 'field_69696da4d1820',
				'label' => 'Pause other videos',
				'name' => 'video_pauseothervideos',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_69696da4d1811', 'operator' => '==', 'value' => '1' ) ) ),
				'message' => 'Pause other videos playing at the same time',
			),
			array(
				'key' => 'field_69696da4d1823',
				'label' => 'Preload',
				'name' => 'video_preload',
				'type' => 'radio',
				'conditional_logic' => array( array( array( 'field' => 'field_69696da4d1811', 'operator' => '==', 'value' => '1' ) ) ),
				'choices' => array(
					'metadata' => 'Load just the metadata',
					'auto' => 'Load around 1minute (does not work on mobile)',
				),
				'default_value' => 'metadata',
				'layout' => 'vertical',
			),
			array(
				'key' => 'field_69696da4d1826',
				'label' => 'Video Schema',
				'name' => 'video_schema',
				'type' => 'true_false',
				'instructions' => 'Add video schema for the first video on Twitter and Facebook.',
				'conditional_logic' => array( array( array( 'field' => 'field_69696da4d1811', 'operator' => '==', 'value' => '1' ) ) ),
			),
		),
		'location' => array(
			array( array( 'param' => 'attachment', 'operator' => '==', 'value' => 'video' ) ),
		),
		'active' => true,
	) );

	// =========================================================================
	// 8. Video player(s) per post
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_59cf9e9e8f51f',
		'title' => 'Video player(s) per post',
		'fields' => array(
			array(
				'key' => 'field_59cf9efdaeb67',
				'label' => 'Alternative video player(s)',
				'name' => 'alternative_video_player_options_on_post',
				'type' => 'true_false',
				'instructions' => "Define different player options for all videos on the gallery, unless it's defined on the video attachment.",
				'message' => 'Define player options',
			),
			array(
				'key' => 'field_61158e3eeaf05',
				'label' => 'Controls',
				'name' => 'post_video_controls',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_59cf9efdaeb67', 'operator' => '==', 'value' => '1' ) ) ),
			),
			array(
				'key' => 'field_59cfa9f1c71a2',
				'label' => 'Mute',
				'name' => 'post_video_mute',
				'type' => 'true_false',
				'instructions' => 'It will also hide the sound button.',
				'conditional_logic' => array( array( array( 'field' => 'field_59cf9efdaeb67', 'operator' => '==', 'value' => '1' ) ) ),
				'message' => 'Mute the video',
			),
			array(
				'key' => 'field_59cfaa2ee2998',
				'label' => 'Loop',
				'name' => 'post_video_loop',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_59cf9efdaeb67', 'operator' => '==', 'value' => '1' ) ) ),
				'message' => 'Play it in loop',
			),
			array(
				'key' => 'field_59cfabb151f72',
				'label' => 'Autoplay',
				'name' => 'post_video_autoplay',
				'type' => 'true_false',
				'instructions' => 'Autoplays will just play on iOS devices if the video is also mute.',
				'conditional_logic' => array( array( array( 'field' => 'field_59cf9efdaeb67', 'operator' => '==', 'value' => '1' ) ) ),
				'message' => 'Start to play the video on page load (does not work on iOS)',
			),
			array(
				'key' => 'field_59cfadc2bf6ee',
				'label' => 'Pause other videos',
				'name' => 'post_video_pauseothervideos',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_59cf9efdaeb67', 'operator' => '==', 'value' => '1' ) ) ),
				'message' => 'Pause other videos playing at the same time',
			),
			array(
				'key' => 'field_59cfac1fa478b',
				'label' => 'Preload',
				'name' => 'post_video_preload',
				'type' => 'radio',
				'conditional_logic' => array( array( array( 'field' => 'field_59cf9efdaeb67', 'operator' => '==', 'value' => '1' ) ) ),
				'choices' => array(
					'metadata' => 'Load just the metadata',
					'auto' => 'Load around 1minute (does not work on mobile)',
				),
				'default_value' => 'metadata',
				'layout' => 'vertical',
			),
			array(
				'key' => 'field_59f4b845db2c6',
				'label' => 'Video Schema',
				'name' => 'video_schema',
				'type' => 'true_false',
				'instructions' => 'Add video schema for the first video on Twitter and Facebook.',
				'conditional_logic' => array( array( array( 'field' => 'field_59cf9efdaeb67', 'operator' => '==', 'value' => '1' ) ) ),
			),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'dusk' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'emulsion' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'log' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'hyper' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'films' ) ),
		),
		'active' => true,
	) );

	// =========================================================================
	// 9. Re-attach images from post editor
	// =========================================================================
	acf_add_local_field_group( array(
		'key' => 'group_5921cdc08a134',
		'title' => 'Re-attach images from post editor',
		'fields' => array(
			array(
				'key' => 'field_5921cdfcd2e32',
				'label' => 'Re-attach images from post editor',
				'name' => 're-attach_images_from_post_editor',
				'type' => 'true_false',
			),
			array(
				'key' => 'field_5921d2baf3f6e',
				'label' => 'Destination post',
				'name' => 'destination_post',
				'type' => 'relationship',
				'conditional_logic' => array( array( array( 'field' => 'field_5921cdfcd2e32', 'operator' => '==', 'value' => '1' ) ) ),
				'filters' => array( 'search', 'post_type', 'taxonomy' ),
				'elements' => array( 'featured_image' ),
				'max' => 1,
				'return_format' => 'id',
			),
			array(
				'key' => 'field_5921ce1ed2e33',
				'label' => 'Re-attach mode',
				'name' => 're-attach_mode',
				'type' => 'radio',
				'conditional_logic' => array( array( array( 'field' => 'field_5921cdfcd2e32', 'operator' => '==', 'value' => '1' ) ) ),
				'choices' => array( 'keep' => 'Keep these images', 'reattach' => 'Re-attach these images' ),
				'default_value' => 'reattach',
				'return_format' => 'value',
			),
			array(
				'key' => 'field_select_all_vertical_images',
				'label' => 'All vertical images',
				'name' => 'select_all_vertical_images',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_5921cdfcd2e32', 'operator' => '==', 'value' => '1' ) ) ),
			),
			array(
				'key' => 'field_select_all_horizontal_images',
				'label' => 'All horizontal images',
				'name' => 'select_all_horizontal_images',
				'type' => 'true_false',
				'conditional_logic' => array( array( array( 'field' => 'field_5921cdfcd2e32', 'operator' => '==', 'value' => '1' ) ) ),
			),
			array(
				'key' => 'field_5921cea9d2e34',
				'label' => 'Selected Images',
				'name' => 'selected_images',
				'type' => 'gallery',
				'conditional_logic' => array( array( array( 'field' => 'field_5921cdfcd2e32', 'operator' => '==', 'value' => '1' ) ) ),
				'insert' => 'append',
				'library' => 'uploadedTo',
			),
		),
		'location' => array(
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'dusk' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'emulsion' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'log' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'hyper' ) ),
			array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'films' ) ),
		),
		'active' => true,
	) );

} );
