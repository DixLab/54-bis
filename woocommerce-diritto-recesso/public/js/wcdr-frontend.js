/**
 * Script del modulo frontend di recesso.
 *
 * Esegue una validazione lato client (non sostituisce quella lato server)
 * e migliora l'esperienza utente.
 *
 * @package WooCommerce_Diritto_Recesso
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.querySelector('.wcdr-form');
		if (!form) {
			return;
		}

		// Imposta come data massima selezionabile la data odierna.
		var campoData = form.querySelector('#wcdr_data_acquisto');
		if (campoData) {
			var oggi = new Date().toISOString().split('T')[0];
			campoData.setAttribute('max', oggi);
		}

		// Validazione all'invio del form.
		form.addEventListener('submit', function (e) {
			var errori = [];

			var ordine = form.querySelector('#wcdr_numero_ordine');
			var nome = form.querySelector('#wcdr_nome_cliente');
			var email = form.querySelector('#wcdr_email_cliente');
			var data = form.querySelector('#wcdr_data_acquisto');
			var privacy = form.querySelector('input[name="wcdr_privacy"]');

			if (ordine && ordine.value.trim() === '') {
				errori.push('Inserisci il numero ordine.');
			}
			if (nome && nome.value.trim() === '') {
				errori.push('Inserisci nome e cognome.');
			}
			if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
				errori.push('Inserisci un indirizzo email valido.');
			}
			if (data && data.value.trim() === '') {
				errori.push('Inserisci la data di acquisto.');
			}
			if (privacy && !privacy.checked) {
				errori.push('Devi accettare il trattamento dei dati personali.');
			}

			if (errori.length > 0) {
				e.preventDefault();
				alert(errori.join('\n'));
			} else {
				// Disabilita il bottone per evitare doppi invii.
				var bottone = form.querySelector('.wcdr-bottone');
				if (bottone) {
					bottone.disabled = true;
					bottone.textContent = 'Invio in corso...';
				}
			}
		});
	});
})();
