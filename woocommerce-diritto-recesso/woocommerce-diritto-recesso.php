<?php
/**
 * Plugin Name: WooCommerce Diritto di Recesso
 * Plugin URI:  https://github.com/DixLab/54-bis
 * Description: Gestione del diritto di recesso secondo la normativa italiana (art. 54-bis Codice del Consumo) per WooCommerce. Fornisce un modulo frontend per i clienti, area amministrativa, generazione PDF del modulo di recesso e notifiche email.
 * Version:     1.0.0
 * Author:      DixLab
 * Author URI:  https://github.com/DixLab/54-bis
 * Text Domain: wc-diritto-recesso
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 *
 * @package WooCommerce_Diritto_Recesso
 */

// Impedisce l'accesso diretto al file per motivi di sicurezza.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// COSTANTI DEL PLUGIN
// =============================================================================

/** Versione corrente del plugin. */
define( 'WCDR_VERSION', '1.0.0' );

/** Percorso assoluto alla cartella del plugin (con slash finale). */
define( 'WCDR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/** URL alla cartella del plugin (con slash finale). */
define( 'WCDR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/** Percorso assoluto al file principale del plugin. */
define( 'WCDR_PLUGIN_FILE', __FILE__ );

/** Nome della tabella custom (senza prefisso del database). */
define( 'WCDR_TABLE_NAME', 'wcdr_richieste_recesso' );

/** Numero di giorni entro i quali è possibile esercitare il recesso. */
define( 'WCDR_GIORNI_RECESSO', 14 );

// =============================================================================
// CARICAMENTO CLASSI
// =============================================================================

require_once WCDR_PLUGIN_DIR . 'includes/class-wcdr-database.php';
require_once WCDR_PLUGIN_DIR . 'includes/class-wcdr-pdf.php';
require_once WCDR_PLUGIN_DIR . 'includes/class-wcdr-emails.php';
require_once WCDR_PLUGIN_DIR . 'includes/class-wcdr-shortcode.php';
require_once WCDR_PLUGIN_DIR . 'admin/class-wcdr-admin.php';
require_once WCDR_PLUGIN_DIR . 'includes/class-wcdr-plugin.php';

// =============================================================================
// HOOK DI ATTIVAZIONE E DISATTIVAZIONE
// =============================================================================

/**
 * Funzione eseguita all'attivazione del plugin.
 * Crea la tabella custom nel database e imposta le opzioni di default.
 */
function wcdr_attivazione_plugin() {
	WCDR_Database::crea_tabella();

	// Salva la versione del database per future migrazioni.
	update_option( 'wcdr_db_version', WCDR_VERSION );

	// Pulisce la cache delle regole di rewrite.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wcdr_attivazione_plugin' );

/**
 * Funzione eseguita alla disattivazione del plugin.
 * NON elimina i dati: la rimozione avviene solo in fase di disinstallazione
 * (vedi uninstall.php) per evitare perdite accidentali di dati.
 */
function wcdr_disattivazione_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wcdr_disattivazione_plugin' );

// =============================================================================
// AVVIO DEL PLUGIN
// =============================================================================

/**
 * Avvia il plugin dopo che tutti i plugin sono stati caricati.
 * Verifica che WooCommerce sia attivo prima di procedere.
 */
function wcdr_avvia_plugin() {
	// Verifica la presenza di WooCommerce.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wcdr_avviso_woocommerce_mancante' );
		return;
	}

	// Inizializza la classe principale del plugin (pattern Singleton).
	WCDR_Plugin::istanza();
}
add_action( 'plugins_loaded', 'wcdr_avvia_plugin' );

/**
 * Mostra un avviso nell'area amministrativa se WooCommerce non è installato/attivo.
 */
function wcdr_avviso_woocommerce_mancante() {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'WooCommerce Diritto di Recesso', 'wc-diritto-recesso' ); ?></strong>
			<?php esc_html_e( 'richiede WooCommerce per funzionare. Installa e attiva WooCommerce.', 'wc-diritto-recesso' ); ?>
		</p>
	</div>
	<?php
}
