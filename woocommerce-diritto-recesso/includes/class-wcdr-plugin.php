<?php
/**
 * Classe principale del plugin (pattern Singleton).
 *
 * Inizializza i componenti del plugin (frontend e admin) e carica
 * il file di traduzione.
 *
 * @package WooCommerce_Diritto_Recesso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCDR_Plugin
 */
class WCDR_Plugin {

	/** @var WCDR_Plugin|null Istanza unica della classe. */
	private static $istanza = null;

	/** @var WCDR_Shortcode Componente frontend (shortcode). */
	public $shortcode;

	/** @var WCDR_Admin Componente area amministrativa. */
	public $admin;

	/**
	 * Restituisce l'istanza unica del plugin (Singleton).
	 *
	 * @return WCDR_Plugin
	 */
	public static function istanza() {
		if ( null === self::$istanza ) {
			self::$istanza = new self();
		}
		return self::$istanza;
	}

	/**
	 * Costruttore privato: inizializza i componenti.
	 */
	private function __construct() {
		// Carica le traduzioni.
		add_action( 'init', array( $this, 'carica_traduzioni' ) );

		// Verifica/aggiorna lo schema del database se necessario.
		add_action( 'plugins_loaded', array( $this, 'verifica_aggiornamento_db' ) );

		// Inizializza il componente frontend (sempre).
		$this->shortcode = new WCDR_Shortcode();

		// Inizializza il componente admin solo in area amministrativa.
		if ( is_admin() ) {
			$this->admin = new WCDR_Admin();
		}
	}

	/**
	 * Carica il file di traduzione del plugin.
	 */
	public function carica_traduzioni() {
		load_plugin_textdomain(
			'wc-diritto-recesso',
			false,
			dirname( plugin_basename( WCDR_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Verifica se occorre aggiornare lo schema del database.
	 * Utile quando il plugin viene aggiornato senza disattivazione/riattivazione.
	 */
	public function verifica_aggiornamento_db() {
		$versione_salvata = get_option( 'wcdr_db_version' );
		if ( $versione_salvata !== WCDR_VERSION ) {
			WCDR_Database::crea_tabella();
			update_option( 'wcdr_db_version', WCDR_VERSION );
		}
	}
}
