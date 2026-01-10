/*****************************
 * UTILITY FUNCTIONS
 *****************************/

// --- Capitalizza ogni parola di una stringa ---
let Capitalize = function (str) {
	return str
		.split(" ")
		.map(word => word.charAt(0).toUpperCase() + word.slice(1))
		.join(" ");
};

function normalizzaValore(valore) {
	if (!valore) return null;

	let v = valore.trim();

	// Se è percentuale
	if (v.includes('%')) {
		v = v.replace('%', '').trim();
		v = v.replace(/\./g, '');  // rimuove separatori migliaia
		v = v.replace(',', '.');   // converte virgola in punto
		let num = parseFloat(v);
		if (isNaN(num)) return null;
		return num.toFixed(2); // "10.00" per 10%
	}

	// Se è valuta
	if (v.includes('€')) {
		v = v.replace('€', '').trim();
	}

	// Rimuovo eventuali spazi e punti per separatore migliaia
	//v = v.replace(/\./g, '');
	// Virgola italiana → punto decimale
	v = v.replace(',', '.');

	let num = parseFloat(v);
	if (isNaN(num)) return null;

	return num.toFixed(2); // decimale normale
}

// --- Validazione email ---
let ValidateEmail = function (email) {
	const emailReg = /^([\w-.]+@([\w-]+\.)+[\w-]{2,4})?$/;
	return emailReg.test(email);
};

// --- Validazione link URL ---
let ValidateLink = function (link) {
	const linkReg = /^(http|https|ftp):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i;
	return linkReg.test(link);
};

// --- Copia testo negli appunti ---
function copyToClipboard(text) {
	const dummy = document.createElement("input");
	document.body.appendChild(dummy);
	dummy.value = text;
	dummy.select();
	document.execCommand("copy");
	document.body.removeChild(dummy);
	return text;
}

// --- Rimuove un elemento dal DOM tramite ID ---
function RimuoviElemento(idElemento) {
	$(`#${idElemento}`).remove();
}

// --- Mostra modal dinamica ---
function MostraModal(titolo, body, bottoni, type = "classic") {
	$('#staticBackdrop').css("background-color", "rgba(0,0,0,.9)");
	$('#staticBackdropLabel').html(titolo);
	$('#modalBody').html(body);
	$('#bottoniModal').html(bottoni);

	if (type !== "classic") {
		$('#classDialog').addClass('modal-fullscreen');
	} else {
		$('#classDialog').removeClass('modal-fullscreen');
	}

	$('#staticBackdrop').modal('show');
}

// --- Chiude modal ---
function ChiudiModal() {
	$('#staticBackdrop').modal('hide');
}

