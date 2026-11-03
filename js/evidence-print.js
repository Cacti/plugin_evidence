/* Build a print snapshot without fetching or rescanning evidence data. */
function evidenceCreatePrintContent(source, checkboxes) {
	const content = source.cloneNode(true);
	content.removeAttribute('id');
	checkboxes.forEach(function(checkbox) {
		const key = checkbox.id.substring(3);
		content.querySelectorAll('.paragraph_' + key).forEach(function(section) {
			if (checkbox.checked) {
				section.removeAttribute('style');
			} else {
				section.remove();
			}
		});
	});
	// Expansion checkboxes affect the screen only; every date is printed.
	content.querySelectorAll('dd').forEach(function(date) {
		date.removeAttribute('style');
	});
	// OIDs normally shown on hover must also be readable on paper.
	content.querySelectorAll('.cactiTooltipHint').forEach(function(hint) {
		hint.replaceWith(document.createTextNode(' (' + hint.textContent.trim() + ') '));
	});
	content.querySelectorAll('script, style, .noprint, input, button, select, textarea').forEach(function(element) {
		element.remove();
	});
	content.querySelectorAll('a').forEach(function(link) {
		const url = new URL(link.getAttribute('href') || '', document.baseURI);
		if (url.searchParams.get('actual') === '1') {
			link.remove();
		} else {
			link.href = url.href;
		}
	});
	return content;
}

function evidenceOpenPrintView(button) {
	const source = document.getElementById('evidence_results');
	if (!source) {
		return;
	}
	const content = evidenceCreatePrintContent(source, Array.from(document.querySelectorAll('[data-evidence-section]')));
	const printWindow = window.open('', '_blank');
	if (!printWindow) {
		window.alert(button.dataset.popupMessage);
		return;
	}
	printWindow.opener = null;
	const doc = printWindow.document;
	doc.documentElement.lang = document.documentElement.lang;
	doc.title = button.dataset.printTitle;
	const stylesheet = doc.createElement('link');
	stylesheet.rel = 'stylesheet';
	stylesheet.href = new URL(button.dataset.printCss, document.baseURI).href;
	doc.head.appendChild(stylesheet);
	const toolbar = doc.createElement('div');
	toolbar.className = 'evidence-print-toolbar';
	const printButton = doc.createElement('button');
	printButton.type = 'button';
	printButton.textContent = button.dataset.printLabel;
	printButton.addEventListener('click', function() { printWindow.print(); });
	toolbar.appendChild(printButton);
	doc.body.appendChild(toolbar);
	const heading = doc.createElement('h1');
	heading.textContent = button.dataset.printTitle;
	doc.body.appendChild(heading);
	doc.body.appendChild(doc.importNode(content, true));
}

$(function() {
	$('#evidence_export').off('click.evidencePrint').on('click.evidencePrint', function() {
		evidenceOpenPrintView(this);
	});
});
