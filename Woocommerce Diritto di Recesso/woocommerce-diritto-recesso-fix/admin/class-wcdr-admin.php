<?php
/**
 * Classe per la gestione dell'area amministrativa.
 *
 * Registra il menu, mostra la lista delle richieste, il dettaglio,
 * gestisce il cambio di stato e il download del PDF.
 *
 * @package WooCommerce_Diritto_Recesso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCDR_Admin
 */
class WCDR_Admin {

	/**
	 * Inizializza gli hook dell'area amministrativa.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'registra_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'carica_asset' ) );

		// Gestione delle azioni admin (cambio stato, download PDF).
		add_action( 'admin_post_wcdr_cambia_stato', array( $this, 'gestisci_cambio_stato' ) );
		add_action( 'admin_post_wcdr_scarica_pdf', array( $this, 'gestisci_download_pdf' ) );
	}

	/**
	 * Registra il menu e i sottomenu nell'area amministrativa.
	 */
	public function registra_menu() {
		$numero_in_attesa = WCDR_Database::conta_richieste( array( 'stato' => 'in_attesa' ) );
		$badge            = $numero_in_attesa > 0 ? ' <span class="awaiting-mod">' . (int) $numero_in_attesa . '</span>' : '';

		add_menu_page(
			__( 'Diritto di Recesso', 'wc-diritto-recesso' ),
			__( 'Diritto Recesso', 'wc-diritto-recesso' ) . $badge,
			'manage_woocommerce',
			'wcdr-richieste',
			array( $this, 'render_pagina' ),
			'dashicons-undo',
			56
		);

		add_submenu_page(
			'wcdr-richieste',
			__( 'Richieste di Recesso', 'wc-diritto-recesso' ),
			__( 'Tutte le richieste', 'wc-diritto-recesso' ),
			'manage_woocommerce',
			'wcdr-richieste',
			array( $this, 'render_pagina' )
		);
	}

	/**
	 * Carica CSS e JS dell'area amministrativa solo nelle pagine del plugin.
	 *
	 * @param string $hook Hook della pagina corrente.
	 */
	public function carica_asset( $hook ) {
		if ( false === strpos( $hook, 'wcdr-richieste' ) ) {
			return;
		}
		wp_enqueue_style(
			'wcdr-admin',
			WCDR_PLUGIN_URL . 'admin/css/wcdr-admin.css',
			array(),
			WCDR_VERSION
		);
	}

	/**
	 * Renderizza la pagina principale: instrada tra lista e dettaglio.
	 */
	public function render_pagina() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'wc-diritto-recesso' ) );
		}

		$azione = isset( $_GET['azione'] ) ? sanitize_text_field( wp_unslash( $_GET['azione'] ) ) : 'lista'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'dettaglio' === $azione ) {
			$this->render_dettaglio();
		} else {
			$this->render_lista();
		}
	}

	/**
	 * Renderizza la lista delle richieste con filtri e ricerca.
	 */
	private function render_lista() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$stato_filtro  = isset( $_GET['stato'] ) ? sanitize_text_field( wp_unslash( $_GET['stato'] ) ) : '';
		$ricerca       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$pagina_corr   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$per_pagina = 20;
		$offset     = ( $pagina_corr - 1 ) * $per_pagina;

		$argomenti = array(
			'stato'   => $stato_filtro,
			'ricerca' => $ricerca,
			'limit'   => $per_pagina,
			'offset'  => $offset,
		);

		$richieste = WCDR_Database::ottieni_richieste( $argomenti );
		$totale    = WCDR_Database::conta_richieste( $argomenti );
		$num_pagine = (int) ceil( $totale / $per_pagina );

		$stati = WCDR_Database::stati_disponibili();

		include WCDR_PLUGIN_DIR . 'admin/views/vista-lista.php';
	}

	/**
	 * Renderizza il dettaglio di una singola richiesta.
	 */
	private function render_dettaglio() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$richiesta = WCDR_Database::ottieni_richiesta( $id );

		if ( ! $richiesta ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Richiesta non trovata', 'wc-diritto-recesso' ) . '</h1>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wcdr-richieste' ) ) . '">' . esc_html__( '« Torna all\'elenco', 'wc-diritto-recesso' ) . '</a></p></div>';
			return;
		}

		$stati = WCDR_Database::stati_disponibili();

		// Recupera l'eventuale ordine WooCommerce collegato.
		$ordine = null;
		if ( function_exists( 'wc_get_order' ) ) {
			$numero_pulito = preg_replace( '/[^0-9]/', '', $richiesta->numero_ordine );
			if ( '' !== $numero_pulito ) {
				$ordine = wc_get_order( (int) $numero_pulito );
			}
		}

		include WCDR_PLUGIN_DIR . 'admin/views/vista-dettaglio.php';
	}

	/**
	 * Gestisce il cambio di stato di una richiesta (con invio email al cliente).
	 */
	public function gestisci_cambio_stato() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti.', 'wc-diritto-recesso' ) );
		}

		// Verifica nonce.
		if ( ! isset( $_POST['wcdr_nonce_stato'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcdr_nonce_stato'] ) ), 'wcdr_cambia_stato' ) ) {
			wp_die( esc_html__( 'Verifica di sicurezza fallita.', 'wc-diritto-recesso' ) );
		}

		$id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$stato = isset( $_POST['stato'] ) ? sanitize_text_field( wp_unslash( $_POST['stato'] ) ) : '';
		$note  = isset( $_POST['note_admin'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note_admin'] ) ) : '';

		$aggiornato = WCDR_Database::aggiorna_stato( $id, $stato, $note );

		if ( $aggiornato ) {
			// Invia la notifica del cambio di stato al cliente.
			$richiesta = WCDR_Database::ottieni_richiesta( $id );
			if ( $richiesta ) {
				WCDR_Emails::notifica_cliente_cambio_stato( $richiesta );
			}
			$messaggio = 'aggiornato';
		} else {
			$messaggio = 'errore';
		}

		$url = add_query_arg(
			array(
				'page'      => 'wcdr-richieste',
				'azione'    => 'dettaglio',
				'id'        => $id,
				'wcdr_msg'  => $messaggio,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Gestisce il download del PDF del modulo di recesso.
	 * Se il PDF non esiste, lo rigenera al volo.
	 */
	public function gestisci_download_pdf() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti.', 'wc-diritto-recesso' ) );
		}

		$id    = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wcdr_scarica_pdf_' . $id ) ) {
			wp_die( esc_html__( 'Verifica di sicurezza fallita.', 'wc-diritto-recesso' ) );
		}

		$richiesta = WCDR_Database::ottieni_richiesta( $id );
		if ( ! $richiesta ) {
			wp_die( esc_html__( 'Richiesta non trovata.', 'wc-diritto-recesso' ) );
		}

		$percorso = $richiesta->file_pdf;

		// Rigenera il PDF se mancante o non più presente su disco.
		if ( empty( $percorso ) || ! file_exists( $percorso ) ) {
			$percorso = WCDR_PDF::genera_modulo( $richiesta );
			if ( $percorso ) {
				WCDR_Database::aggiorna_file_pdf( $id, $percorso );
			}
		}

		if ( ! $percorso || ! file_exists( $percorso ) ) {
			wp_die( esc_html__( 'Impossibile generare il PDF.', 'wc-diritto-recesso' ) );
		}

		// Invia il file al browser come download.
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="modulo-recesso-' . (int) $id . '.pdf"' );
		header( 'Content-Length: ' . filesize( $percorso ) );
		readfile( $percorso ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
}
