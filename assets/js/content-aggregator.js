import jQuery from 'jquery';
import { defaultI18n } from '@wordpress/i18n';
import '../scss/content-aggregator.scss';

jQuery(document).ready(function ($) {
	function split(val) {
		return val.split(/ \s*/);
	}
	function extractLast(term) {
		return split(term).pop();
	}
	$('#categories').select2({
		width: '95%',
		multiple: true,
		placeholder: defaultI18n.__('Search categories', 'content-aggregator'),
		allowClear: true,
	});
	$('input[data-tags],textarea[data-tags]').each(function () {
		const tags = $(this).data('tags');
		$(this)
			.on('keydown', function (event) {
				if (
					event.key === 'Tab' &&
					$(this).autocomplete('instance').menu.active
				) {
					event.preventDefault();
				}
			})
			.autocomplete({
				minLength: 0,
				source(request, response) {
					response(
						$.ui.autocomplete.filter(
							tags,
							extractLast(request.term)
						)
					);
				},
				focus() {
					return false;
				},
				select(event, ui) {
					const terms = split(this.value);
					terms.pop();
					terms.push(ui.item.value);
					terms.push('');
					this.value = terms.join(' ');
					return false;
				},
			});
	});
	const { wp } = window;
	let frame;
	$('.content-aggregator-image-selector .select-image').on(
		'click',
		function (e) {
			e.preventDefault();
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media({
				title: defaultI18n.__('Select', 'content-aggregator'),
				button: {
					text: defaultI18n.__('Select', 'content-aggregator'),
				},
				multiple: false,
			});
			frame.on('select', function () {
				const attachment = frame
					.state()
					.get('selection')
					.first()
					.toJSON();
				$('#image-preview').html(
					'<img src="' +
						attachment.url +
						'" style="max-width:150px;">'
				);
				$(
					'.content-aggregator-image-selector input[type="hidden"]'
				).val(attachment.id);
			});
			frame.open();
		}
	);
	let timerAutoDetect = false;
	$('input[name="content_aggregator_source[url]"]').on(
		'change keyup blur input",',
		async (e) => {
			if (timerAutoDetect) {
				clearTimeout(timerAutoDetect);
			}
			timerAutoDetect = setTimeout(async () => {
				try {
					const response = await fetch(
						contentAggregator.ajax_url +
							'?action=content_aggregator&url=' +
							encodeURIComponent(e.target.value) +
							'&nonce=' +
							encodeURI(contentAggregator.ajax_nonce)
					);
					if (!response.ok) {
						return false;
					}
					const result = await response.json();
					if (result.success === 1) {
						$('select[name="content_aggregator_source[type]"]')
							.val(result.type)
							.attr('selected', 'selected');
						$(
							'input[name="content_aggregator_source[scrap_url]"]'
						).val(result.url);
					} else {
						// eslint-disable-next-line no-alert
						alert(
							defaultI18n.__(
								'Error, no source URL found.',
								'content-aggregator'
							)
						);
					}
				} catch (_) {
					// eslint-disable-next-line no-alert
					alert(
						defaultI18n.__(
							'Error, no source URL found.',
							'content-aggregator'
						)
					);
				}
			}, 300);
		}
	);
});
