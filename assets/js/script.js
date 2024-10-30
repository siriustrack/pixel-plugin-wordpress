jQuery('.url-validate').blur(function() {
	if (!/^http:\/\//.test(this.value) && !/^https:\/\//.test(this.value)) {
		this.value = "https://" + this.value;
	}
});

document.addEventListener('DOMContentLoaded', async () => {
	document.querySelectorAll('.tom-select')
		.forEach(field => {
			if (field.tagName === 'SELECT' || field.tagName === 'INPUT') {
				new TomSelect(field);
			} else {
				// Busque pelo primeiro filho que seja <select> ou <input>
				const inputOrSelect = field.querySelector('select, input');
				if (inputOrSelect) {
					new TomSelect(inputOrSelect);
				}
			}
		});
});

function sweet_alert(text, link, label_confirm = "Confirmar") {
	Swal.fire({
		text: text,
		showCancelButton: true,
		confirmButtonText: label_confirm,
	}).then((result) => {
		if (result.isConfirmed) {
			window.location.replace(link);
		}
	});
}