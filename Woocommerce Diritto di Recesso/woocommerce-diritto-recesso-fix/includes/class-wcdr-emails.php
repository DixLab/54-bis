<?php
/**
 * Classe per la gestione delle notifiche email.
 *
 * Invia email al cliente e all'amministratore in occasione di:
 * - invio di una nuova richiesta di recesso (cliente + admin);
 * - cambio di stato della richiesta (cliente).
 *
 * @package WooCommerce_Diritto_Recesso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCDR_Emails
 */
class WCDR_Emails {

	/**
	 * Indirizzo email a cui inviare le notifiche di nuova richiesta.
	 *
	 * Può essere sovrascritto definendo la costante WCDR_ADMIN_EMAIL
	 * (ad es. nel wp-config.php) prima del caricamento del plugin.
	 *
	 * @return string Indirizzo email amministrazione.
	 */
	private static function email_amministrazione() {
		if ( defined( 'WCDR_ADMIN_EMAIL' ) && is_email( WCDR_ADMIN_EMAIL ) ) {
			return WCDR_ADMIN_EMAIL;
		}

		return 'info@viridiasrl.it';
	}

	/**
	 * Restituisce le intestazioni standard per l'invio di email HTML.
	 *
	 * @return array Intestazioni email.
	 */
	private static function intestazioni() {
		$nome_mittente  = get_bloginfo( 'name' );
		$email_mittente = get_option( 'admin_email' );

		return array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $nome_mittente, $email_mittente ),
		);
	}

	/**
	 * Avvolge il contenuto in un template HTML di base.
	 *
	 * @param string $titolo    Titolo mostrato nell'email.
	 * @param string $contenuto Contenuto HTML del corpo.
	 * @return string HTML completo dell'email.
	 */
	private static function template( $titolo, $contenuto ) {
		$nome_negozio = get_bloginfo( 'name' );

		ob_start();
		?>
		<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
			<div style="background: #2c3e50; color: #fff; padding: 20px; text-align: center;">
				<h2 style="margin: 0;"><?php echo esc_html( $nome_negozio ); ?></h2>
			</div>
			<div style="padding: 24px; background: #f9f9f9;">
				<h3 style="color: #2c3e50;"><?php echo esc_html( $titolo ); ?></h3>
				<?php echo wp_kses_post( $contenuto ); ?>
			</div>
			<div style="padding: 16px; text-align: center; font-size: 12px; color: #999;">
				<?php esc_html_e( 'Questa è una email automatica relativa alla gestione del diritto di recesso.', 'wc-diritto-recesso' ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Invia la conferma di ricezione della richiesta al cliente.
	 *
	 * @param object $richiesta Oggetto richiesta.
	 * @return bool Esito dell'invio.
	 */
	public static function notifica_cliente_nuova_richiesta( $richiesta ) {
		$oggetto = sprintf(
			/* translators: %s: numero ordine */
			__( 'Richiesta di recesso ricevuta - Ordine %s', 'wc-diritto-recesso' ),
			$richiesta->numero_ordine
		);

		$contenuto  = '<p>' . sprintf(
			/* translators: %s: nome cliente */
			esc_html__( 'Gentile %s,', 'wc-diritto-recesso' ),
			esc_html( $richiesta->nome_cliente )
		) . '</p>';
		$contenuto .= '<p>' . esc_html__( 'abbiamo ricevuto correttamente la sua richiesta di recesso. Di seguito il riepilogo:', 'wc-diritto-recesso' ) . '</p>';
		$contenuto .= self::tabella_riepilogo( $richiesta );
		$contenuto .= '<p>' . esc_html__( 'La sua richiesta verrà esaminata dal nostro personale e riceverà un aggiornamento sullo stato. La preghiamo di conservare questa email.', 'wc-diritto-recesso' ) . '</p>';

		$corpo = self::template( __( 'Richiesta di recesso ricevuta', 'wc-diritto-recesso' ), $contenuto );

		return wp_mail( $richiesta->email_cliente, $oggetto, $corpo, self::intestazioni() );
	}

	/**
	 * Notifica l'amministratore dell'arrivo di una nuova richiesta.
	 *
	 * @param object $richiesta Oggetto richiesta.
	 * @return bool Esito dell'invio.
	 */
	public static function notifica_admin_nuova_richiesta( $richiesta ) {
		$email_admin = self::email_amministrazione();

		$oggetto = sprintf(
			/* translators: %s: numero ordine */
			__( 'Nuova richiesta di recesso - Ordine %s', 'wc-diritto-recesso' ),
			$richiesta->numero_ordine
		);

		$url_dettaglio = admin_url( 'admin.php?page=wcdr-richieste&azione=dettaglio&id=' . (int) $richiesta->id );

		$contenuto  = '<p>' . esc_html__( 'È stata inviata una nuova richiesta di recesso. Di seguito il riepilogo:', 'wc-diritto-recesso' ) . '</p>';
		$contenuto .= self::tabella_riepilogo( $richiesta );
		$contenuto .= '<p><a href="' . esc_url( $url_dettaglio ) . '" style="display:inline-block;background:#2c3e50;color:#fff;padding:10px 18px;text-decoration:none;border-radius:4px;">' . esc_html__( 'Visualizza la richiesta', 'wc-diritto-recesso' ) . '</a></p>';

		$corpo = self::template( __( 'Nuova richiesta di recesso', 'wc-diritto-recesso' ), $contenuto );

		return wp_mail( $email_admin, $oggetto, $corpo, self::intestazioni() );
	}

	/**
	 * Notifica il cliente del cambio di stato della richiesta.
	 *
	 * @param object $richiesta Oggetto richiesta (con stato aggiornato).
	 * @return bool Esito dell'invio.
	 */
	public static function notifica_cliente_cambio_stato( $richiesta ) {
		$stati    = WCDR_Database::stati_disponibili();
		$etichetta = isset( $stati[ $richiesta->stato ] ) ? $stati[ $richiesta->stato ] : $richiesta->stato;

		$oggetto = sprintf(
			/* translators: 1: numero ordine, 2: nuovo stato */
			__( 'Aggiornamento richiesta di recesso - Ordine %1$s (%2$s)', 'wc-diritto-recesso' ),
			$richiesta->numero_ordine,
			$etichetta
		);

		$contenuto  = '<p>' . sprintf(
			/* translators: %s: nome cliente */
			esc_html__( 'Gentile %s,', 'wc-diritto-recesso' ),
			esc_html( $richiesta->nome_cliente )
		) . '</p>';
		$contenuto .= '<p>' . sprintf(
			/* translators: %s: stato della richiesta */
			esc_html__( 'lo stato della sua richiesta di recesso è stato aggiornato a: %s.', 'wc-diritto-recesso' ),
			'<strong>' . esc_html( $etichetta ) . '</strong>'
		) . '</p>';

		// Aggiunge eventuali note dell'amministratore.
		if ( ! empty( $richiesta->note_admin ) ) {
			$contenuto .= '<p><strong>' . esc_html__( 'Note:', 'wc-diritto-recesso' ) . '</strong><br>' . nl2br( esc_html( $richiesta->note_admin ) ) . '</p>';
		}

		$contenuto .= self::tabella_riepilogo( $richiesta );

		$corpo = self::template( __( 'Aggiornamento stato richiesta', 'wc-diritto-recesso' ), $contenuto );

		return wp_mail( $richiesta->email_cliente, $oggetto, $corpo, self::intestazioni() );
	}

	/**
	 * Genera una tabella HTML di riepilogo della richiesta.
	 *
	 * @param object $richiesta Oggetto richiesta.
	 * @return string HTML della tabella.
	 */
	private static function tabella_riepilogo( $richiesta ) {
		$data_acquisto = ! empty( $richiesta->data_acquisto )
			? date_i18n( 'd/m/Y', strtotime( $richiesta->data_acquisto ) )
			: '-';

		$righe = array(
			__( 'Numero ordine', 'wc-diritto-recesso' ) => $richiesta->numero_ordine,
			__( 'Nome', 'wc-diritto-recesso' )          => $richiesta->nome_cliente,
			__( 'Email', 'wc-diritto-recesso' )         => $richiesta->email_cliente,
			__( 'Data acquisto', 'wc-diritto-recesso' ) => $data_acquisto,
			__( 'Motivo', 'wc-diritto-recesso' )        => ! empty( $richiesta->motivo ) ? $richiesta->motivo : '-',
		);

		$html = '<table style="width:100%;border-collapse:collapse;margin:16px 0;">';
		foreach ( $righe as $etichetta => $valore ) {
			$html .= '<tr>';
			$html .= '<td style="padding:8px;border:1px solid #ddd;background:#f0f0f0;font-weight:bold;width:40%;">' . esc_html( $etichetta ) . '</td>';
			$html .= '<td style="padding:8px;border:1px solid #ddd;">' . esc_html( $valore ) . '</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';

		return $html;
	}
}
