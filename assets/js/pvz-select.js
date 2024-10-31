jQuery(function ($) {
	"use strict";
	if ($().select2) {
		$(document.body).on('update_checkout', function () {
			var $select = $('#pecom-pvz-code');
			if ($select.data('select2')) {
				$select.select2('destroy');
			}
		})

		$(document.body).on('updated_checkout', function () {
			var $select = $('#pecom-pvz-code');
			if (!$select.data('select2')) {
				$select.select2({
					language: {
						noResults: function () {
							return $select.data('noresults');
						}
					},
					matcher: function(params, data) {
						if ($.trim(params.term) === '') {
							return data;
						}

						if ($(data.element).data('searchTerm').toString().indexOf(params.term.toLowerCase()) > -1) {
							return data;
						}

						return null;
					}
				});
			}
		});
	}

	$('body').on('change', '#pecom-pvz-code', function () {
		var $shippingCountry = $('#shipping_country').val();
		var $billingCountry = $('#billing_country').val();

		if ($shippingCountry && $shippingCountry != 'RU' || $billingCountry && $billingCountry != 'RU') {
			$('body').trigger('update_checkout');
		}
	});

	if (typeof pecomYandexMap === "undefined") {
		$('#pecom-map-trigger').hide();
	}

	$('#pecom-yandex-map-backdrop').click(function () {
		$('#pecom-yandex-map-wrapper').css('display', 'none')
	})

	if (typeof ymaps !== "undefined") {
		ymaps.ready(initpecomYandexMap);
	}

	window.pecomYandexMap = window.pecomYandexObjectManager = window.pecomMapWrapper = null;

	window.pecomMapWrapper = $('#pecom-yandex-map-wrapper');

	$('body').on('click', '#pecom-map-trigger', function () {
		let selectedPointId = jQuery('#pecom-pvz-code').val() ? jQuery('#pecom-pvz-code').val().split('|')[0] : null;
		let selectedPoint = selectedPointId ? pecomYandexMapData.features.filter(point => point.id === selectedPointId)[0] : null;

		pecomMapWrapper.css('display', 'flex');
		pecomYandexMap.setCenter(selectedPoint.geometry.coordinates);
		pecomYandexObjectManager.removeAll();
		pecomYandexObjectManager.add(pecomYandexMapData);
		selectedPointId && pecomYandexObjectManager.objects.balloon.open(selectedPointId);

		return false;
	});

	window.pecomSetPvzFromBaloon = function (id) {
		console.log(id)
		$('#pecom-pvz-code').val(id).trigger('change');
		pecomMapWrapper.css('display', 'none');
	}

	function initpecomYandexMap() {
		$('#pecom-ekom-map-trigger').show();
		pecomYandexMap = new ymaps.Map("pecom-yandex-map", {
			center: [55.76, 37.64],
			zoom: 12,
			behaviors: ['default', 'scrollZoom'],
			controls: []
		});

		pecomYandexObjectManager = new ymaps.ObjectManager({
			clusterize: true
		});

		pecomYandexMap.geoObjects.add(pecomYandexObjectManager);
	}
});
