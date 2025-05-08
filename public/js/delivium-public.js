(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 */

	$(function() {
		// Initialize delivery tracking map if element exists
		if ($('#delivium-tracking-map').length) {
			initializeTrackingMap();
		}

		// Initialize delivery status updates
		if ($('.delivium-delivery-status').length) {
			initializeStatusUpdates();
		}

		// Handle delivery rating submission
		$('.delivium-rating-form').on('submit', function(e) {
			e.preventDefault();
			submitDeliveryRating($(this));
		});

		// Initialize address autocomplete
		if (typeof google !== 'undefined' && $('.delivium-address-field').length) {
			initializeAddressAutocomplete();
		}

		// Initialize tracking functionality
		initTracking();

		// Initialize delivery completion
		initDeliveryCompletion();

		// Initialize rating submission
		initRatingSubmission();

		// Initialize driver portal
		if ($('.delivium-driver-portal').length) {
			initializeDriverPortal();
		}

		// Initialize order tracking
		if ($('.delivium-order-tracking').length) {
			initializeOrderTracking();
		}

		// Initialize status toggle
		$('#driver-status').on('change', function() {
			updateDriverStatus($(this).is(':checked') ? 'online' : 'offline');
		});

		// Initialize order actions
		$('.start-delivery').on('click', function() {
			handleDeliveryAction($(this).data('order'), 'start');
		});

		$('.complete-delivery').on('click', function() {
			handleDeliveryAction($(this).data('order'), 'complete');
		});

		// Handle order claim
		$('.delivium-claim-order').on('click', function(e) {
			e.preventDefault();
			var button = $(this);
			var orderId = button.data('order-id');

			$.ajax({
				url: delivium_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'delivium_claim_order',
					nonce: delivium_ajax.nonce,
					order_id: orderId
				},
				beforeSend: function() {
					button.prop('disabled', true).text(delivium_ajax.claiming_text || 'Claiming...');
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert(response.data.message);
						button.prop('disabled', false).text(delivium_ajax.claim_text || 'Claim Order');
					}
				},
				error: function() {
					alert(delivium_ajax.error_text || 'An error occurred. Please try again.');
					button.prop('disabled', false).text(delivium_ajax.claim_text || 'Claim Order');
				}
			});
		});

		// Handle start delivery
		$('.delivium-start-delivery').on('click', function(e) {
			e.preventDefault();
			var button = $(this);
			var orderId = button.data('order-id');

			$.ajax({
				url: delivium_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'delivium_start_delivery',
					nonce: delivium_ajax.nonce,
					order_id: orderId
				},
				beforeSend: function() {
					button.prop('disabled', true).text(delivium_ajax.starting_text || 'Starting...');
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert(response.data.message);
						button.prop('disabled', false).text(delivium_ajax.start_text || 'Start Delivery');
					}
				},
				error: function() {
					alert(delivium_ajax.error_text || 'An error occurred. Please try again.');
					button.prop('disabled', false).text(delivium_ajax.start_text || 'Start Delivery');
				}
			});
		});

		// Handle complete delivery
		$('.delivium-complete-delivery').on('click', function(e) {
			e.preventDefault();
			var button = $(this);
			var orderId = button.data('order-id');

			$.ajax({
				url: delivium_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'delivium_complete_delivery',
					nonce: delivium_ajax.nonce,
					order_id: orderId
				},
				beforeSend: function() {
					button.prop('disabled', true).text(delivium_ajax.completing_text || 'Completing...');
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert(response.data.message);
						button.prop('disabled', false).text(delivium_ajax.complete_text || 'Complete Delivery');
					}
				},
				error: function() {
					alert(delivium_ajax.error_text || 'An error occurred. Please try again.');
					button.prop('disabled', false).text(delivium_ajax.complete_text || 'Complete Delivery');
				}
			});
		});
	});

	function initializeTrackingMap() {
		var mapElement = document.getElementById('delivium-tracking-map');
		var lat = parseFloat(mapElement.dataset.lat) || 0;
		var lng = parseFloat(mapElement.dataset.lng) || 0;

		var map = new google.maps.Map(mapElement, {
			center: { lat: lat, lng: lng },
			zoom: 15
		});

		var marker = new google.maps.Marker({
			position: { lat: lat, lng: lng },
			map: map,
			title: 'Delivery Location'
		});

		// Update location periodically if tracking is enabled
		if (delivium_public.tracking_enabled) {
			setInterval(function() {
				updateDeliveryLocation(map, marker);
			}, 30000); // Update every 30 seconds
		}
	}

	function updateDeliveryLocation(map, marker) {
		$.ajax({
			url: delivium_public.ajaxurl,
			type: 'POST',
			data: {
				action: 'delivium_get_location',
				delivery_id: delivium_public.delivery_id,
				nonce: delivium_public.nonce
			},
			success: function(response) {
				if (response.success) {
					var location = response.data;
					var latLng = new google.maps.LatLng(location.lat, location.lng);
					marker.setPosition(latLng);
					map.panTo(latLng);
				}
			}
		});
	}

	function initializeStatusUpdates() {
		setInterval(function() {
			$.ajax({
				url: delivium_public.ajaxurl,
				type: 'POST',
				data: {
					action: 'delivium_get_status',
					delivery_id: delivium_public.delivery_id,
					nonce: delivium_public.nonce
				},
				success: function(response) {
					if (response.success) {
						$('.delivium-delivery-status').html(response.data.status);
						$('.delivium-estimated-time').html(response.data.eta);
					}
				}
			});
		}, 60000); // Update every minute
	}

	function submitDeliveryRating(form) {
		var submitButton = form.find('input[type="submit"]');
		var messageContainer = form.find('.delivium-message');

		$.ajax({
			url: delivium_public.ajaxurl,
			type: 'POST',
			data: form.serialize() + '&action=delivium_submit_rating&nonce=' + delivium_public.nonce,
			beforeSend: function() {
				submitButton.prop('disabled', true);
				messageContainer.html('').hide();
			},
			success: function(response) {
				if (response.success) {
					messageContainer.html(response.data.message)
						.addClass('updated')
						.removeClass('error')
						.show();
					if (response.data.redirect) {
						window.location.href = response.data.redirect;
					}
				} else {
					messageContainer.html(response.data.message)
						.addClass('error')
						.removeClass('updated')
						.show();
				}
			},
			error: function() {
				messageContainer.html('An error occurred. Please try again.')
					.addClass('error')
					.show();
			},
			complete: function() {
				submitButton.prop('disabled', false);
			}
		});
	}

	function initializeAddressAutocomplete() {
		$('.delivium-address-field').each(function() {
			var input = this;
			var autocomplete = new google.maps.places.Autocomplete(input, {
				types: ['address']
			});

			autocomplete.addListener('place_changed', function() {
				var place = autocomplete.getPlace();
				if (!place.geometry) {
					return;
				}

				// Update hidden fields with coordinates if they exist
				var form = $(input).closest('form');
				form.find('.delivium-lat').val(place.geometry.location.lat());
				form.find('.delivium-lng').val(place.geometry.location.lng());
			});
		});
	}

	// Initialize tracking functionality
	function initTracking() {
		const trackingContainer = $('.delivium-tracking-container');
		if (!trackingContainer.length) return;

		const orderId = trackingContainer.data('order-id');
		if (!orderId) return;

		// Initialize map if enabled
		if (trackingContainer.data('show-map') === 'yes') {
			initMap(trackingContainer);
		}

		// Start tracking updates
		if (deliviumPublic.tracking_enabled) {
			updateTrackingInfo();
			setInterval(updateTrackingInfo, deliviumPublic.tracking_interval * 1000);
		}
	}

	// Initialize map
	function initMap(container) {
		const mapContainer = container.find('.delivium-map');
		if (!mapContainer.length) return;

		// Map initialization code will be added in premium version
		if (deliviumPublic.is_premium) {
			// Premium map features are loaded from delivium-public-premium.js
			return;
		}

		// Basic map for non-premium version
		mapContainer.html('<div class="delivium-map-placeholder">' +
			'<p>' + deliviumPublic.i18n.basic_map_notice + '</p>' +
			'</div>');
	}

	// Update tracking information
	function updateTrackingInfo() {
		const container = $('.delivium-tracking-container');
		const orderId = container.data('order-id');

		$.ajax({
			url: deliviumPublic.ajax_url,
			method: 'POST',
			data: {
				action: 'delivium_get_tracking',
				nonce: deliviumPublic.nonce,
				order_id: orderId
			},
			success: function(response) {
				if (response.success) {
					updateTrackingDisplay(response.data);
				}
			},
			error: function() {
				console.error(deliviumPublic.i18n.tracking_error);
			}
		});
	}

	// Update tracking display
	function updateTrackingDisplay(data) {
		const container = $('.delivium-tracking-container');
		
		// Update status
		container.find('.delivium-status').text(data.status);
		
		// Update estimated delivery time
		if (data.estimated_time) {
			container.find('.delivium-estimated-time').text(data.estimated_time);
		}

		// Update driver info if available
		if (data.driver_info) {
			const driverInfo = container.find('.delivium-driver-info');
			driverInfo.find('.driver-name').text(data.driver_info.name);
			driverInfo.find('.driver-phone').text(data.driver_info.phone);
			driverInfo.removeClass('hidden');
		}

		// Update location on map if premium
		if (deliviumPublic.is_premium && data.location) {
			// Premium map update is handled in delivium-public-premium.js
		}
	}

	// Initialize delivery completion
	function initDeliveryCompletion() {
		$('.delivium-complete-delivery').on('click', function(e) {
			e.preventDefault();
			
			if (!confirm(deliviumPublic.i18n.confirm_delivery)) {
				return;
			}

			const button = $(this);
			const orderId = button.data('order-id');

			$.ajax({
				url: deliviumPublic.ajax_url,
				method: 'POST',
				data: {
					action: 'delivium_complete_delivery',
					nonce: deliviumPublic.nonce,
					order_id: orderId
				},
				beforeSend: function() {
					button.prop('disabled', true);
				},
				success: function(response) {
					if (response.success) {
						alert(deliviumPublic.i18n.delivery_complete);
						location.reload();
					} else {
						alert(response.data.message);
					}
				},
				error: function() {
					alert(deliviumPublic.i18n.tracking_error);
				},
				complete: function() {
					button.prop('disabled', false);
				}
			});
		});
	}

	// Initialize rating submission
	function initRatingSubmission() {
		$('.delivium-rating-form').on('submit', function(e) {
			e.preventDefault();

			const form = $(this);
			const deliveryId = form.data('delivery-id');
			const rating = form.find('input[name="rating"]:checked').val();
			const comment = form.find('textarea[name="comment"]').val();

			$.ajax({
				url: deliviumPublic.ajax_url,
				method: 'POST',
				data: {
					action: 'delivium_submit_rating',
					nonce: deliviumPublic.nonce,
					delivery_id: deliveryId,
					rating: rating,
					comment: comment
				},
				beforeSend: function() {
					form.find('button[type="submit"]').prop('disabled', true);
				},
				success: function(response) {
					if (response.success) {
						form.html('<p class="delivium-rating-success">' + 
							deliviumPublic.i18n.rating_success + '</p>');
					} else {
						alert(response.data.message);
					}
				},
				error: function() {
					alert(deliviumPublic.i18n.rating_error);
				},
				complete: function() {
					form.find('button[type="submit"]').prop('disabled', false);
				}
			});
		});
	}

	function initializeDriverPortal() {
		// Update orders list every 30 seconds
		setInterval(updateDriverOrders, 30000);

		// Update earnings in real-time when order status changes
		$(document).on('deliveryCompleted', updateDriverEarnings);

		// Initialize location tracking if enabled
		if (deliviumPublic.location_tracking_enabled) {
			initializeLocationTracking();
		}
	}

	function updateDriverOrders() {
		$.ajax({
			url: deliviumPublic.ajax_url,
			type: 'POST',
			data: {
				action: 'delivium_get_driver_orders',
				nonce: deliviumPublic.nonce
			},
			success: function(response) {
				if (response.success) {
					$('.delivium-orders tbody').html(response.data.orders_html);
					updateDashboardStats(response.data.stats);
				}
			}
		});
	}

	function updateDriverEarnings() {
		$.ajax({
			url: deliviumPublic.ajax_url,
			type: 'POST',
			data: {
				action: 'delivium_get_driver_earnings',
				nonce: deliviumPublic.nonce
			},
			success: function(response) {
				if (response.success) {
					$('.earnings-summary').html(response.data.summary_html);
					$('.earnings-history tbody').html(response.data.history_html);
				}
			}
		});
	}

	function updateDriverStatus(status) {
		$.ajax({
			url: deliviumPublic.ajax_url,
			type: 'POST',
			data: {
				action: 'delivium_update_driver_status',
				status: status,
				nonce: deliviumPublic.nonce
			},
			success: function(response) {
				if (response.success) {
					// Update UI to reflect new status
					$('.driver-status-indicator')
						.removeClass('status-online status-offline')
						.addClass('status-' + status)
						.text(status);
				}
			}
		});
	}

	function handleDeliveryAction(orderId, action) {
		$.ajax({
			url: deliviumPublic.ajax_url,
			type: 'POST',
			data: {
				action: 'delivium_update_delivery_status',
				order_id: orderId,
				status: action,
				nonce: deliviumPublic.nonce
			},
			success: function(response) {
				if (response.success) {
					// Update order list
					updateDriverOrders();
					
					// Trigger earnings update if delivery completed
					if (action === 'complete') {
						$(document).trigger('deliveryCompleted');
					}
				}
			}
		});
	}

	function initializeLocationTracking() {
		if ("geolocation" in navigator) {
			// Update location every minute
			setInterval(updateDriverLocation, 60000);
			
			// Initial location update
			updateDriverLocation();
		}
	}

	function updateDriverLocation() {
		navigator.geolocation.getCurrentPosition(function(position) {
			$.ajax({
				url: deliviumPublic.ajax_url,
				type: 'POST',
				data: {
					action: 'delivium_update_driver_location',
					latitude: position.coords.latitude,
					longitude: position.coords.longitude,
					nonce: deliviumPublic.nonce
				}
			});
		});
	}

	function initializeOrderTracking() {
		const container = $('.delivium-order-tracking');
		const orderId = container.data('order-id');
		
		if (!orderId) return;

		// Update tracking info every 30 seconds
		setInterval(function() {
			updateOrderTracking(orderId);
		}, 30000);

		// Initial update
		updateOrderTracking(orderId);
	}

	function updateOrderTracking(orderId) {
		$.ajax({
			url: deliviumPublic.ajax_url,
			type: 'POST',
			data: {
				action: 'delivium_get_order_tracking',
				order_id: orderId,
				nonce: deliviumPublic.nonce
			},
			success: function(response) {
				if (response.success) {
					updateTrackingDisplay(response.data);
				}
			}
		});
	}

	// Map functionality
	let deliviumMap = null;
	let deliveryMarker = null;

	function initializeTrackingMap() {
		const mapElement = document.getElementById('delivium-tracking-map');
		if (!mapElement) return;

		const lat = parseFloat(mapElement.dataset.lat) || 0;
		const lng = parseFloat(mapElement.dataset.lng) || 0;

		deliviumMap = new google.maps.Map(mapElement, {
			center: { lat, lng },
			zoom: 15,
			styles: deliviumPublic.map_styles || []
		});

		deliveryMarker = new google.maps.Marker({
			position: { lat, lng },
			map: deliviumMap,
			icon: deliviumPublic.delivery_marker || null
		});
	}

	function updateDeliveryMarker(location) {
		if (!deliveryMarker || !location.lat || !location.lng) return;

		const position = new google.maps.LatLng(location.lat, location.lng);
		deliveryMarker.setPosition(position);
		deliviumMap.panTo(position);
	}

})( jQuery );