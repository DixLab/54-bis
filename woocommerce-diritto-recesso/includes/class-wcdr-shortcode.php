<?php
/**
 * Classe per la gestione dello shortcode e del modulo frontend.
 *
 * Registra lo shortcode [modulo_recesso] e gestisce l'invio del form,
 * incluse le validazioni (ordine esistente ed entro i 14 giorni).
 *
 * @package WooCommerce_Diritto_Recesso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCDR_Shortcode
 */
class WCDR_Shortcode {

	/**
	 * Inizializza gli hook dello shortcode e del gestore del form.
	 */
	public function __construct() {
		add_shortcode( 'modulo_recesso', array( $this, 'render_modulo' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'carica_asset' ) );

		// Gestione invio form sia per utenti loggati che non loggati.
		add_action( 'admin_post_wcdr_invia_recesso', array( $this, 'gestisci_invio' ) );
		add_action( 'admin_post_nopriv_wcdr_invia_recesso', array( $this, 'gestisci_invio' ) );
	}

	/**
	 * Carica CSS e JS del frontend solo dove serve.
	 */
	public function carica_asset() {
		wp_enqueue_style(
			'wcdr-frontend',
			WCDR_PLUGIN_URL . 'public/css/wcdr-frontend.css',
			array(),
			WCDR_VERSION
		);
		wp_enqueue_script(
			'wcdr-frontend',
			WCDR_PLUGIN_URL . 'public/js/wcdr-frontend.js',
			array(),
			WCDR_VERSION,
			true
		);
	}

