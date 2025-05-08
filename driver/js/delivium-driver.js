/**
 * Driver mobile app functionality
 */
(function($) {
    'use strict';

    // Map instance
    let map = null;
    let markers = [];
    let currentLocationMarker = null;
    let directionsService = null;
    let directionsRenderer = null;
    let watchPositionId = null;

    // App state
    const state = {
        driverId: null,
        currentLocation: null,
        isOnline: false,
        selectedOrderId: null,
        isUpdating: false
    };

    /**
     * Initialize the driver app
     */
    function initDriverApp() {
        state.driverId = $('.delivium-driver-app').data('driver-id');
        state.isOnline = $('#status-toggle').is(':checked');

        initMap();
        initEventListeners();
        startLocationTracking();
    }

    /**
     * Initialize Google Maps
     */
    function initMap() {
        // Create map instance
        map = new google.maps.Map(document.getElementById('driver-map'), {
            zoom: 12,
            center: { lat: 0, lng: 0 },
            disableDefaultUI: true,
            zoomControl: true,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });

        // Initialize directions service
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true
        });

        // Create delivery markers
        createDeliveryMarkers();
    }

    /**
     * Create markers for all deliveries
     */
    function createDeliveryMarkers() {
        // Clear existing markers
        markers.forEach(marker => marker.setMap(null));
        markers = [];

        // Create new markers
        $('.delivery-item').each(function() {
            const $item = $(this);
            const lat = parseFloat($item.find('.btn-navigate').data('lat'));
            const lng = parseFloat($item.find('.btn-navigate').data('lng'));
            const orderId = $item.data('order-id');

            if (lat && lng) {
                const marker = new google.maps.Marker({
                    position: { lat, lng },
                    map: map,
                    title: `Order #${orderId}`,
                    icon: {
                        url: deliviumDriver.markerIcon,
                        scaledSize: new google.maps.Size(30, 30)
                    }
                });

                marker.addListener('click', () => {
                    showDeliveryDetails(orderId);
                });

                markers.push(marker);
            }
        });
    }

    /**
     * Start tracking driver's location
     */
    function startLocationTracking() {
        if ('geolocation' in navigator) {
            watchPositionId = navigator.geolocation.watchPosition(
                position => {
                    updateDriverLocation(position.coords);
                },
                error => {
                    console.error('Geolocation error:', error);
                    showNotification('error', deliviumDriver.i18n.locationError);
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 30000,
                    timeout: 27000
                }
            );
        }
    }

    /**
     * Update driver's location
     */
    function updateDriverLocation(coords) {
        state.currentLocation = {
            lat: coords.latitude,
            lng: coords.longitude,
            accuracy: coords.accuracy,
            speed: coords.speed,
            heading: coords.heading
        };

        // Update current location marker
        if (!currentLocationMarker) {
            currentLocationMarker = new google.maps.Marker({
                map: map,
                icon: {
                    url: deliviumDriver.driverIcon,
                    scaledSize: new google.maps.Size(32, 32)
                }
            });
        }

        const position = new google.maps.LatLng(coords.latitude, coords.longitude);
        currentLocationMarker.setPosition(position);

        // Center map if first location update
        if (!map.get('initialized')) {
            map.setCenter(position);
            map.set('initialized', true);
        }

        // Send location update to server
        if (state.isOnline) {
            $.ajax({
                url: deliviumDriver.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'delivium_update_driver_location',
                    nonce: deliviumDriver.nonce,
                    location: state.currentLocation
                }
            });
        }
    }

    /**
     * Show delivery details and route
     */
    function showDeliveryDetails(orderId) {
        const $item = $(`.delivery-item[data-order-id="${orderId}"]`);
        
        // Highlight selected delivery
        $('.delivery-item').removeClass('selected');
        $item.addClass('selected');

        // Calculate and display route if we have current location
        if (state.currentLocation) {
            const destination = {
                lat: parseFloat($item.find('.btn-navigate').data('lat')),
                lng: parseFloat($item.find('.btn-navigate').data('lng'))
            };

            calculateRoute(
                new google.maps.LatLng(state.currentLocation.lat, state.currentLocation.lng),
                new google.maps.LatLng(destination.lat, destination.lng)
            );
        }
    }

    /**
     * Calculate and display route
     */
    function calculateRoute(origin, destination) {
        directionsService.route(
            {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode.DRIVING
            },
            (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                } else {
                    showNotification('error', deliviumDriver.i18n.routeError);
                }
            }
        );
    }

    /**
     * Update delivery status
     */
    function updateDeliveryStatus(orderId, status, note) {
        if (state.isUpdating) return;

        state.isUpdating = true;
        $('.loading-overlay').show();

        $.ajax({
            url: deliviumDriver.ajaxUrl,
            method: 'POST',
            data: {
                action: 'delivium_update_delivery_status',
                nonce: deliviumDriver.nonce,
                order_id: orderId,
                status: status,
                note: note,
                location: state.currentLocation
            },
            success: response => {
                if (response.success) {
                    showNotification('success', response.data.message);
                    if (status === 'delivered' || status === 'failed') {
                        $(`.delivery-item[data-order-id="${orderId}"]`).fadeOut();
                    }
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: () => {
                showNotification('error', deliviumDriver.i18n.updateError);
            },
            complete: () => {
                state.isUpdating = false;
                $('.loading-overlay').hide();
                $('#status-modal').hide();
            }
        });
    }

    /**
     * Show notification
     */
    function showNotification(type, message) {
        const $notification = $('<div>')
            .addClass(`notification ${type}`)
            .text(message)
            .appendTo('body');

        setTimeout(() => {
            $notification.fadeOut(() => $notification.remove());
        }, 3000);
    }

    /**
     * Initialize event listeners
     */
    function initEventListeners() {
        // Status toggle
        $('#status-toggle').on('change', function() {
            state.isOnline = $(this).is(':checked');
            $('.status-label').text(state.isOnline ? deliviumDriver.i18n.online : deliviumDriver.i18n.offline);

            $.ajax({
                url: deliviumDriver.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'delivium_update_driver_status',
                    nonce: deliviumDriver.nonce,
                    status: state.isOnline ? 'online' : 'offline'
                }
            });
        });

        // Navigate button
        $('.btn-navigate').on('click', function() {
            const $item = $(this).closest('.delivery-item');
            showDeliveryDetails($item.data('order-id'));
        });

        // Update status button
        $('.btn-update-status').on('click', function() {
            const $item = $(this).closest('.delivery-item');
            state.selectedOrderId = $item.data('order-id');
            $('#status-order-id').val(state.selectedOrderId);
            $('#status-modal').show();
        });

        // Status modal
        $('#status-update-form').on('submit', function(e) {
            e.preventDefault();
            updateDeliveryStatus(
                state.selectedOrderId,
                $('#delivery-status').val(),
                $('#status-note').val()
            );
        });

        $('.btn-cancel, .modal').on('click', function(e) {
            if (e.target === this) {
                $('#status-modal').hide();
            }
        });

        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                navigator.geolocation.clearWatch(watchPositionId);
            } else {
                startLocationTracking();
            }
        });
    }

    // Initialize when document is ready
    $(document).ready(initDriverApp);

})(jQuery); 