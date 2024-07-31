
// used in host.php
$(function() {

	$('#evidence_info').click(function(event) {

		event.preventDefault();
		$.get(urlPath+'plugins/evidence/evidence.php?host_id='+$('#evidence_info').data('evidence_id'))
			.done(function(data) {
			$('#ping_results').html(data);
			hostInfoHeight = $('.hostInfoHeader').height();
		})
		.fail(function(data) {
			getPresentHTTPError(data);
		});
	})
});


// used in evidence_tab.php
function applyFilter() {
	strURL  = 'evidence_tab.php' +
		'?host_id=' + $('#host_id').val() +
		'&header=false';
	loadPageNoHeader(strURL);
}

function clearFilter() {
	strURL = 'evidence_tab.php?clear=1&header=false';
	loadPageNoHeader(strURL);
}

$(function() {
	$('#clear').unbind().on('click', function() {
		clearFilter();
	});

	$('#filter').unbind().on('change', function() {
		applyFilter();
	});

	$('#form_evidence').unbind().on('submit', function(event) {
		event.preventDefault();
		applyFilter();
	});

	$('dd').hide();
	$('dd:first').slideToggle(); // open first line automatically
	$('dt').click(function () {
        	$(this).next('dd').slideToggle(250);
	});

});