	/**
	 * Renderizza il modulo di recesso (output dello shortcode).
	 *
	 * @param array $attributi Attributi dello shortcode.
	 * @return string HTML del modulo.
	 */
	public function render_modulo( $attributi ) {
		ob_start();

		// Mostra messaggio di conferma o di errore se presente (post-invio).
		$stato_invio = isset( $_GET['wcdr_stato'] ) ? sanitize_text_field( wp_unslash( $_GET['wcdr_stato'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'successo' === $stato_invio ) {
			echo $this->messaggio_conferma(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return ob_get_clean();
		}

		$errore = '';
		if ( 'errore' === $stato_invio ) {
			$codice_errore = isset( $_GET['wcdr_errore'] ) ? sanitize_text_field( wp_unslash( $_GET['wcdr_errore'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$errore        = $this->testo_errore( $codice_errore );
		}

		// Recupera i valori precedenti in caso di errore (per non perdere i dati).
		$dati_precedenti = get_transient( 'wcdr_form_' . $this->chiave_sessione() );
		delete_transient( 'wcdr_form_' . $this->chiave_sessione() );
		$dati_precedenti = is_array( $dati_precedenti ) ? $dati_precedenti : array();

		$v = function ( $campo ) use ( $dati_precedenti ) {
			return isset( $dati_precedenti[ $campo ] ) ? esc_attr( $dati_precedenti[ $campo ] ) : '';
		};
		?>
		<div class="wcdr-modulo-wrapper">
			<h3 class="wcdr-titolo"><?php esc_html_e( 'Modulo per il Diritto di Recesso', 'wc-diritto-recesso' ); ?></h3>
			<p class="wcdr-intro">
				<?php esc_html_e( 'Ai sensi dell\'art. 54-bis del Codice del Consumo, hai diritto di recedere dal contratto entro 14 giorni dalla ricezione dei beni, senza alcuna penalità e senza specificarne il motivo. Compila il modulo seguente per inviare la tua richiesta.', 'wc-diritto-recesso' ); ?>
			</p>

			<?php if ( ! empty( $errore ) ) : ?>
				<div class="wcdr-avviso wcdr-avviso-errore"><?php echo esc_html( $errore ); ?></div>
			<?php endif; ?>

			<form class="wcdr-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
				<input type="hidden" name="action" value="wcdr_invia_recesso">
				<?php wp_nonce_field( 'wcdr_invia_recesso', 'wcdr_nonce' ); ?>
				<input type="hidden" name="wcdr_pagina_ritorno" value="<?php echo esc_url( get_permalink() ); ?>">

				<div class="wcdr-campo">
					<label for="wcdr_numero_ordine"><?php esc_html_e( 'Numero ordine', 'wc-diritto-recesso' ); ?> <span class="wcdr-obbligatorio">*</span></label>
					<input type="text" id="wcdr_numero_ordine" name="numero_ordine" value="<?php echo $v( 'numero_ordine' ); ?>" required>
					<small><?php esc_html_e( 'Lo trovi nell\'email di conferma del tuo ordine.', 'wc-diritto-recesso' ); ?></small>
				</div>

				<div class="wcdr-campo">
					<label for="wcdr_nome_cliente"><?php esc_html_e( 'Nome e cognome', 'wc-diritto-recesso' ); ?> <span class="wcdr-obbligatorio">*</span></label>
					<input type="text" id="wcdr_nome_cliente" name="nome_cliente" value="<?php echo $v( 'nome_cliente' ); ?>" required>
				</div>

				<div class="wcdr-campo">
					<label for="wcdr_email_cliente"><?php esc_html_e( 'Email', 'wc-diritto-recesso' ); ?> <span class="wcdr-obbligatorio">*</span></label>
					<input type="email" id="wcdr_email_cliente" name="email_cliente" value="<?php echo $v( 'email_cliente' ); ?>" required>
					<small><?php esc_html_e( 'Deve corrispondere all\'email usata per l\'ordine.', 'wc-diritto-recesso' ); ?></small>
				</div>

				<div class="wcdr-campo">
					<label for="wcdr_data_acquisto"><?php esc_html_e( 'Data di acquisto', 'wc-diritto-recesso' ); ?> <span class="wcdr-obbligatorio">*</span></label>
					<input type="date" id="wcdr_data_acquisto" name="data_acquisto" value="<?php echo $v( 'data_acquisto' ); ?>" required>
				</div>

				<div class="wcdr-campo">
					<label for="wcdr_motivo"><?php esc_html_e( 'Motivo del recesso (facoltativo)', 'wc-diritto-recesso' ); ?></label>
					<textarea id="wcdr_motivo" name="motivo" rows="4"><?php echo esc_textarea( isset( $dati_precedenti['motivo'] ) ? $dati_precedenti['motivo'] : '' ); ?></textarea>
				</div>

				<div class="wcdr-campo wcdr-campo-privacy">
					<label>
						<input type="checkbox" name="wcdr_privacy" value="1" required>
						<?php esc_html_e( 'Ho letto e accetto il trattamento dei miei dati personali per la gestione della richiesta di recesso.', 'wc-diritto-recesso' ); ?> <span class="wcdr-obbligatorio">*</span>
					</label>
				</div>

				<div class="wcdr-campo">
					<button type="submit" class="wcdr-bottone"><?php esc_html_e( 'Invia richiesta di recesso', 'wc-diritto-recesso' ); ?></button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Genera l'HTML del messaggio di conferma post-invio.
	 *
	 * @return string HTML della conferma.
	 */
	private function messaggio_conferma() {
		ob_start();
		?>
		<div class="wcdr-modulo-wrapper">
			<div class="wcdr-conferma">
				<div class="wcdr-conferma-icona">&#10004;</div>
				<h3><?php esc_html_e( 'Richiesta inviata con successo!', 'wc-diritto-recesso' ); ?></h3>
				<p><?php esc_html_e( 'La tua richiesta di recesso è stata registrata correttamente. Riceverai a breve un\'email di conferma all\'indirizzo indicato. Il nostro personale esaminerà la richiesta e ti aggiornerà sullo stato.', 'wc-diritto-recesso' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Restituisce il testo di errore in base al codice.
	 *
	 * @param string $codice Codice dell'errore.
	 * @return string Messaggio di errore in italiano.
	 */
	private function testo_errore( $codice ) {
		$messaggi = array(
			'campi_mancanti'  => __( 'Compila tutti i campi obbligatori.', 'wc-diritto-recesso' ),
			'email_invalida'  => __( 'L\'indirizzo email inserito non è valido.', 'wc-diritto-recesso' ),
			'privacy'         => __( 'Devi accettare il trattamento dei dati personali per procedere.', 'wc-diritto-recesso' ),
			'ordine_inesistente' => __( 'Nessun ordine trovato con il numero indicato. Verifica il numero ordine.', 'wc-diritto-recesso' ),
			'email_non_corrisponde' => __( 'L\'email indicata non corrisponde a quella dell\'ordine.', 'wc-diritto-recesso' ),
			'fuori_termine'   => __( 'Il termine di 14 giorni per esercitare il diritto di recesso è scaduto.', 'wc-diritto-recesso' ),
			'sicurezza'       => __( 'Sessione scaduta o non valida. Riprova a inviare il modulo.', 'wc-diritto-recesso' ),
			'generico'        => __( 'Si è verificato un errore durante l\'invio della richiesta. Riprova più tardi.', 'wc-diritto-recesso' ),
		);

		return isset( $messaggi[ $codice ] ) ? $messaggi[ $codice ] : $messaggi['generico'];
	}

	/**
	 * Gestisce l'invio del form di recesso: validazione, salvataggio,
	 * generazione PDF e invio email.
	 */
	public function gestisci_invio() {
		// 1. Verifica del nonce (sicurezza CSRF).
		if ( ! isset( $_POST['wcdr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcdr_nonce'] ) ), 'wcdr_invia_recesso' ) ) {
			$this->reindirizza_errore( 'sicurezza' );
		}

		$pagina_ritorno = isset( $_POST['wcdr_pagina_ritorno'] ) ? esc_url_raw( wp_unslash( $_POST['wcdr_pagina_ritorno'] ) ) : home_url();

		// 2. Raccolta e sanitizzazione dei dati.
		$dati = array(
			'numero_ordine' => isset( $_POST['numero_ordine'] ) ? sanitize_text_field( wp_unslash( $_POST['numero_ordine'] ) ) : '',
			'nome_cliente'  => isset( $_POST['nome_cliente'] ) ? sanitize_text_field( wp_unslash( $_POST['nome_cliente'] ) ) : '',
			'email_cliente' => isset( $_POST['email_cliente'] ) ? sanitize_email( wp_unslash( $_POST['email_cliente'] ) ) : '',
			'data_acquisto' => isset( $_POST['data_acquisto'] ) ? sanitize_text_field( wp_unslash( $_POST['data_acquisto'] ) ) : '',
			'motivo'        => isset( $_POST['motivo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['motivo'] ) ) : '',
		);
		$privacy = isset( $_POST['wcdr_privacy'] ) ? (bool) $_POST['wcdr_privacy'] : false;

		// Salva i dati temporaneamente per ripopolare il form in caso di errore.
		$this->salva_dati_temporanei( $dati );

		// 3. Validazione campi obbligatori.
		if ( '' === $dati['numero_ordine'] || '' === $dati['nome_cliente'] || '' === $dati['email_cliente'] || '' === $dati['data_acquisto'] ) {
			$this->reindirizza_errore( 'campi_mancanti', $pagina_ritorno );
		}

		if ( ! is_email( $dati['email_cliente'] ) ) {
			$this->reindirizza_errore( 'email_invalida', $pagina_ritorno );
		}

		if ( ! $privacy ) {
			$this->reindirizza_errore( 'privacy', $pagina_ritorno );
		}

		// 4. Validazione dell'ordine WooCommerce.
		$ordine = $this->trova_ordine( $dati['numero_ordine'] );
		if ( ! $ordine ) {
			$this->reindirizza_errore( 'ordine_inesistente', $pagina_ritorno );
		}

		// 5. Verifica corrispondenza email con l'ordine.
		$email_ordine = $ordine->get_billing_email();
		if ( $email_ordine && strtolower( $email_ordine ) !== strtolower( $dati['email_cliente'] ) ) {
			$this->reindirizza_errore( 'email_non_corrisponde', $pagina_ritorno );
		}

		// 6. Verifica del termine di 14 giorni dalla data dell'ordine.
		if ( ! $this->entro_termine_recesso( $ordine ) ) {
			$this->reindirizza_errore( 'fuori_termine', $pagina_ritorno );
		}

		// 7. Salvataggio della richiesta nel database.
		$id_richiesta = WCDR_Database::inserisci_richiesta( $dati );
		if ( ! $id_richiesta ) {
			$this->reindirizza_errore( 'generico', $pagina_ritorno );
		}

		// Pulisce i dati temporanei dato che l'invio è riuscito.
		delete_transient( 'wcdr_form_' . $this->chiave_sessione() );

		// 8. Recupera la richiesta completa per PDF ed email.
		$richiesta = WCDR_Database::ottieni_richiesta( $id_richiesta );

		// 9. Generazione del PDF del modulo di recesso.
		$percorso_pdf = WCDR_PDF::genera_modulo( $richiesta );
		if ( $percorso_pdf ) {
			WCDR_Database::aggiorna_file_pdf( $id_richiesta, $percorso_pdf );
			$richiesta->file_pdf = $percorso_pdf;
		}

		// 10. Invio delle notifiche email.
		WCDR_Emails::notifica_cliente_nuova_richiesta( $richiesta );
		WCDR_Emails::notifica_admin_nuova_richiesta( $richiesta );

		// 11. Reindirizza con conferma di successo.
		$url_successo = add_query_arg( 'wcdr_stato', 'successo', $pagina_ritorno );
		wp_safe_redirect( $url_successo );
		exit;
	}

	/**
	 * Trova un ordine WooCommerce a partire dal numero/ID indicato.
	 *
	 * @param string $numero_ordine Numero o ID dell'ordine.
	 * @return WC_Order|false Oggetto ordine, oppure false se non trovato.
	 */
	private function trova_ordine( $numero_ordine ) {
		// Rimuove eventuali caratteri non numerici comuni (es. il prefisso "#").
		$numero_pulito = preg_replace( '/[^0-9]/', '', $numero_ordine );

		if ( '' === $numero_pulito ) {
			return false;
		}

		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}

		$ordine = wc_get_order( (int) $numero_pulito );
		return $ordine ? $ordine : false;
	}

	/**
	 * Verifica se l'ordine è ancora entro il termine di recesso (14 giorni).
	 *
	 * Il termine decorre dalla data di completamento/consegna se disponibile,
	 * altrimenti dalla data di creazione dell'ordine.
	 *
	 * @param WC_Order $ordine Oggetto ordine WooCommerce.
	 * @return bool True se entro i 14 giorni, false altrimenti.
	 */
	private function entro_termine_recesso( $ordine ) {
		// Preferisce la data di completamento (più vicina alla consegna).
		$data_riferimento = $ordine->get_date_completed();
		if ( ! $data_riferimento ) {
			$data_riferimento = $ordine->get_date_created();
		}

		if ( ! $data_riferimento ) {
			// Senza data non possiamo escludere: per prudenza consentiamo.
			return true;
		}

		$timestamp_ordine = $data_riferimento->getTimestamp();
		$giorni_trascorsi = ( current_time( 'timestamp' ) - $timestamp_ordine ) / DAY_IN_SECONDS;

		return $giorni_trascorsi <= WCDR_GIORNI_RECESSO;
	}

	/**
	 * Salva temporaneamente i dati del form (per ripopolamento in caso di errore).
	 *
	 * @param array $dati Dati del form.
	 */
	private function salva_dati_temporanei( $dati ) {
		set_transient( 'wcdr_form_' . $this->chiave_sessione(), $dati, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Genera una chiave di sessione basata sull'utente o sull'IP.
	 *
	 * @return string Chiave per i transient.
	 */
	private function chiave_sessione() {
		if ( is_user_logged_in() ) {
			return 'u' . get_current_user_id();
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'anon';
		return md5( $ip . wp_salt() );
	}

	/**
	 * Reindirizza alla pagina del form mostrando un errore specifico.
	 *
	 * @param string $codice         Codice dell'errore.
	 * @param string $pagina_ritorno URL della pagina del form.
	 */
	private function reindirizza_errore( $codice, $pagina_ritorno = '' ) {
		if ( '' === $pagina_ritorno ) {
			$pagina_ritorno = home_url();
		}
		$url = add_query_arg(
			array(
				'wcdr_stato'  => 'errore',
				'wcdr_errore' => $codice,
			),
			$pagina_ritorno
		);
		wp_safe_redirect( $url );
		exit;
	}
}
