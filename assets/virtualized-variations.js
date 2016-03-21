jQuery(function() {
	// wcvv_init_floating_final_total();

	setTimeout(wcvv_init_builder_logic, 300);
});

function wcvv_init_floating_final_total() {
	var $final = jQuery('#builder-final');
	var $body = jQuery('body');
	var debounce_timer = false;
	var is_fixed = false;

	var recalculate_final_position = function(e) {
		var bottom_x = $body.scrollTop() + jQuery(window).height();
		var final_top = $final.offset().top;

		if ( is_fixed !== (final_top >= bottom_x) ) {
			is_fixed = (final_top >= bottom_x);
			$final
				.toggleClass( 'final-fixed', is_fixed )
				.toggleClass( 'final-static', !is_fixed );
		}
	};

	jQuery(window).unbind('resize').resize(function(e) {
		if ( debounce_timer !== false ) clearTimeout(debounce_timer);
		debounce_timer = setTimeout(function() {
			recalculate_final_position();
		}, 150);
	});

	jQuery(window).unbind('scroll').scroll(function(e) {
		recalculate_final_position();
	});
}


function wcvv_init_builder_logic() {
	var $builder = jQuery('#product-builder');
	var $steps = $builder.find('.builder-actual-steps .builder-step');
	var $final = jQuery('#builder-final');

	var $builder_fields = {
		step_current: $final.find('.step-current'),
		steps_total: $final.find('.step-total'),

		subtotal: $final.find('.builder-subtotal'),
		subtotal_price: $final.find('.builder-subtotal .total-price'),

		total: $final.find('.builder-total'),
		total_price: $final.find('.builder-total .total-price'),

		cart_disabled: $final.find('.builder-add-to-cart-placeholder'),
		cart_submit: $final.find('.builder-add-to-cart-submit'),

		indicator_steps: $final.find('.builder-step-indicator'),
		indicator_complete: $final.find('.builder-step-complete'),

		quantity: $final.find('input.qty'),
		calculated_price: jQuery('#vv-calculated_price')
	};

	var starting_price = jQuery('#vv-starting_price').val();
	var original_price = jQuery('#vv-original_price').val();

	var builder_price = starting_price;

	var steps_to_skip = [];

	var step_get_value = function( $step ) {
		var $input = $step.find('input:radio:checked');
		if ( $input.length < 1 ) return false;

		if ( $input.val() === '_skip' ) {
			var step_title = $step.find('input.step-field-title').val();

			// Verify that this step should be skipped
			if ( steps_to_skip.indexOf( step_title ) < 0 ) {
				// Step was set to skip, but was not preceeded by an option allowing it to be skipped
				$input.prop('checked', false);
				return false;
			}else{
				// This step was skipped
				return {
					'element': $input,
					'value': '_skip',
					'title': 'N/A',
					'skip': '',
					'price': '0'
				};
			}
		}

		return {
			'element': $input,
			'value': $input.val(),
			'title': $input.attr('data-title'),
			'skip': $input.attr('data-skip-steps'),
			'price': $input.attr('data-price') ? parseFloat($input.attr('data-price')) : '0'
		};
	};

	var count_steps = function( condition, $the_steps ) {
		if ( typeof condition == 'undefined' ) condition = 'total'; // Possible conditions: total, completed, skipped, incomplete
		if ( typeof $the_steps == 'undefined' ) $the_steps = $steps;

		if ( condition === 'total' ) return $the_steps.length;

		var count = 0;

		$the_steps.each(function() {
			var selected = step_get_value( jQuery(this) );

			switch( condition ) {
				case 'completed':
					if ( selected !== false ) count++; break;

				case 'skipped':
					if ( selected !== false && selected.value === '_skip' ) count++; break;

				case 'incomplete':
					if ( selected === false ) count++; break;
			}
		});

		return count;
	};

	var next_incomplete_step = function( $the_steps ) {
		if ( !$the_steps || $the_steps.length < 1 ) $the_steps = $steps;
		var $incomplete_step = false;

		$the_steps.each(function() {
			var selected = step_get_value( jQuery(this) );

			if ( selected === false ) {
				$incomplete_step = jQuery(this);
				return false;
			}
		});

		return $incomplete_step;
	};

	var last_completed_step = function() {
		jQuery( $steps.get().reverse() ).each(function() {
			var selected = step_get_value( jQuery(this) );

			if ( selected !== false ) {
				return jQuery(this);
			}
		});

		return false;
	};

	var builder_format_price = function( value ) {
		value = Math.ceil( parseFloat(value) * 100 ) / 100;
		if ( value % 1 > 0 ) value = value.toFixed(2);
		return value;
	};

	var update_builder_final_field = function() {
		var $current_step = last_completed_step();
		var $next_step = next_incomplete_step( $current_step );

		var steps_total = count_steps( "total" );
		var steps_completed = count_steps( "completed" );
		var steps_skipped = count_steps( "skipped" );
		var steps_remaining = count_steps( "total" ) - steps_completed;

		var steps_total_not_skipped = steps_total - steps_skipped;

		$builder_fields.steps_total.html( steps_total_not_skipped );
		$builder_fields.calculated_price.val( builder_price );

		// Display subtotal
		$builder_fields.total.css('display', 'none');
		$builder_fields.subtotal.css('display', 'block');
		$builder_fields.subtotal_price.html( builder_format_price( builder_price ) );

		if ( steps_remaining < 1 ) {
			// Show qty next to subtotal
			var qty = $builder_fields.quantity.val();
			$builder_fields.subtotal_price.append( " &times; " + qty );

			// Display total, only when completed
			var total = builder_price * qty;
			$builder_fields.total.css('display', 'block');
			$builder_fields.total_price.html( builder_format_price( total ) );

			// Other stuff
			$builder_fields.cart_disabled.css('display', 'none');
			$builder_fields.cart_submit.css('display', 'block');

			$builder_fields.indicator_complete.css('display', 'block');
			$builder_fields.indicator_steps.css('display', 'none');
			$builder_fields.step_current.html( 'Ready to Order' );
		}else{
			$builder_fields.cart_disabled.css('display', 'block');
			$builder_fields.cart_submit.css('display', 'none');

			$builder_fields.indicator_complete.css('display', 'none');
			$builder_fields.indicator_steps.css('display', 'block');
			if ( $next_step.length > 0) {
				$builder_fields.step_current.html( $next_step.find('span.step-number').html() );
			}else{
				$builder_fields.step_current.html( 'Step 1' );
			}
		}

		$builder
			.toggleClass('builder-incomplete', steps_remaining > 0)
			.toggleClass('builder-complete', steps_remaining < 1);
	};

	var update_builder_state = function( is_repeating ) {
		var $current_step = last_completed_step();
		var $next_step = next_incomplete_step( $current_step );

		// Settings that will be calculated here
		builder_price = starting_price;
		steps_to_skip = [];

		var step_number = 0;

		$steps.each(function() {
			var selected = step_get_value( jQuery(this) );

			// Update the step number in case a step is skipped, to show "Step 1" then "Step 2" even if the second step was skipped.
			// Note that this includes incomplete steps, as long as they aren't skipped
			if ( selected === false || (selected && selected.value !== '_skip') ) {
				step_number++;
				jQuery(this).find('span.step-number').html('Step ' + step_number);
			}

			// Add classes to indicate the status of this step.
			var classes = [
				[ 'valid', ( selected !== false ) ],
				[ 'invalid', ( selected === false ) ],
				[ 'skipped', ( selected !== false && selected.value == '_skip' ) ],
				[ 'next', ( $next_step.length > 0 && jQuery(this)[0] === $next_step[0] ) ]
			];

			for ( var i in classes ) {
				if ( !classes.hasOwnProperty(i) ) continue;

				jQuery(this).toggleClass( 'step-' + classes[i][0], classes[i][1] );
			}

			if ( selected ) {
				// Add any steps being skipped
				if ( selected.skip && typeof selected.skip === 'string' ) {
					var skip_these = selected.skip.trim().split(/, */);
					steps_to_skip = steps_to_skip.concat( skip_these );

					jQuery(this).nextAll('.builder-step').each(function() {
						var title = jQuery(this).find('input:hidden.step-field-title').val();

						if ( skip_these.indexOf(title) >= 0 ) {
							// Mark this one to be skipped but don't trigger the builder
							jQuery(this).find('input:radio.step-field-skip').prop('checked', true).trigger('change', 'skip_builder');
						}
					});
				}

				// Adjust the price if necessary
				if ( selected.price > 0 ) {
					builder_price = parseFloat(builder_price) + parseFloat(selected.price);
				}
			}
		});

		if ( typeof is_repeating == 'undefined' ) {
			// Repeat this function to let anything calculated above be populated
			update_builder_state( true );
		}else{
			update_builder_final_field();
		}
	};

	// Allow labels to check radio buttons (which is normal behavior...), and relay the click to our change event.
	$builder.on('click', 'label', function(e) {
		e.preventDefault();

		jQuery('#' + jQuery(this).attr('for')).prop('checked', true).trigger('change');
	});

	$builder.on('change', 'input:radio', function(e, note) {
		if ( typeof note == 'string' && note == 'skip_builder' ) return;

		update_builder_state();
	});

	$builder_fields.quantity.on('change', function(e) {
		update_builder_state();
	});

	update_builder_state();
}