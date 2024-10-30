(function(global) {
	function ClipboardModule(configs) {
		const toastMessage = configs?.toast.message || 'Conteúdo Copiado com Sucesso!';
		const toastTime = configs?.toast.seconds || 3;
		const toastBackground = configs?.toast.background || '#333';
		const clipboardClass = configs?.class || 'clipboard';
		const elements = document.querySelectorAll('[data-clipboard], .' + clipboardClass);

		const styleSheet = document.createElement("style");
		styleSheet.innerText = `
		[data-clipboard], [data-clipboard] *, .clipboard_icon {
			cursor: pointer;
		}
		.clipboard_icon input {
			padding-right: 30px;
		}
		.clipboard_icon::after {
			font-size: 18px;
			color: #555;
			float: right;
			font-family: dashicons;
			content: '\\f105';
			width: 25px;
			margin-top: -25px;
		}`;
		document.head.appendChild(styleSheet);

		elements.forEach(element => {
			console.log(element.getAttribute('data-clipboard-icon'))
			if (element.tagName === 'INPUT') {
				element.parentNode.classList.add('clipboard_icon');
			} else if (element.getAttribute('data-clipboard-icon')) {
				element.classList.add('clipboard_icon');
			}

			element.addEventListener('click', () => {
				const clipboardText = element.getAttribute('data-clipboard');
				const textToCopy = clipboardText || (element.tagName.toLowerCase() === 'input' ? element.value : '');

				if (navigator.clipboard && navigator.clipboard.writeText) {
					// Usa a API Clipboard moderna
					navigator.clipboard.writeText(textToCopy).then(() => {
						showToast(toastMessage);
					}).catch(err => {
						console.error('Erro ao copiar texto: ', err);
					});
				} else {
					// Fallback para navegadores que não suportam navigator.clipboard
					const textarea = document.createElement('textarea');
					textarea.value = textToCopy;
					document.body.appendChild(textarea);
					textarea.select();
					try {
						document.execCommand('copy');
						showToast(toastMessage);
					} catch (err) {
						console.error('Erro ao copiar texto: ', err);
					}
					document.body.removeChild(textarea);
				}
			});
		});

		function showToast(message) {
			const toast = document.createElement('div');
			toast.textContent = message;
			toast.style.position = 'fixed';
			toast.style.bottom = '20px';
			toast.style.left = '50%';
			toast.style.transform = 'translateX(-50%)';
			toast.style.background = toastBackground;
			toast.style.color = '#fff';
			toast.style.padding = '10px 20px';
			toast.style.borderRadius = '4px';
			toast.style.opacity = '0';
			toast.style.transition = 'opacity 0.5s';

			document.body.appendChild(toast);

			setTimeout(() => {
				toast.style.opacity = '1';
			}, 0);

			setTimeout(() => {
				toast.style.opacity = '0';
				setTimeout(() => {
					document.body.removeChild(toast);
				}, 500);
			}, toastTime * 1000);
		}
	}

	global.ClipboardModule = ClipboardModule;
})(window);