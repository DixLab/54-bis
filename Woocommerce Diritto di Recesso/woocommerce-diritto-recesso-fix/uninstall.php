<?php
/**
 * File di disinstallazione del plugin.
 *
 * Viene eseguito automaticamente da WordPress quando il plugin viene
 * ELIMINATO (non semplicemente disattivato). Rimuove la tabella custom,
 * le opzioni e i file PDF generati.
 *
 * @package WooCommerce_Diritto_Recesso
 */

// Sicurezza: questo file deve essere chiamato solo da WordPress in fase di uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Elimina la tabella custom delle richieste.
$nome_tabella = $wpdb->prefix . 'wcdr_richieste_recesso';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$nome_tabella}" );

// 2. Elimina le opzioni salvate dal plugin.
delete_option( 'wcdr_db_version' );

// 3. Elimina la cartella dei PDF generati (con tutti i file all'interno).
$uploads = wp_upload_dir();
if ( empty( $uploads['error'] ) ) {
	$dir = trailingslashit( $uploads['basedir'] ) . 'wcdr-moduli-recesso';
	if ( is_dir( $dir ) ) {
		$file = glob( trailingslashit( $dir ) . '*' );
		if ( is_array( $file ) ) {
			foreach ( $file as $f ) {
				if ( is_file( $f ) ) {
					wp_delete_file( $f );
				}
			}
		}
		// Rimuove la directory ora vuota.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		@rmdir( $dir );
	}
}
