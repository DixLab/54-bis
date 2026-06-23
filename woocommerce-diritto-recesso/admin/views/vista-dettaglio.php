<?php
/**
 * Vista: dettaglio di una singola richiesta di recesso (area amministrativa).
 *
 * Variabili disponibili (definite in WCDR_Admin::render_dettaglio):
 *
 * @var object   $richiesta Oggetto richiesta.
 * @var array    $stati     Stati disponibili (codice => etichetta).
 * @var WC_Order $ordine    Ordine WooCommerce collegato (o null).
 *
 * @package WooCommerce_Diritto_Recesso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$url_lista = admin_url( 'admin.php?page=wcdr-richieste' );

// URL per il download del PDF (protetto da nonce).
$url_pdf = wp_nonce_url(
	add_query_arg(
		array(
			'action' => 'wcdr_scarica_pdf',
			'id'     => (int) $richiesta->id,
		),
		admin_url( 'admin-post.php' )
	),
	'wcdr_scarica_pdf_' . (int) $richiesta->id
);

$etichetta_stato = isset( $stati[ $richiesta->stato ] ) ? $stati[ $richiesta->stato ] : $richiesta->stato;
$data_acquisto   = ! empty( $richiesta->data_acquisto ) ? date_i18n( 'd/m/Y', strtotime( $richiesta->data_acquisto ) ) : '-';

// Messaggio di esito.
$msg = isset( $_GET['wcdr_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['wcdr_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap wcdr-admin">
	<h1 class="wp-heading-inline">
		<?php
		/* translators: %d: ID richiesta */
		printf( esc_html__( 'Richiesta di Recesso #%d', 'wc-diritto-recesso' ), (int) $richiesta->id );
		?>
	</h1>
	<a href="<?php echo esc_url( $url_lista ); ?>" class="page-title-action"><?php esc_html_e( '« Torna all\'elenco', 'wc-diritto-recesso' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( 'aggiornato' === $msg ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Stato aggiornato e cliente notificato via email.', 'wc-diritto-recesso' ); ?></p></div>
	<?php elseif ( 'errore' === $msg ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Si è verificato un errore durante l\'aggiornamento.', 'wc-diritto-recesso' ); ?></p></div>
	<?php endif; ?>

	<div class="wcdr-dettaglio-griglia">

		<!-- Colonna principale: dati della richiesta -->
		<div class="wcdr-dettaglio-principale">
			<div class="wcdr-box">
				<h2><?php esc_html_e( 'Dati della richiesta', 'wc-diritto-recesso' ); ?></h2>
				<table class="wcdr-tabella-dettaglio">
					<tr>
						<th><?php esc_html_e( 'Numero ordine', 'wc-diritto-recesso' ); ?></th>
						<td><?php echo esc_html( $richiesta->numero_ordine ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Nome cliente', 'wc-diritto-recesso' ); ?></th>
						<td><?php echo esc_html( $richiesta->nome_cliente ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Email', 'wc-diritto-recesso' ); ?></th>
						<td><a href="mailto:<?php echo esc_attr( $richiesta->email_cliente ); ?>"><?php echo esc_html( $richiesta->email_cliente ); ?></a></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Data acquisto', 'wc-diritto-recesso' ); ?></th>
						<td><?php echo esc_html( $data_acquisto ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Data richiesta', 'wc-diritto-recesso' ); ?></th>
						<td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $richiesta->data_richiesta ) ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Motivo', 'wc-diritto-recesso' ); ?></th>
						<td><?php echo ! empty( $richiesta->motivo ) ? nl2br( esc_html( $richiesta->motivo ) ) : '<em>' . esc_html__( 'Non specificato', 'wc-diritto-recesso' ) . '</em>'; ?></td>
					</tr>
				</table>
			</div>

			<?php if ( $ordine ) : ?>
				<div class="wcdr-box">
					<h2><?php esc_html_e( 'Dettagli ordine WooCommerce', 'wc-diritto-recesso' ); ?></h2>
					<table class="wcdr-tabella-dettaglio">
						<tr>
							<th><?php esc_html_e( 'Stato ordine', 'wc-diritto-recesso' ); ?></th>
							<td><?php echo esc_html( wc_get_order_status_name( $ordine->get_status() ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Totale', 'wc-diritto-recesso' ); ?></th>
							<td><?php echo wp_kses_post( $ordine->get_formatted_order_total() ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Data ordine', 'wc-diritto-recesso' ); ?></th>
							<td><?php echo esc_html( $ordine->get_date_created() ? $ordine->get_date_created()->date_i18n( 'd/m/Y H:i' ) : '-' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Link ordine', 'wc-diritto-recesso' ); ?></th>
							<td><a href="<?php echo esc_url( $ordine->get_edit_order_url() ); ?>"><?php esc_html_e( 'Apri ordine', 'wc-diritto-recesso' ); ?></a></td>
						</tr>
					</table>
				</div>
			<?php else : ?>
				<div class="wcdr-box">
					<p class="wcdr-nota-avviso"><?php esc_html_e( 'Nessun ordine WooCommerce trovato con questo numero.', 'wc-diritto-recesso' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<!-- Colonna laterale: stato e azioni -->
		<div class="wcdr-dettaglio-laterale">
			<div class="wcdr-box">
				<h2><?php esc_html_e( 'Stato attuale', 'wc-diritto-recesso' ); ?></h2>
				<p><span class="wcdr-badge wcdr-badge-<?php echo esc_attr( $richiesta->stato ); ?>"><?php echo esc_html( $etichetta_stato ); ?></span></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wcdr_cambia_stato">
					<input type="hidden" name="id" value="<?php echo (int) $richiesta->id; ?>">
					<?php wp_nonce_field( 'wcdr_cambia_stato', 'wcdr_nonce_stato' ); ?>

					<p>
						<label for="wcdr_stato"><strong><?php esc_html_e( 'Cambia stato:', 'wc-diritto-recesso' ); ?></strong></label><br>
						<select name="stato" id="wcdr_stato" class="widefat">
							<?php foreach ( $stati as $codice => $etichetta ) : ?>
								<option value="<?php echo esc_attr( $codice ); ?>" <?php selected( $richiesta->stato, $codice ); ?>>
									<?php echo esc_html( $etichetta ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label for="wcdr_note"><strong><?php esc_html_e( 'Note (incluse nell\'email al cliente):', 'wc-diritto-recesso' ); ?></strong></label><br>
						<textarea name="note_admin" id="wcdr_note" rows="4" class="widefat"><?php echo esc_textarea( $richiesta->note_admin ); ?></textarea>
					</p>

					<p>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Aggiorna e notifica cliente', 'wc-diritto-recesso' ); ?></button>
					</p>
				</form>
			</div>

			<div class="wcdr-box">
				<h2><?php esc_html_e( 'Modulo PDF', 'wc-diritto-recesso' ); ?></h2>
				<p><?php esc_html_e( 'Scarica il modulo di recesso conforme alla normativa 54-bis.', 'wc-diritto-recesso' ); ?></p>
				<a href="<?php echo esc_url( $url_pdf ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
					<?php esc_html_e( 'Scarica PDF', 'wc-diritto-recesso' ); ?>
				</a>
			</div>
		</div>

	</div>
</div>
