(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * @link       https://delivium.top
	 * @since      1.0.0
	 *
	 * @package    Delivium
	 * @subpackage Delivium/admin/js
	 */

	$(document).ready(function() {
		// Handle application form submission
		$('#delivium-application-form').on('submit', function(e) {
			e.preventDefault();
			
			const formData = $(this).serialize();
			
			$.ajax({
				url: delivium_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'process_application',
					nonce: delivium_admin.nonce,
					formData: formData
				},
				success: function(response) {
					if (response.success) {
						alert('Application processed successfully!');
						location.reload();
					} else {
						alert('Error processing application: ' + response.data.message);
					}
				},
				error: function() {
					alert('Server error occurred. Please try again.');
				}
			});
		});

		// Handle report generation
		$('.delivium-generate-report').on('click', function(e) {
			e.preventDefault();
			
			const reportType = $(this).data('report-type');
			
			$.ajax({
				url: delivium_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'generate_report',
					nonce: delivium_admin.nonce,
					reportType: reportType
				},
				success: function(response) {
					if (response.success) {
						window.location.href = response.data.downloadUrl;
					} else {
						alert('Error generating report: ' + response.data.message);
					}
				},
				error: function() {
					alert('Server error occurred. Please try again.');
				}
			});
		});

		// Handle settings updates
		$('#delivium-settings-form').on('submit', function(e) {
			e.preventDefault();
			
			const settings = $(this).serialize();
			
			$.ajax({
				url: delivium_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'update_settings',
					nonce: delivium_admin.nonce,
					settings: settings
				},
				success: function(response) {
					if (response.success) {
						alert('Settings updated successfully!');
					} else {
						alert('Error updating settings: ' + response.data.message);
					}
				},
				error: function() {
					alert('Server error occurred. Please try again.');
				}
			});
		});

		// Initialize datepickers
		$('.delivium-datepicker').datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true
		});

		// Initialize tooltips
		$('.delivium-tooltip').tooltip();

		// Initialize color picker if it exists
		if ($.fn.wpColorPicker) {
			$('.delivium-color-picker').wpColorPicker();
		}

		// Handle media uploader
		var mediaUploader;
		$('.delivium-upload-button').on('click', function(e) {
			e.preventDefault();
			var button = $(this);
			var imagePreview = button.siblings('.delivium-image-preview');
			var imageInput = button.siblings('.delivium-image-input');

			if (mediaUploader) {
				mediaUploader.open();
				return;
			}

			mediaUploader = wp.media({
				title: 'Choose Image',
				button: {
					text: 'Use this image'
				},
				multiple: false
			});

			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				imagePreview.attr('src', attachment.url);
				imageInput.val(attachment.url);
			});

			mediaUploader.open();
		});

		// Handle AJAX form submissions
		$('.delivium-ajax-form').on('submit', function(e) {
			e.preventDefault();
			var form = $(this);
			var submitButton = form.find('input[type="submit"]');
			var messageContainer = form.find('.delivium-message');

			$.ajax({
				url: delivium_ajax.ajaxurl,
				type: 'POST',
				data: form.serialize() + '&delivium_wpnonce=' + delivium_nonce.nonce,
				beforeSend: function() {
					submitButton.prop('disabled', true);
					messageContainer.html('').hide();
				},
				success: function(response) {
					try {
						var data = JSON.parse(response);
						if (data.result === '1') {
							messageContainer.html(data.error).addClass('updated').removeClass('error').show();
							if (data.redirect) {
								window.location.href = data.redirect;
							}
						} else {
							messageContainer.html(data.error).addClass('error').removeClass('updated').show();
						}
					} catch(e) {
						messageContainer.html(response).show();
					}
				},
				error: function() {
					messageContainer.html('An error occurred. Please try again.').addClass('error').show();
				},
				complete: function() {
					submitButton.prop('disabled', false);
				}
			});
		});

		// Handle bulk actions
		$('.delivium-bulk-action-form').on('submit', function(e) {
			var form = $(this);
			var action = form.find('select[name="action"]').val();
			var checked = form.find('input[name="bulk-items[]"]:checked');

			if (action === '-1' || checked.length === 0) {
				e.preventDefault();
				alert('Please select both an action and at least one item.');
			}
		});

		// Toggle all checkboxes
		$('.delivium-toggle-all').on('change', function() {
			var checked = $(this).prop('checked');
			$('.delivium-bulk-item').prop('checked', checked);
		});

		// Analytics Page Functions
		const DeliviumAnalytics = {
			init: function() {
				this.initDateRangePicker();
				this.initTooltips();
				this.setupChartRefresh();
			},

			initDateRangePicker: function() {
				const startDate = $('#start_date');
				const endDate = $('#end_date');

				if (startDate.length && endDate.length) {
					startDate.on('change', function() {
						endDate.attr('min', $(this).val());
					});

					endDate.on('change', function() {
						startDate.attr('max', $(this).val());
					});
				}
			},

			setupChartRefresh: function() {
				$('.delivium-analytics-charts').on('resize', function() {
					if (window.deliviumCharts) {
						window.deliviumCharts.forEach(chart => chart.resize());
					}
				});
			}
		};

		// Time Slots Management
		const DeliviumTimeSlots = {
			init: function() {
				this.initSlotActions();
				this.initDefaultSlots();
			},

			initSlotActions: function() {
				// Add new time slot
				$('.add-slot').on('click', function(e) {
					e.preventDefault();
					const template = $('.slot-row').first().clone();
					template.find('input').val('');
					$('#default-slots').append(template);
				});

				// Remove time slot
				$('#default-slots').on('click', '.remove-slot', function(e) {
					e.preventDefault();
					if ($('.slot-row').length > 1) {
						$(this).closest('.slot-row').remove();
					}
				});

				// Time slot validation
				$('#default-slots').on('change', 'input[type="time"]', function() {
					const row = $(this).closest('.slot-row');
					const start = row.find('input[name="slot_start[]"]').val();
					const end = row.find('input[name="slot_end[]"]').val();

					if (start && end && start >= end) {
						alert(delivium_admin.i18n.invalid_time_range);
						$(this).val('');
					}
				});
			},

			initDefaultSlots: function() {
				// Save default slots
				$('form').on('submit', function(e) {
					let isValid = true;
					$('.slot-row').each(function() {
						const start = $(this).find('input[name="slot_start[]"]').val();
						const end = $(this).find('input[name="slot_end[]"]').val();
						const capacity = $(this).find('input[name="slot_capacity[]"]').val();

						if (!start || !end || !capacity) {
							isValid = false;
							return false;
						}
					});

					if (!isValid) {
						e.preventDefault();
						alert(delivium_admin.i18n.fill_all_fields);
					}
				});
			}
		};

		// Delivery Zones Management
		const DeliviumZones = {
			map: null,
			drawingManager: null,
			selectedShape: null,

			init: function() {
				this.initMap();
				this.initZoneActions();
			},

			initMap: function() {
				const mapElement = document.getElementById('zone_map');
				if (!mapElement) return;

				// Initialize Google Maps
				this.map = new google.maps.Map(mapElement, {
					zoom: 13,
					center: { lat: parseFloat(delivium_admin.default_lat), lng: parseFloat(delivium_admin.default_lng) }
				});

				// Initialize drawing manager
				this.drawingManager = new google.maps.drawing.DrawingManager({
					drawingMode: google.maps.drawing.OverlayType.POLYGON,
					drawingControl: true,
					drawingControlOptions: {
						position: google.maps.ControlPosition.TOP_CENTER,
						drawingModes: ['polygon']
					},
					polygonOptions: {
						editable: true,
						draggable: true
					}
				});

				this.drawingManager.setMap(this.map);

				// Handle polygon complete
				google.maps.event.addListener(this.drawingManager, 'polygoncomplete', (polygon) => {
					this.setSelection(polygon);
					this.updateZoneCoverage(polygon);
				});
			},

			setSelection: function(shape) {
				if (this.selectedShape) {
					this.selectedShape.setMap(null);
				}
				this.selectedShape = shape;
			},

			updateZoneCoverage: function(polygon) {
				const coordinates = polygon.getPath().getArray();
				const coverage = coordinates.map(coord => {
					return coord.lat() + ',' + coord.lng();
				}).join('|');
				$('#zone_coverage').val(coverage);
			},

			initZoneActions: function() {
				// Delete zone confirmation
				$('.delete').on('click', function(e) {
					if (!confirm(delivium_admin.i18n.confirm_delete)) {
						e.preventDefault();
					}
				});

				// Zone form validation
				$('form').on('submit', function(e) {
					const coverage = $('#zone_coverage').val();
					if (!coverage) {
						e.preventDefault();
						alert(delivium_admin.i18n.draw_zone);
					}
				});
			}
		};

		// Common Functions
		const DeliviumCommon = {
			init: function() {
				this.initTooltips();
				this.initDeleteConfirmations();
			},

			initTooltips: function() {
				$('.delivium-tooltip').each(function() {
					const $this = $(this);
					if ($this.data('tooltip')) {
						$this.attr('title', $this.data('tooltip'));
					}
				});
			},

			initDeleteConfirmations: function() {
				$('.delete').on('click', function(e) {
					if (!confirm(delivium_admin.i18n.confirm_delete)) {
						e.preventDefault();
					}
				});
			}
		};

		// Initialize on document ready
		DeliviumCommon.init();

		// Initialize page-specific functionality
		if ($('.delivium-analytics-cards').length) {
			DeliviumAnalytics.init();
		}

		if ($('.delivium-calendar-view').length || $('.delivium-list-view').length) {
			DeliviumTimeSlots.init();
		}

		if ($('.delivium-zone-form').length) {
			DeliviumZones.init();
		}
	});

})( jQuery ); 