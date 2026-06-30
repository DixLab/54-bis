<?php
/**
 * Vista: elenco delle richieste di recesso (area amministrativa).
 *
 * Variabili disponibili (definite in WCDR_Admin::render_lista):
 *
 * @var array  $richieste    Elenco delle richieste.
 * @var int    $totale       Numero totale di richieste filtrate.
 * @var int    $num_pagine   Numero di pagine.
 * @var int    $pagina_corr  Pagina corrente.
 * @var string $stato_filtro Stato selezionato come filtro.
 * @var string $ricerca      Termine di ricerca.
 * @var array  $stati        Stati disponibili (codice => etichetta).
 *
 * @package WooCommerce_Diritto_Recesso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wcdr-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Richieste di Recesso', 'wc-diritto-recesso' ); ?></h1>
	<hr class="wp-header-end">

	<?php
	// Mostra eventuale messaggio di esito proveniente dal dettaglio.
	$msg = isset( $_GET['wcdr_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['wcdr_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'aggiornato' === $msg ) :
		?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Stato della richiesta aggiornato correttamente.', 'wc-diritto-recesso' ); ?></p></div>
	<?php endif; ?>

	<!-- Filtri di stato (tab) -->
	<ul class="subsubsub">
		<?php
		$totale_tutte = WCDR_Database::conta_richieste();
		$url_base     = admin_url( 'admin.php?page=wcdr-richieste' );
		?>
		<li>
			<a href="<?php echo esc_url( $url_base ); ?>" class="<?php echo '' === $stato_filtro ? 'current' : ''; ?>">
				<?php esc_html_e( 'Tutte', 'wc-diritto-recesso' ); ?> <span class="count">(<?php echo (int) $totale_tutte; ?>)</span>
			</a> |
		</li>
		<?php
		$i = 0;
		$n_stati = count( $stati );
		foreach ( $stati as $codice => $etichetta ) :
			$i++;
			$conteggio = WCDR_Database::conta_richieste( array( 'stato' => $codice ) );
			$url_stato = add_query_arg( 'stato', $codice, $url_base );
			?>
			<li>
				<a href="<?php echo esc_url( $url_stato ); ?>" class="<?php echo $stato_filtro === $codice ? 'current' : ''; ?>">
					<?php echo esc_html( $etichetta ); ?> <span class="count">(<?php echo (int) $conteggio; ?>)</span>
				</a><?php echo $i < $n_stati ? ' |' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<!-- Form di ricerca -->
	<form method="get" class="wcdr-ricerca-form">
		<input type="hidden" name="page" value="wcdr-richieste">
		<?php if ( '' !== $stato_filtro ) : ?>
			<input type="hidden" name="stato" value="<?php echo esc_attr( $stato_filtro ); ?>">
		<?php endif; ?>
		<p class="search-box">
			<label class="screen-reader-text" for="wcdr-search-input"><?php esc_html_e( 'Cerca richieste', 'wc-diritto-recesso' ); ?></label>
			<input type="search" id="wcdr-search-input" name="s" value="<?php echo esc_attr( $ricerca ); ?>" placeholder="<?php esc_attr_e( 'Cerca ordine, nome o email...', 'wc-diritto-recesso' ); ?>">
			<input type="submit" class="button" value="<?php esc_attr_e( 'Cerca', 'wc-diritto-recesso' ); ?>">
		</p>
	</form>

	<!-- Tabella delle richieste -->
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'ID', 'wc-diritto-recesso' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Ordine', 'wc-diritto-recesso' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Cliente', 'wc-diritto-recesso' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Email', 'wc-diritto-recesso' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Data richiesta', 'wc-diritto-recesso' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Stato', 'wc-diritto-recesso' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Azioni', 'wc-diritto-recesso' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $richieste ) ) : ?>
				<tr>
					<td colspan="7"><?php esc_html_e( 'Nessuna richiesta di recesso trovata.', 'wc-diritto-recesso' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $richieste as $r ) : ?>
					<?php
					$url_dettaglio = add_query_arg(
						array(
							'page'   => 'wcdr-richieste',
							'azione' => 'dettaglio',
							'id'     => (int) $r->id,
						),
						admin_url( 'admin.php' )
					);
					$etichetta_stato = isset( $stati[ $r->stato ] ) ? $stati[ $r->stato ] : $r->stato;
					?>
					<tr>
						<td>#<?php echo (int) $r->id; ?></td>
						<td><strong><?php echo esc_html( $r->numero_ordine ); ?></strong></td>
						<td><?php echo esc_html( $r->nome_cliente ); ?></td>
						<td><?php echo esc_html( $r->email_cliente ); ?></td>
						<td><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $r->data_richiesta ) ) ); ?></td>
						<td><span class="wcdr-badge wcdr-badge-<?php echo esc_attr( $r->stato ); ?>"><?php echo esc_html( $etichetta_stato ); ?></span></td>
						<td>
							<a href="<?php echo esc_url( $url_dettaglio ); ?>" class="button button-small"><?php esc_html_e( 'Dettaglio', 'wc-diritto-recesso' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Paginazione -->
	<?php if ( $num_pagine > 1 ) : ?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					/* translators: %s: numero totale di richieste */
					printf( esc_html__( '%s elementi', 'wc-diritto-recesso' ), (int) $totale );
					?>
				</span>
				<span class="pagination-links">
					<?php
					$args_base = array( 'page' => 'wcdr-richieste' );
					if ( '' !== $stato_filtro ) {
						$args_base['stato'] = $stato_filtro;
					}
					if ( '' !== $ricerca ) {
						$args_base['s'] = $ricerca;
					}
					for ( $p = 1; $p <= $num_pagine; $p++ ) {
						$args_base['paged'] = $p;
						$url_p              = add_query_arg( $args_base, admin_url( 'admin.php' ) );
						if ( $p === $pagina_corr ) {
							echo '<span class="button button-small button-primary" style="margin:0 2px;">' . (int) $p . '</span>';
						} else {
							echo '<a class="button button-small" style="margin:0 2px;" href="' . esc_url( $url_p ) . '">' . (int) $p . '</a>';
						}
					}
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>
</div>
