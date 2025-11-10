var show_expand = false;
var show_expand_latest = false;


$(function() {

	// used in host.php
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

	// in tab
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

	// open date
	$('dd').hide();
	$('dt').click(function () {
        	$(this).next('dd').slideToggle(250);
	});

	// expand/hide all dates
	$('#ch_expand').off('click').on('click', function() {
		if (show_expand) {
			show_expand = false;
			$('dd').hide();
		} else {
			show_expand = true;
			$('dd').slideToggle(250);
		}
	});

	// expand/hide latest date
	$('#ch_expand_latest').off('click').on('click', function() {
			$('.latest').click();
	});

	// display/hide paragraphs

	$('#ch_info').off('click').on('click', function() {
		if ($('.paragraph_info').css('display') == 'block') {
			$('.paragraph_info').css('display','none');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=info&state=false', function() {});
		} else {
			$('.paragraph_info').css('display','block');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=info&state=true', function() {});
		}
	});

	$('#ch_entity').off('click').on('click', function() {
		if ($('.paragraph_entity').css('display') == 'block') {
			$('.paragraph_entity').css('display','none');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=entity&state=false', function() {});
		} else {
			$('.paragraph_entity').css('display','block');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=entity&state=true', function() {});
		}
	});

	$('#ch_mac').off('click').on('click', function() {
		if ($('.paragraph_mac').css('display') == 'block') {
			$('.paragraph_mac').css('display','none');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=mac&state=false', function() {});
		} else {
			$('.paragraph_mac').css('display','block');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=mac&state=true', function() {});
		}
	});

	$('#ch_ip').off('click').on('click', function() {
		if ($('.paragraph_ip').css('display') == 'block') {
			$('.paragraph_ip').css('display','none');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=ip&state=false', function() {});
		} else {
			$('.paragraph_ip').css('display','block');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=ip&state=true', function() {});
		}
	});

	$('#ch_spec').off('click').on('click', function() {
		if ($('.paragraph_spec').css('display') == 'block') {
			$('.paragraph_spec').css('display','none');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=spec&state=false', function() {});
		} else {
			$('.paragraph_spec').css('display','block');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=spec&state=true', function() {});
		}
	});

	$('#ch_opt').off('click').on('click', function() {
		if ($('.paragraph_opt').css('display') == 'block') {
			$('.paragraph_opt').css('display','none');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=opt&state=false', function() {});
		} else {
			$('.paragraph_opt').css('display','block');
			$.get(urlPath+'plugins/evidence/evidence_tab.php?action=setting&what=opt&state=true', function() {});
		}
	});
});


// used in evidence_tab.php
function applyFilter() {
	strURL  = 'evidence_tab.php' +
		'?host_id=' + $('#host_id').val() +
		'&template_id=' + $('#template_id').val() +
		'&scan_date=' + $('#scan_date').val() +
		'&find_text=' + $('#find_text').val() +
		'&header=false&action=find';
	loadPageNoHeader(strURL);
}

function clearFilter() {
	strURL = 'evidence_tab.php?clear=1&header=false';
	loadPageNoHeader(strURL);
}
