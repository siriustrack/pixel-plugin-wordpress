const PixelXApp = class {

	constructor() {
		this.WEEK_DAYS = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
		this.MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
		this.data = {};
		let console_style;

		const console_msg = `
%cPixel X App
%cOtimizando o rastreamento das campanhas desse site!
%cAcesse https://pixelx.app/csl e conheça como podemos otimizar as suas campanhas também.`;

		if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
			console_style = [
				'font-size: 20px; font-weight: bold; color: #0047bb;',
				'font-size: 16px; color: #ee7b37;',
				'font-size: 14px; color: #f9f9f9;',
			];
		} else {
			console_style = [
				'font-size: 20px; font-weight: bold; color: #0047bb;',
				'font-size: 16px; color: #ee7b37;',
				'font-size: 14px; color: #101820;',
			];
		}

		console.log(console_msg, ...console_style);
	}

	async start(data) {
		this.data = data;

		// Variáveis do Sistema
		localStorage.setItem('pxa_domain', this.data.domain);
		this.data.page_url = window.location.href;

		if (!this.check_domain()) {
			return;
		}

		// Variáveis da Página
		this.data.page_title = document.title;
		this.data.traffic_source = document.referrer;
		this.data.utm_source = this.search_params_url_cookie('utm_source');
		this.data.utm_medium = this.search_params_url_cookie('utm_medium');
		this.data.utm_campaign = this.search_params_url_cookie('utm_campaign');
		this.data.utm_id = this.search_params_url_cookie('utm_id');
		this.data.utm_term = this.search_params_url_cookie('utm_term');
		this.data.utm_content = this.search_params_url_cookie('utm_content');
		this.data.src = this.search_params_url_cookie('src');
		this.data.sck = this.search_params_url_cookie('sck');

		// Variáveis do Evento
		const pxa_day_of_week = new Date().getDay();
		const pxa_month = new Date().getMonth();
		const pxa_hour_start = new Date().getHours();
		this.data.event_day_in_month = new Date().getDate();
		this.data.event_day = this.WEEK_DAYS[pxa_day_of_week];
		this.data.event_month = this.MONTHS[pxa_month];
		this.data.event_time_interval = `${pxa_hour_start}-${pxa_hour_start + 1}`;
		this.data.event_time_start = this.getTimestampUtc();

		// Variáveis do Lead
		this.data.lead_id = this.search_params_url_cookie('lead_id', 'external_id') || this.get('pxa_lead_id');

		this.data.lead_name = this.search_params_url_cookie('full_name', 'lead_name') || this.get('pxa_lead_name');
		this.data.lead_fname = this.search_params_url_cookie('fname', 'first-name', 'first_name', 'first name') || this.get('pxa_lead_fname');
		this.data.lead_lname = this.search_params_url_cookie('lname', 'last-name', 'last_name', 'last name') || this.get('pxa_lead_lname');
		this.data.lead_email = this.search_params_url_cookie('email', 'lead_email') || this.get('pxa_lead_email');
		this.data.lead_phone = this.search_params_url_cookie('phone', 'tel', 'whatsapp', 'ph', 'fone', 'lead_phone') || this.get('pxa_lead_phone');

		// Geo Location
		this.data.geolocation = await this.get_geolocation();
		this.data.user_agent = navigator.userAgent;

		// Variáveis do Facebook
		this.data.fb_fbc = this.search_params_url_cookie('_fbc', 'fbclid');
		if (this.data.fb_fbc != '' && !this.data.fb_fbc.includes('.')) {
			this.data.fb_fbc = 'fb.1.' + this.data.event_time_start + '.' + this.data.fb_fbc;
		}

		this.data.fb_fbp = this.search_params_url_cookie('_fbp', 'fbp')
			|| 'fb.1.' + this.data.event_time_start + '.' + this.randomInt(1000000000, 9999999999);

		// Send and Set Lead Data
		await this.send_lead_data();
		// const forms = document.querySelectorAll('form');
		// for (const form of forms) {
		// 	form.addEventListener('submit', async (event) => {
		// 		event.preventDefault();
		// 		await this.send_lead_data();
		// 	});
		// }

		// Monitor Submit Forms
		const monitorFormsAndMaskLoad = async () => {
			await this.monitor_forms();
			await this.mask_load();
			await this.mask_load_inter();
		};

		// Monitor Submit Forms e carregar máscara inicialmente
		await monitorFormsAndMaskLoad();
		setInterval(async () => {
			await this.monitor_forms();
		}, 2000);

		// Monitorar eventos de abertura de popup do Elementor
		await this.elementor_function(monitorFormsAndMaskLoad);

		// Monitorar cliques em links de ação do Elementor, Wix
		(document.querySelectorAll('a[href*="elementor-action"], .link_popup, a[aria-haspopup="true"]'))
			.forEach(el => {
				el.classList.add('pxa_button_form_monitor');
				el.addEventListener('click', async () => {
					setTimeout(async () => {
						await monitorFormsAndMaskLoad();
					}, 500);
				});
			});

		// Select all "a" and "button"
		(document.querySelectorAll('a, button'))
			.forEach(el => {
				if (el.classList.contains('pxa_button_form_monitor')) {
					return;
				}

				if (
					(el.tagName == 'A' && !el.getAttribute('href')?.toLowerCase()?.startsWith('http'))
					|| (el.tagName == 'BUTTON' && el?.type == 'button')
				) {
					el.classList.add('pxa_button_form_monitor');
					el.addEventListener('click', async () => {
						setTimeout(async () => {
							await monitorFormsAndMaskLoad();
						}, 500);
					});
				}
			});

		// Load
		await this.facebook_load_pixel();
	}

	async elementor_function(functionRun) {
		// Verifica se a função interna é uma função
		if (typeof functionRun === 'function') {
			if (window.jQuery) {
				setTimeout(() => {
					jQuery(document).on('elementor/popup/show', async () => {
						await functionRun();
					});
				}, 500);
			}

			document.addEventListener('elementor/popup/show', async () => {
				await functionRun();
			});
		}
	}


	/*
	 * Getters and Setters
	 */
	get(key) {
		// Verifica se o cookie existe
		const cookies = document.cookie.split(';');
		for (const cookie of cookies) {
			const [cookieKey, cookieValue] = cookie.trim().split('=');
			if (cookieKey === key) {
				return cookieValue;
			}
		}

		// Se não encontrar no cookie, tenta buscar no localStorage
		const localStorageValue = localStorage.getItem(key);
		return localStorageValue;
	}

	set(values) {
		const domain = this.get('pxa_domain');
		const expirationTime = 15552000 * 1000;
		const expires = new Date(Date.now() + expirationTime).toUTCString();

		// Set Cookies
		for (let key in values) {
			const value = values[key];
			if (value && value !== 'undefined') {
				// document.cookie = `${key}=${value}; expires=${expires}; path=/; domain=.${domain}`;
				document.cookie = `${key}=${value}; SameSite=None; Secure; expires=${expires}; path=/; domain=.${domain}`;
				localStorage.setItem(key, value);
			}
		}

		// Set DataLayer
		var dataLayer = window.dataLayer || [];
		dataLayer.push(values);
	}

	getTimestampUtc() {
		return Math.floor(Date.now() / 1000);
	}

	search_params_url_cookie(...params) {
		// Verifique a URL
		const urlSearchParams = new URLSearchParams(window.location.search);
		for (const param of params) {
			if (urlSearchParams.has(param)) {
				return urlSearchParams.get(param);
			}
		}

		// Verifique os Cookies
		for (const param of params) {
			const cookieName = param;
			const cookieValue = this.get(cookieName);
			if (cookieValue) {
				return cookieValue;
			}
		}

		// Se não encontrado em nenhum lugar, retorne em branco
		return '';
	}

	get_path(element) {
		let path = [];

		while (element) {
			path.push(element);
			element = element.parentElement;
		}

		return path;
	}

	/*
	 * Geo Location
	 */
	async get_ip() {
		// 		const ip = this.data.pxa_lead_ip;
		// 
		// 		if (ip) {
		// 			return ip;
		// 		}

		try {
			const response = await fetch('https://www.cloudflare.com/cdn-cgi/trace');
			const data = await response.text();
			const lines = data.split('\n');

			for (const line of lines) {
				const parts = line.split('=');
				if (parts[0] === 'ip') {

					this.set('pxa_lead_ip', parts[1]);

					return parts[1];
				}
			}

			return null;
		} catch (error) {
			// console.error('Erro ao obter o IP:', error);
			return null;
		}
	}

	async get_geolocation() {
		const ip = this.get('pxa_lead_ip') || await this.get_ip();
		let key_ip = 'pxa_geo_location';

		if (ip) {
			key_ip = 'pxa_geo_location_' + ip.replaceAll('.', '_').replaceAll(':', '_');
		}

		const geolocation = localStorage.getItem(key_ip)

		// Check localStorage first for cached data
		if (geolocation) {
			return JSON.parse(geolocation);
		}

		let response, values;

		if (ip) {
			response = await fetch('https://pro.ip-api.com/json/' + ip + '?key=TOLoWxdNIA0zIZm');
		} else {
			response = await fetch('https://pro.ip-api.com/json/?key=TOLoWxdNIA0zIZm');
		}

		// https://ipapi.co/json/
		// http://ip-api.com/json/- Sem moeda
		// https://ipwho.is/ - Sem moeda
		// https://freeipapi.com/api/json/ - Sem moeda
		// https://api.db-ip.com/v2/free/self

		if (response.ok) {
			response = await response.json();

			values = {
				pxa_lead_ip: response?.query,
				pxa_lead_city: response?.city?.toLowerCase(),
				pxa_lead_region: response?.regionName?.toLowerCase(),
				pxa_lead_region_code: response?.region?.toLowerCase(),
				pxa_lead_country: response?.country?.toLowerCase(),
				pxa_lead_country_code: response?.countryCode?.toLowerCase(),
				pxa_lead_currency: response?.currency,
				pxa_lead_zipcode: response?.zip,
			};
		} else {
			if (ip) {
				response = await fetch('https://ipapi.co/json/' + ip);
			} else {
				response = await fetch('https://ipapi.co/json/');
			}

			if (response.ok) {
				response = await response.json();

				values = {
					pxa_lead_ip: response?.ip,
					pxa_lead_city: response?.city?.toLowerCase(),
					pxa_lead_region: response?.region?.toLowerCase(),
					pxa_lead_region_code: response?.region_code?.toLowerCase(),
					pxa_lead_country: response?.country_name?.toLowerCase(),
					pxa_lead_country_code: response?.country_code?.toLowerCase(),
					pxa_lead_currency: response?.currency,
					pxa_lead_zipcode: response?.postal,
				};
			} else {
				if (ip) {
					response = await fetch('https://json.geoiplookup.io/' + ip);
				} else {
					response = await fetch('https://json.geoiplookup.io/');
				}

				if (response.ok) {
					response = await response.json();

					values = {
						pxa_lead_ip: response?.ip,
						pxa_lead_city: response?.city?.toLowerCase(),
						pxa_lead_region: response?.region?.toLowerCase(),
						// pxa_lead_region_code: response?.region_code?.toLowerCase(),
						pxa_lead_country: response?.country_name?.toLowerCase(),
						pxa_lead_country_code: response?.country_code?.toLowerCase(),
						pxa_lead_currency: response?.currency_code,
						pxa_lead_zipcode: response?.postal_code,
					};
				}
			}
		}

		this.set(this.remove_accents(values));
		localStorage.setItem(key_ip, JSON.stringify(values));

		return values;
	}

	remove_accents(object) {
		const newObject = {};

		for (const key in object) {
			const value = object[key];
			const newKey = key?.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

			if (typeof value === 'object') {
				newObject[newKey] = this.remove_accents(value);
			} else {
				newObject[newKey] = value?.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
			}
		}

		return newObject;
	}

	/*
	 * Forms
	 */
	async mask_load() {
		// Verifique se as configurações estão presentes
		if (!this.data?.phone_mask || !this.data.phone_mask_js) {
			return;
		}

		if (typeof IMask === 'undefined') {
			await this.load_script(this.data.phone_mask_js)
		}

		await this.mask();

	}

	async mask() {
		// Selecione elementos que precisam da máscara
		const elements = document.querySelectorAll(".pxa_mask_phone, #pxa_mask_phone");

		// Aplique a máscara nos elementos encontrados
		elements.forEach((el) => {
			if (el.tagName === "INPUT" && this.input_has_phone(el.name)) {
				el.type = "text";
				el.removeAttribute('pattern');

				IMask(el, {
					mask: this.data?.phone_mask,
				});
			} else {
				// Encontre campos de input descendentes
				const inputs = el.querySelectorAll("input");
				inputs.forEach((subel) => {
					if (this.input_has_phone(subel.name)) {
						subel.type = "text";
						el.pattern = "";
						IMask(subel, {
							mask: this.data?.phone_mask,
						});
					}
				});
			}
		});
	}

	async mask_load_inter() {
		// Verifique se as configurações estão presentes
		if (!this.data?.phone_mask_inter) {
			return;
		}

		if (typeof intlTelInput == 'undefined') {
			await this.load_style(this.data.phone_mask_inter_css);
			await this.add_style(`
				.iti, .iti--allow-dropdown {
					width: 100% !important;
					z-index: 9999 !important;
				}
				.iti__tel-input {
					font-size: inherit;
					color: inherit;
				}
			`);
			await this.load_script(this.data.phone_mask_inter_js);
		}

		await this.mask_inter();
	}

	async mask_inter() {
		// Selecione elementos que precisam da máscara
		const elements = document.querySelectorAll(".pxa_mask_phone_inter, #pxa_mask_phone_inter");

		const intlTel = (el) => {
			el.type = "tel";
			el.removeAttribute('pattern');
			el.classList.add("pxa_mask_phone_inter_active");

			let input_phone = window.intlTelInput(el, {
				initialCountry: this.data.geolocation?.pxa_lead_country_code || "br",
				placeholderNumberType: 'MOBILE',
				validationNumberType: 'MOBILE',
				showSelectedDialCode: true,
				separateDialCode: true,
				strictMode: true,
			});

			el.addEventListener('blur', async () => {
				var full_number = input_phone.getNumber();
				// el.value = full_number;
				this.data.lead_phone = full_number;
				this.set({ pxa_lead_phone: full_number });
				await this.send_lead_data();
			});

			el.closest('form').querySelector('[type="submit"]')
				.addEventListener('click', () => {
					el.value = input_phone.getNumber();
				}, true);
		}

		// Aplique a máscara nos elementos encontrados
		elements.forEach((el) => {
			if (el.tagName === "INPUT" && this.input_has_phone(el.name)) {
				intlTel(el);
			} else {
				// Encontre campos de input descendentes
				const inputs = el.querySelectorAll("input");
				inputs.forEach((subel) => {
					if (this.input_has_phone(subel.name)) {
						intlTel(subel);
					}
				});
			}
		});

	}

	// Verifica o Nome do Campo
	input_has_phone(name) {
		const names = [
			'tel',
			'phone',
			'ph',
			'cel',
			'mobile',
			'fone',
			'whats',
		];
		const variables = names.concat(
			this.data?.input_custom_phone?.split(',')
		);
		let result = false;

		for (const variable of variables) {
			if (variable && name.toLowerCase().includes(variable)) {
				result = true;
				break;
			}
		}

		return result;
	}

	input_has_mail(name) {
		const names = [
			'mail',
			'email',
		];
		const variables = names.concat(
			this.data?.input_custom_email?.split(',')
		);
		let result = false;

		for (const variable of variables) {
			if (variable && name.toLowerCase().includes(variable)) {
				result = true;
				break;
			}
		}

		return result;
	}

	input_has_name(name) {
		const names = [
			'nome',
			'nombre',
			'name'
		];
		const variables = names.concat(
			this.data?.input_custom_name?.split(',')
		);
		let result = false;

		for (const variable of variables) {
			if (variable && name.toLowerCase().includes(variable)) {
				result = true;
				break;
			}
		}

		return result;
	}

	async monitor_forms() {
		// Seleciona todos os campos do formulário
		document.querySelectorAll('input').forEach(field => {
			this.input_monitor(field.name || field.id)

			if (field.value || field.classList.contains("pxa_monitor_forms_set_value")) {
				return;
			}

			// Email
			if (this.data?.lead_email && (this.input_has_mail(field.name) || this.input_has_mail(field.id))) {
				field.value = this.data?.lead_email;
				field.classList.add('pxa_monitor_forms_set_value');
			}

			// Name
			if (
				(this.input_has_name(field.name) || this.input_has_name(field.id))
				&& (this.data?.lead_name || this.data?.lead_fname || this.data?.lead_lname)
			) {
				if (field.name.includes('first')) { // First
					field.value = this.data?.lead_fname || this.data?.lead_name;
				} else if (field.name.includes('last')) { // Last
					field.value = this.data?.lead_lname;
				} else if (!field.name.includes('last')) { // Full
					field.value = this.data?.lead_name || this.data?.lead_fname;
				}
				field.classList.add('pxa_monitor_forms_set_value');
			}

			// Phone
			if (this.data?.lead_phone) {
				if (
					(this.input_has_phone(field.name) || this.input_has_phone(field.id))
					&& !field.name.includes('ddi')
					&& !field.name.includes('ddd')
				) {
					field.value = this.data?.lead_phone;
					field.classList.add('pxa_monitor_forms_set_value');
				}
			}
		});
	}

	check_values(form) {
		let checked = true;

		// Obtém todos os campos do formulário
		const fields = form.querySelectorAll('[name]');

		// Percorre cada campo
		for (const field of fields) {
			const fieldName = field.name.toLowerCase(); // Converte o nome do campo para minúsculo

			// Verifica se o nome do campo está na lista de campos obrigatórios
			if (
				this.input_has_phone(fieldName) ||
				this.input_has_mail(fieldName) ||
				this.input_has_name(fieldName)
			) {
				// Verifica se o campo possui valor
				if (!field.value.trim()) {
					checked = false; // Se o campo não tiver valor, define checked como false
					break; // Sai do loop se encontrar um campo vazio
				}
			}
		}

		return checked;
	}


	async input_monitor(name) {
		const formFields = document.querySelectorAll(`[name*="${name}"]`);
		formFields.forEach((field) => {
			field.addEventListener('input', async (event) => {
				await this.input_save(event.target.name, event.target.value);
			});
			field.addEventListener('blur', async (event) => {
				if (!event.target.classList.contains("pxa_mask_phone_inter_active")) {
					await this.input_save(event.target.name, event.target.value, field);
					await this.send_lead_data();
				}
			});
		});
	}

	async input_save(name, value, field = undefined) {
		// Email
		if (this.input_has_mail(name)) {
			this.set({ pxa_lead_email: value });
			this.data.lead_email = value;
		}

		// Phone
		if (
			this.input_has_phone(name)
			&& !name.includes('ddi')
			&& !name.includes('ddd')
		) {
			if (this.data?.phone_valid) {
				value = this.phone_valid(value, this.data?.phone_country);

				if (field && this.data?.phone_update) {
					field.value = value;
				}
			}

			this.set({ pxa_lead_phone: value })
			this.data.lead_phone = value;
		}

		// Name
		if (this.input_has_name(name)) {
			let full_name = this.data.lead_name,
				fname = this.data.lead_fname,
				lname = this.data.lead_lname;

			if (name.includes('first')) {
				fname = value.substring(0, value.indexOf(' '));
				lname = value.substring(value.indexOf(' ') + 1);
				full_name = [fname, lname].join(' ')
			} else if (name.includes('last')) {
				lname = value;
				full_name = [fname, lname].join(' ')
			} else if (!name.includes('last')) {
				full_name = value;
				fname = value.substring(0, value.indexOf(' '));
				lname = value.substring(value.indexOf(' ') + 1) || this.data.lead_lname;
			}

			this.set({
				pxa_lead_name: full_name,
				pxa_lead_fname: fname,
				pxa_lead_lname: lname,
			});

			this.data.lead_name = full_name;
			this.data.lead_fname = fname;
			this.data.lead_lname = lname;
		}
	}

	phone_valid(phone, country = '55') {
		// Limpa todos os caracteres que não são números
		phone = phone.replace(/[^0-9]/g, '');

		if (country === '55') {
			// Remove todos os 0 do começo do telefone, se houver
			if (phone.startsWith('00')) {
				phone = phone.substring(2);
			} else if (phone.startsWith('0')) {
				phone = phone.substring(1);
			}

			// Valida se o telefone tem 10 dígitos
			if (phone.length === 10) {
				// Adiciona o nono dígito no telefone, após o DDD, 2 números iniciais do telefone
				phone = `55${phone.substring(0, 2)}9${phone.substring(2)}`;
			}

			// Valida se o telefone tem 12 dígitos e começa com 55
			else if (phone.length === 12 && phone.startsWith('55')) {
				// Insere o nono dígito do telefone, após o DDI e DDD, os 4 números iniciais
				phone = `55${phone.substring(0, 4)}9${phone.substring(4)}`;
			}
		}

		// Verifica se o país já está incluído no início do telefone
		if (!phone.startsWith(country)) {
			phone = `${country}${phone}`;
		}

		return `+${phone}`;
	}

	async load_script(url) {
		return new Promise((resolve, reject) => {
			const tag = document.createElement('script');
			tag.src = url;
			tag.onload = resolve;
			tag.onerror = reject;
			document.head.appendChild(tag);
		});
	}

	async load_style(url) {
		return new Promise((resolve, reject) => {
			let tag = document.createElement('link');
			tag.href = url;
			tag.rel = 'stylesheet';
			tag.type = 'text/css';
			tag.onload = resolve;
			tag.onerror = reject;
			document.head.appendChild(tag);
		});
	}

	async add_style(content) {
		return new Promise((resolve, reject) => {
			const tag = document.createElement('style');
			tag.textContent = content;
			tag.onload = resolve;
			tag.onerror = reject;
			document.head.appendChild(tag);
		});
	}

	async parameters_load(lead_info = false) {
		const urlParams = new URLSearchParams(window.location.search);

		// Facebook
		if (this.data.fb_fbc && !urlParams.has('fbclid')) {
			urlParams.append('fbclid', this.data.fb_fbc)
		}

		if (this.data.fb_fbp && !urlParams.has('fbp')) {
			urlParams.append('fbp', this.data.fb_fbp)
		}

		// UTMs
		if (this.data.utm_source && !urlParams.has('utm_source')) {
			urlParams.append('utm_source', this.data.utm_source)
		}

		if (this.data.utm_medium && !urlParams.has('utm_medium')) {
			urlParams.append('utm_medium', this.data.utm_medium)
		}

		if (this.data.utm_campaign && !urlParams.has('utm_campaign')) {
			urlParams.append('utm_campaign', this.data.utm_campaign)
		}

		if (this.data.utm_content && !urlParams.has('utm_content')) {
			urlParams.append('utm_content', this.data.utm_content)
		}

		if (this.data.utm_term && !urlParams.has('utm_term')) {
			urlParams.append('utm_term', this.data.utm_term)
		}

		// SRC e SCK
		if (this.data.src && !urlParams.has('src')) {
			urlParams.append('src', this.data.src)
		}

		if (!urlParams.has('sck')) {
			urlParams.append('sck', this.data.sck || this.data.lead_id)
		}


		if (this.data.lead_id && !urlParams.has('external_id')) {
			urlParams.append('external_id', this.data.lead_id)
		}

		if (lead_info) {
			if (this.data.lead_name && !urlParams.has('name')) {
				urlParams.append('name', this.data.lead_name)
			}

			if (this.data.lead_email && !urlParams.has('email')) {
				urlParams.append('email', this.data.lead_email)
			}

			if (this.data.lead_phone && !urlParams.has('phone')) {
				urlParams.append('phone', this.data.lead_phone)
			}

			if (this.data.geolocation?.pxa_lead_ip && !urlParams.has('ip')) {
				urlParams.append('ip', this.data.geolocation?.pxa_lead_ip)
			}
		}

		const aTags = document.querySelectorAll('a[href^="http"]');

		aTags.forEach(link => {
			if (!link.href.includes("#") && !link.href.includes(window.location.origin)) {
				link.href += link.href.includes('?') ? `&${urlParams.toString()}` : `?${urlParams.toString()}`
			}
		});
	}

	async hash_value(value) {
		if (!value || !value.length) {
			return null;
		}

		// Verifica se o navegador suporta a API de Criptografia Web
		if (crypto && crypto.subtle) {
			// Converte a string para ArrayBuffer
			const encoder = new TextEncoder();
			const data = encoder.encode(value);

			// Calcula o hash usando o algoritmo SHA-256
			const hashBuffer = await crypto.subtle.digest('SHA-256', data);

			// Converte o ArrayBuffer para uma string hexadecimal
			const hashArray = Array.from(new Uint8Array(hashBuffer));
			const hashHex = hashArray.map(byte => byte.toString(16).padStart(2, '0')).join('');

			return hashHex;
		} else {
			await this.load_script('https://cdn.jsdelivr.net/npm/js-sha256/src/sha256.min.js');


			return sha256(value);
		}
	}

	randomInt(min = 1000000000, max = 9999999999) {
		return Math.floor(Math.random() * (max - min + 1)) + min;
	}

	uuid() {
		if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
			// Se o navegador suportar crypto.randomUUID(), use-a para gerar o UUID
			return crypto.randomUUID();
		} else {
			// Se não, use uma implementação alternativa para gerar um UUID pseudoaleatório
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
				let r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
				return v.toString(16);
			});
		}
	}


	check_domain() {
		if (!this.data.page_url.includes(this.data.domain) && !this.data.domain.includes(this.data.page_url)) {
			console.log("Página fora do domínio de origem", this.data.domain);
			return false;
		}

		return true;
	}

	check_is_hidden(element) {
		if (!element) {
			return true; // Se o elemento não existe, consideramos como oculto.
		}

		// Função auxiliar para verificar se um elemento está visível
		function inViewport(elm) {
			var rect = elm.getBoundingClientRect();
			var viewHeight = Math.max(document.documentElement.clientHeight, window.innerHeight);
			return !(rect.bottom < 0 || rect.top - viewHeight >= 0);
		}

		function isVisible(elem) {
			const style = window.getComputedStyle(elem);

			// Verifica se o elemento está oculto por `display`, `visibility` ou `opacity`
			if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
				return false;
			}

			// Verifica se o elemento está fora do DOM ou sem tamanho visível
			if (!inViewport(elem)) {
				return false;
			}

			return true;
		}

		// Verifica se o elemento passado é visível
		if (!isVisible(element)) {
			return true; // Se o elemento em si está oculto, retorna true
		}

		// Verifica todos os ancestrais do elemento
		let currentElement = element;
		while (currentElement) {
			if (currentElement === document.body) {
				break; // Chegamos ao body, termina a verificação
			}

			// Move para o pai do elemento atual
			currentElement = currentElement.parentElement;

			// Verifica se o elemento pai é visível
			if (currentElement && !isVisible(currentElement)) {
				return true; // Se algum pai não é visível, retorna true
			}
		}

		return false; // Se nenhum elemento ou ancestral estiver oculto, retorna false
	}

	/*
	 * Facebook
	 */
	async facebook_send_event(data) {
		if (!this.data.fb_pixels) {
			return;
		}

		let content_ids = data?.content_ids || null;

		if (typeof content_ids === 'string') {
			content_ids = content_ids.split(',');
		}

		if (data?.lead_name) {
			this.data.lead_name = data?.lead_name;
		}

		if (data?.lead_fname) {
			this.data.lead_fname = data?.lead_fname;
		}

		if (data?.lead_lname) {
			this.data.lead_lname = data?.lead_lname;
		}

		if (data?.lead_email) {
			this.data.lead_email = data?.lead_email;
		}

		if (data?.lead_phone) {
			this.data.lead_phone = data?.lead_phone;
		}

		if (
			data?.lead_name
			|| data?.lead_fname
			|| data?.lead_lname
			|| data?.lead_email
			|| data?.lead_phone
		) {
			await this.send_lead_data();
		}

		const fb_data = {
			event_id: data?.event_id,
			event_time: this.getTimestampUtc(),
			event_name: data?.event_name,
			content_ids: content_ids,
			product_id: data?.product_id,
			product_name: data?.product_name,
			content_name: data?.content_name || data?.product_name,
			value: data?.product_value,
			predicted_ltv: data?.predicted_ltv,
			currency: data?.currency,
			page_title: this.data?.page_title,
			page_id: this.data?.page_id,
		};

		if (this.data.web_priority) {
			await this.facebook_web(fb_data);
			this.facebook_api(fb_data);
		} else {
			this.facebook_api(fb_data);
			await this.facebook_web(fb_data);
		}
	}

	async facebook_load_pixel() {
		if (typeof fbq === 'undefined') {
			! function(f, b, e, v, n, t, s) {
				if (f.fbq) return;
				n = f.fbq = function() {
					n.callMethod ?
						n.callMethod.apply(n, arguments) : n.queue.push(arguments)
				};
				if (!f._fbq) f._fbq = n;
				n.push = n;
				n.loaded = !0;
				n.version = '2.0';
				n.queue = [];
				t = b.createElement(e);
				t.async = !0;
				t.src = v;
				s = b.getElementsByTagName(e)[0];
				s.parentNode.insertBefore(t, s)
			}(window, document, 'script', this.data.fb_js);

			let pixels = this.data.fb_pixels;

			if (pixels.includes(',')) {
				pixels = pixels.split(',');

				for (let pixel of pixels) {
					fbq('init', pixel);
				}
			} else {
				fbq('init', pixels);
			}
		}
	}

	async facebook_web(data) {
		await this.facebook_load_pixel();

		let type = data?.event_custom == true ? 'trackCustom' : 'track';
		let event_name = data?.event_name || "PageView";
		let event_id = data?.event_id || this.data?.event_id;
		let params = {
			// Lead
			external_id: this.data?.lead_id,
			nm: await this.hash_value(this.data?.lead_name),
			fn: await this.hash_value(this.data?.lead_fname || this.data?.lead_name),
			ln: await this.hash_value(this.data?.lead_lname),
			em: await this.hash_value(this.data?.lead_email),
			ph: await this.hash_value(this.data?.lead_phone),

			// FBC e FBP
			fbc: this.data.fbc,
			fbp: this.data.fbp,

			// Product
			currency: data?.currency || this.data.geolocation?.pxa_lead_currency,
			content_type: 'product',
			content_ids: data?.content_ids,
			product_id: data?.product_id,
			product_name: data?.product_name,
			content_name: data?.content_name,
			value: data?.value,
			predicted_ltv: data?.predicted_ltv,

			// Geolocation
			client_user_agent: this.data.user_agent,
			client_ip_address: this.data.geolocation?.pxa_lead_ip,
			ct: await this.hash_value(this.data.geolocation?.pxa_lead_city),
			st: await this.hash_value(this.data.geolocation?.pxa_lead_region),
			zp: await this.hash_value(this.data.geolocation?.pxa_lead_zipcode),
			country: await this.hash_value(this.data.geolocation?.pxa_lead_country_code),

			// Event
			event_time: data?.event_time,
			event_day: this.data.event_day,
			event_day_in_month: this.data.event_day_in_month,
			event_month: this.data.event_month,
			event_time_interval: this.data.event_time_interval,
			event_url: this.data.page_url,
			event_source_url: this.data.page_url,
			traffic_source: this.data.traffic_source,

			// Page
			page_id: this.data?.page_id,
			page_title: this.data?.page_title,

			// App
			plugin: 'Pixel X App',
			plugin_info: 'https://pixelx.app/ph',
		};

		fbq(type, event_name, params, {
			eventID: event_id,
		});
	}

	facebook_api(data) {
		fetch(this.data.api_event, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			// body: data
			body: JSON.stringify({
				...this.data,
				...data
			})
		});
	}

	/**
	 * Methods
	 */
	async send_event(data) {
		if (!this.check_domain() || !data?.event_name) {
			return;
		}

		data = {
			...data,
			event_id: data?.event_id || this.uuid(),
		}

		await this.facebook_send_event(data);
	}

	async send_lead_data() {
		return await fetch(this.data.api_lead, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			// body: data
			body: JSON.stringify({
				// Info
				lead_id: this.data.lead_id,
				lead_name: this.data.lead_name,
				lead_fname: this.data.lead_fname,
				lead_lname: this.data.lead_lname,
				lead_email: this.data.lead_email,
				lead_phone: this.data.lead_phone,

				// Geolocation
				ip: this.data.geolocation?.pxa_lead_ip,
				device: this.data.user_agent,
				adress_city: this.data.geolocation?.pxa_lead_city,
				adress_state: this.data.geolocation?.pxa_lead_region,
				adress_zipcode: this.data.geolocation?.pxa_lead_zipcode,
				adress_country_name: this.data.geolocation?.pxa_lead_country,
				adress_country: this.data.geolocation?.pxa_lead_country_code,

				// Facebook
				fbc: this.data.fb_fbc,
				fbp: this.data.fb_fbp,
				utm_source: this.data.utm_source,
				utm_medium: this.data.utm_medium,
				utm_campaign: this.data.utm_campaign,
				utm_id: this.data.utm_id,
				utm_term: this.data.utm_term,
				utm_content: this.data.utm_content,
				src: this.data.src,
				sck: this.data.sck,
			})
		})
			.then(async (data) => {
				data = await data.json();
				let lead_data = data?.data

				this.data.lead_id = lead_data.id;
				this.data.lead_name = lead_data?.name || this.data.lead_name;
				this.data.lead_fname = lead_data?.fname || this.data.lead_fname;
				this.data.lead_lname = lead_data?.lname || this.data.lead_lname;
				this.data.lead_email = lead_data?.email || this.data.lead_email;
				this.data.lead_phone = lead_data?.phone || this.data.lead_phone;

				this.data.geolocation.pxa_lead_ip = lead_data?.ip || this.data.geolocation?.pxa_lead_ip;
				this.data.geolocation.pxa_lead_city = lead_data?.adress_city || this.data.geolocation?.pxa_lead_city;
				this.data.geolocation.pxa_lead_region = lead_data?.adress_state || this.data.geolocation?.pxa_lead_region;
				this.data.geolocation.pxa_lead_zipcode = lead_data?.adress_zipcode || this.data.geolocation?.pxa_lead_zipcode;
				this.data.geolocation.pxa_lead_country = lead_data?.adress_country_name || this.data.geolocation?.pxa_lead_country;
				this.data.geolocation.pxa_lead_country_code = lead_data?.adress_country || this.data.geolocation?.pxa_lead_country_code;

				this.data.fb_fbc = lead_data?.fbc;
				this.data.fb_first_fbc = lead_data?.first_fbc;
				this.data.fb_fbp = lead_data?.fbp;

				this.data.utm_source = lead_data?.utm_source;
				this.data.utm_medium = lead_data?.utm_medium;
				this.data.utm_campaign = lead_data?.utm_campaign;
				this.data.utm_id = lead_data?.utm_id;
				this.data.utm_term = lead_data?.utm_term;
				this.data.utm_content = lead_data?.utm_content;
				this.data.src = lead_data?.src;
				this.data.sck = lead_data?.sck;

				this.set({
					pxa_lead_id: this.data.lead_id,
					pxa_lead_name: this.data.lead_name,
					pxa_lead_fname: this.data.lead_fname,
					pxa_lead_lname: this.data.lead_lname,
					pxa_lead_email: this.data.lead_email,
					pxa_lead_phone: this.data.lead_phone,
					_fbc: this.data.fb_fbc,
					_fbp: this.data.fb_fbp,
				});

				return lead_data;
			})
			.catch(() => {
				return {};
			});
	}
}