jQuery(document).ready(function($) {
	// Add syntax highlighting.
	$('pre code').each(function(i, e) {
		hljs.highlightBlock(e)
	});

	$('#wpapcc-tabs a').click(function() {
		$('#wpapcc-tabs a').removeClass('nav-tab-active');
		$('.wpapcc-tab').removeClass('active');

		var id = $(this).attr('id').replace('-tab','');
		$('#' + id).addClass('active');
		$(this).addClass('nav-tab-active');
	});

	// init
	var active_tab = window.location.hash.replace('#top#','');

	// Set first tab to active on page load.
	if ( active_tab == '' || active_tab == '#_=_') {
		active_tab = $('.wpapcc-tab').attr('id');
	}

	$('#' + active_tab).addClass('active');
	$('#' + active_tab + '-tab').addClass('nav-tab-active');

});
