var show_entity = true;
var show_mac = true;
var show_ip = true;
var show_specific = true;
var show_optional = true;
var show_expand = false;


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

	// display/hide paragraphs
	$('#ch_entity').off('click').on('click', function() {
		if (show_entity) {
			show_entity = false;
			$('.paragraph_entity').css('display','none');
		} else {
			show_entity = true;
			$('.paragraph_entity').css('display','inline-block');
		}
	});

	$('#ch_mac').off('click').on('click', function() {
		if (show_mac) {
			show_mac = false;
			$('.paragraph_mac').css('display','none');
		} else {
			show_mac = true;
			$('.paragraph_mac').css('display','inline-block');
		}
	});

	$('#ch_ip').off('click').on('click', function() {
		if (show_ip) {
			show_ip = false;
			$('.paragraph_ip').css('display','none');
		} else {
			show_ip = true;
			$('.paragraph_ip').css('display','inline-block');
		}
	});

	$('#ch_specific').off('click').on('click', function() {
		if (show_specific) {
			show_specific = false;
			$('.paragraph_specific').css('display','none');
		} else {
			show_optional = true;
			$('.paragraph_specific').css('display','inline-block');
		}
	});

	$('#ch_optional').off('click').on('click', function() {
		if (show_optional) {
			show_optional = false;
			$('.paragraph_optional').css('display','none');
		} else {
			show_optional = true;
			$('.paragraph_optional').css('display','inline-block');
		}
	});
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
