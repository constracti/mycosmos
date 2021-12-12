function ajax_success(data) {
	const node = this;
	if (typeof(data) !== 'object')
		return alert(data);
	if ('redirect' in data) {
		if (data.redirect !== null)
			document.location.href = data.redirect;
		else
			document.location.reload();
	}
}

$(document).on('click', '.ajax-link', function(event) {
	event.preventDefault();
	const link = $(this);
	if (link.data('ajax-busy') === 'on')
		return false;
	if (link.data('ajax-confirm') !== undefined && !confirm(link.data('ajax-confirm')))
		return false;
	link.addClass('w3-disabled');
	const btn_icon = link.find('span.fa-fw');
	const btn_icon_class = btn_icon.prop('class');
	btn_icon.prop('class', 'fas fa-fw fa-spinner fa-pulse');
	$.post(link.prop('href')).done(function(data) {
		ajax_success.call(link, data);
	}).fail(function(jqXHR) {
		alert(jqXHR.statusText + ' ' + jqXHR.status);
	}).always(function() {
		btn_icon.prop('class', btn_icon_class);
		link.removeClass('w3-disabled');
		link.data('ajax-busy', 'off');
	});
});

$(document).on('submit', '.ajax-form', function(event) {
	event.preventDefault();
	const form = $(this);
	if (form.data('ajax-busy') === 'on')
		return false;
	if (form.data('ajax-confirm') !== undefined && !confirm(form.data('ajax-confirm')))
		return false;
	const btn = form.find('button[type="submit"]');
	btn.addClass('w3-disabled');
	const btn_icon = btn.find('span.fa-fw');
	const btn_icon_class = btn_icon.prop('class');
	btn_icon.prop('class', 'fas fa-fw fa-spinner fa-pulse');
	$.post(form.prop('action'), form.serialize()).done(function(data) {
		ajax_success.call(form, data);
	}).fail(function(jqXHR) {
		alert(jqXHR.statusText + ' ' + jqXHR.status);
	}).always(function() {
		btn_icon.prop('class', btn_icon_class);
		btn.removeClass('w3-disabled');
		form.data('ajax-busy', 'off');
	});
});