// --- Formatta data per DB (YYYY-MM-DD HH:MM:SS) ---
function formatDateForDB(date) {
	const pad = n => n < 10 ? '0' + n : n;
	return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

// --- Cookie helpers ---
function setCookie(name, value, days) {
	const d = new Date();
	d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
	document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/`;
}

function getCookie(name) {
	const cname = name + "=";
	const decodedCookie = decodeURIComponent(document.cookie);
	const ca = decodedCookie.split(';');
	for (let c of ca) {
		c = c.trim();
		if (c.indexOf(cname) === 0) return c.substring(cname.length, c.length);
	}
	return "";
}

/*****************************
 * DATATABLES FUNCTIONS
 *****************************/

// --- Inizializza tabella con ricerca colonna per colonna e wrapper responsive ---
function CaricaTabella(idTabella, tutto = 0, ordine = 0, direzione = "asc", state = true) {
	let cont = 0;

	// --- Aggiunge input di ricerca per ogni colonna nel footer ---
	$("#" + idTabella + " tfoot th").each(function () {
		$(this).html('<input type="text" class="form-control form-control-rounded" id="column' + cont + '" InputTabella="true" placeholder="Cerca..." />');
		cont++;
	});

	const table = $("#" + idTabella).DataTable({
		stateSave: state,
		pageLength: 10,
		stateSaveParams: function (settings, data) {
			delete data.search;
		},
		initComplete: function () {
			// --- Wrappa la tabella dopo che DataTables ha creato il suo wrapper ---
			const $tbl = $("#" + idTabella);
			if (!$tbl.parent().hasClass("table-responsive")) {
				$tbl.wrap('<div class="table-responsive"></div>');
			}
		}
	});

	table.order([ordine, direzione]).draw();

	if (tutto > 0) table.page.len(tutto).draw();

	// --- Abilita ricerca per colonna ---
	table.columns().every(function () {
		const that = this;
		$("input", this.footer()).on("keyup change", function () {
			if (that.search() !== this.value) {
				that.search(this.value).draw();
			}
		});
	});

	// --- Rimuove filtro DataTables predefinito ---
	$('#' + idTabella + '_filter').remove();
	$("#" + idTabella + '_id').attr("SelectTabella", true);

	$('.noInput input').each(function () {
		$(this).addClass('invisibile');
	});
}

// --- Ripristina valori di input delle colonne da localStorage ---
function TableInput(idTable, url_target) {
	const item = idTable + '_' + url_target;
	const x = window.localStorage.getItem('DataTables_' + item);
	if (!x) return;

	const datijson = JSON.parse(x);
	$.each(datijson.columns, (key, value) => {
		if (value.search.search !== "") {
			$('#column' + key).val(value.search.search);
		}
	});
}


/*****************************
 * FORM VALIDATION
 *****************************/
let ErroriInput = "";

$(document).ready(function () {
	// --- Form validation bootstrap 5 ---
	$("form.needs-validation").on("submit", function (event) {
		const form = this;
		let lista = "";
		let vuoti = [];

		if (!form.checkValidity()) {
			event.preventDefault();
			event.stopPropagation();
		}

		// --- Controlla input richiesti ---
		$(form).find(":input[required]").each(function () {
			const nome_input = $(this).attr("name");
			if (!$(this).val().trim()) {
				lista += `<li>${nome_input}</li>`;
				vuoti.push(nome_input || $(this).attr("id"));
			} else if (this.validity && this.validity.patternMismatch) {
				lista += `<li>${nome_input} (formato non valido)</li>`;
				vuoti.push(nome_input + " (formato non valido)");
			}
		});

		// --- Controlla select2 richieste ---
		$(form).find("select[required]").each(function () {
			const $select = $(this);
			if (!$select.val() || $select.val().length === 0) {
				$select.next(".select2").find(".select2-selection.select2-selection--single").addClass("is-invalid");
			} else {
				$select.next(".select2").find(".select2-selection.select2-selection--single").removeClass("is-invalid");
			}
		});

		if (lista) toastr["error"](`<ul>${lista}</ul>`, "Errore Campi Obbligatori");

		//console.log("Campi vuoti:", vuoti);

		$(form).addClass("was-validated");
	});

	// --- Rimuove errore quando l’utente cambia valore ---
	$("select[required]").on("change", function () {
		$(this).next(".select2").find(".select2-selection.select2-selection--single").removeClass("is-invalid");
	});

	// --- Blocca invio form con ENTER ---
	$(window).keydown(function (event) {
		if (event.keyCode === 13) {
			event.preventDefault();
			return false;
		}
	});
});

/*****************************
 * DATE & TIME PICKERS
 *****************************/
function initDynamicPickers(container = document) {
	// --- DATE PICKER ---
	container.querySelectorAll('input[type="date"]').forEach(function (el, idx) {
		el.type = "text";
		el.pattern = "^(0[1-9]|[12][0-9]|3[01])[-./](0[1-9]|1[0-2])[-./](\\d{4})$";
		el.placeholder = "gg-mm-aaaa";
		el.classList.add("datepicker");
		if (!el.id) el.id = "datepicker_" + idx;

		$(el).datepicker({
			autoclose: true,
			format: 'dd-mm-yyyy',
			language: 'it',
			title: 'Calendario Arte 58',
			weekStart: 1,
			todayBtn: true,
			todayHighlight: true,
			orientation: "bottom"
		});
	});

	// --- TIME PICKER ---
	container.querySelectorAll('input[type="time"]').forEach(function (el, idx) {
		if (el._flatpickr) return;
		el.type = "text";
		el.pattern = "^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$";
		el.placeholder = "HH:MM";
		el.classList.add("timepicker");
		if (!el.id) el.id = "timepicker_" + idx;

		$(el).timepicker({
			timeFormat: 'HH:mm',
			interval: 30,
			dynamic: false,
			dropdown: true,
			scrollbar: true
		});
	});

	// --- Imposta valori di default ---
	SettaDefault();
}

function SettaDefault() {
	// Datepicker default
	$('input.datepicker[data-default]').each(function () {
		const defaultDateStr = $(this).data('default');
		const Inserita = $(this).val();
		if (defaultDateStr && Inserita !== "") {
			$(this).datepicker('setDate', defaultDateStr);
		} else {
			$(this).datepicker('setDate', new Date());
		}
	});
}

/*****************************
 * TABS HASH HANDLING
 *****************************/
document.addEventListener("DOMContentLoaded", function () {
	const hash = window.location.hash;
	if (hash) {
		history.replaceState(null, null, window.location.pathname + window.location.search);
		const tabTrigger = document.querySelector(`a[href="${hash}"]`);
		if (tabTrigger) new bootstrap.Tab(tabTrigger).show();
	}

	// --- Aggiorna hash quando cambio tab ---
	document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(link => {
		link.addEventListener("click", e => e.preventDefault());
		link.addEventListener("shown.bs.tab", e => {
			history.replaceState(null, null, e.target.getAttribute("href"));
		});
	});

	setTimeout(() => history.replaceState(null, null, hash), 2);

	// --- Inizializza date/time picker ---
	initDynamicPickers();
	percentualeClass();
});


function percentuale(id) {
	$(function () {
		$('#' + id).blur(function () {
			let val = $(this).val().replace(/[^0-9,.-]/g, ''); // tolgo simboli strani
			if (val === '') return;
			// se contiene la virgola la trasformo in decimale
			val = val.replace(',', '.');
			let num = parseFloat(val);
			if (isNaN(num)) num = 0;
			$(this).val('% ' + num.toFixed(2));
		})
			.keyup(function (e) {
				var keyUnicode = e.charCode || e.keyCode;
				switch (keyUnicode) {
					case 16: case 17: case 18: case 27:
					case 35: case 36: case 37: case 38:
					case 39: case 40: case 78: case 110:
					case 190: case 46:
						break; // tasti di controllo
					default:
						let val = $(this).val().replace(/[^0-9,.-]/g, '');
						if (val === '') return;
						val = val.replace(',', '.');
						let num = parseFloat(val);
						if (!isNaN(num)) {
							$(this).val('% ' + num);
						} else {
							$(this).val('');
						}
				}
			});
	});
}

function percentualeClass() {
	$(function () {
		$('.percentuale').blur(function () {
			let val = $(this).val().replace(/[^0-9,.-]/g, '');
			if (val === '') return;
			val = val.replace(',', '.');
			let num = parseFloat(val);
			if (isNaN(num)) num = 0;
			$(this).val('% ' + num.toFixed(2) );
		})
			.keyup(function (e) {
				var keyUnicode = e.charCode || e.keyCode;
				switch (keyUnicode) {
					case 16: case 17: case 18: case 27:
					case 35: case 36: case 37: case 38:
					case 39: case 40: case 78: case 110:
					case 190: case 46:
						break;
					default:
						let val = $(this).val().replace(/[^0-9,.-]/g, '');
						if (val === '') return;
						val = val.replace(',', '.');
						let num = parseFloat(val);
						if (!isNaN(num)) {
							$(this).val('% ' + num);
						} else {
							$(this).val('');
						}
				}
			});
	});
}