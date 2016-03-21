<?php

if( function_exists('acf_add_local_field_group') ):

	acf_add_local_field_group(array (
		'key' => 'group_56a6b30932121',
		'title' => 'Products: Variations',
		'fields' => array (
			array (
				'key' => 'field_56bb7c3087db9',
				'label' => 'Variation',
				'name' => 'product_has_variations',
				'type' => 'true_false',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array (
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => 'This product offers variations of the base product.',
				'default_value' => 0,
			),
			array (
				'key' => 'field_56a2bec58ad32',
				'label' => 'Customizable',
				'name' => 'product_is_customizable',
				'type' => 'true_false',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => array (
					array (
						array (
							'field' => 'field_56bb7c3087db9',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
				'wrapper' => array (
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => 'This product uses the step-by-step product builder instead of variation dropdowns.',
				'default_value' => 0,
			),
			array (
				'key' => 'field_56afe65344c13',
				'label' => 'Variations',
				'name' => 'builder_variations',
				'type' => 'repeater',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => array (
					array (
						array (
							'field' => 'field_56bb7c3087db9',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
				'wrapper' => array (
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => 'field_56afe6c944c15',
				'min' => 1,
				'max' => '',
				'layout' => 'block',
				'button_label' => 'Add Variation',
				'sub_fields' => array (
					array (
						'key' => 'field_56afe6c944c15',
						'label' => 'Variation Title',
						'name' => 'variation_title',
						'type' => 'text',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'readonly' => 0,
						'disabled' => 0,
					),
					array (
						'key' => 'field_56afe73f44c16',
						'label' => 'Options',
						'name' => 'options',
						'type' => 'repeater',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'collapsed' => 'field_56afe75c44c17',
						'min' => 1,
						'max' => '',
						'layout' => 'table',
						'button_label' => 'Add Option',
						'sub_fields' => array (
							array (
								'key' => 'field_56afe75c44c17',
								'label' => 'Name',
								'name' => 'name',
								'type' => 'text',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array (
									'width' => 33,
									'class' => '',
									'id' => '',
								),
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
							array (
								'key' => 'field_56afe76544c18',
								'label' => 'Price',
								'name' => 'price',
								'type' => 'number',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array (
									'width' => 25,
									'class' => '',
									'id' => '',
								),
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '$',
								'append' => '',
								'min' => '',
								'max' => '',
								'step' => '0.01',
								'readonly' => 0,
								'disabled' => 0,
							),
							array (
								'key' => 'field_56afe77e44c19',
								'label' => 'Image',
								'name' => 'image',
								'type' => 'image',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array (
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'return_format' => 'array',
								'preview_size' => 'thumbnail',
								'library' => 'all',
								'min_width' => '',
								'min_height' => '',
								'min_size' => '',
								'max_width' => '',
								'max_height' => '',
								'max_size' => '',
								'mime_types' => '',
							),
							array (
								'key' => 'field_56afea62ad28c',
								'label' => 'Skip Variation',
								'name' => 'skip',
								'type' => 'text',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array (
									'width' => 25,
									'class' => '',
									'id' => '',
								),
								'default_value' => '',
								'placeholder' => 'Variation Title To Skip',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
						),
					),
				),
			),
			array (
				'key' => 'field_56afea7fad28d',
				'label' => 'About Ignoring Variations',
				'name' => '',
				'type' => 'message',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array (
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => 'You can set a variation option so that a future step in the product builder may be skipped. This will allow certain variations to be optional.

In example, you may offer a wired and wireless version of the same product. Because the wireless version has no cables, you can skip the "Cable Color" variation. To do so, you would find the Wireless option and change the "Skip Variation"

In some cases you may have one variation, for example "Connection Type". You might have another variation such as "Cable Color". However, you might have a connection type	named "Wireless" in which case the cable color option does not apply.

In this example, you should find the "Wireless" option and tell it to ignore the "Cable Color" variation, <a href="https://s3-us-west-2.amazonaws.com/elasticbeanstalk-us-west-2-868470985522/ShareX/LimelightDept/2016/02/chrome_2016-02-01_15-50-39.jpg" target="_blank">Click here for an example</a>.

You can specify more than one step to skip, in which case you separate with commas (spaces are optional) such as: <code>Cable Color, Cable Length</code>.',
				'new_lines' => 'wpautop',
				'esc_html' => 0,
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'product',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => 1,
		'description' => '',
	));

endif;