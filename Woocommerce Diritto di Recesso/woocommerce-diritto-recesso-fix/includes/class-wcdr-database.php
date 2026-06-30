<?php
/**
 * Classe per la gestione del database del plugin.
 *
 * Si occupa di creare la tabella custom, inserire/aggiornare/leggere
 * le richieste di recesso.
 *
 * @package WooCommerce_Diritto_Recesso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCDR_Database
 */
class WCDR_Database {

	/**
	 * Restituisce il nome completo della tabella (con prefisso del database).
	 *
	 * @return string Nome completo della tabella.
	 */
	public static function nome_tabella() {
		global $wpdb;
		return $wpdb->prefix . WCDR_TABLE_NAME;
	}

	/**
	 * Crea la tabella custom nel database.
	 * Utilizza dbDelta() per gestire creazione e aggiornamenti dello schema.
	 */
	public static function crea_tabella() {
		global $wpdb;

		$nome_tabella     = self::nome_tabella();
		$charset_collate  = $wpdb->get_charset_collate();

		// Definizione dello schema della tabella.
		$sql = "CREATE TABLE {$nome_tabella} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			numero_ordine VARCHAR(50) NOT NULL,
			nome_cliente VARCHAR(255) NOT NULL,
			email_cliente VARCHAR(255) NOT NULL,
			motivo TEXT NULL,
			data_acquisto DATE NULL,
			data_richiesta DATETIME NOT NULL,
			stato VARCHAR(20) NOT NULL DEFAULT 'in_attesa',
			note_admin TEXT NULL,
			file_pdf VARCHAR(255) NULL,
			PRIMARY KEY  (id),
			KEY numero_ordine (numero_ordine),
			KEY stato (stato),
			KEY email_cliente (email_cliente)
		) {$charset_collate};";

		// dbDelta richiede questo file di WordPress.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Elimina la tabella custom dal database.
	 * Usata in fase di disinstallazione del plugin.
	 */
	public static function elimina_tabella() {
		global $wpdb;
		$nome_tabella = self::nome_tabella();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$nome_tabella}" );
	}

	/**
	 * Inserisce una nuova richiesta di recesso.
	 *
	 * @param array $dati Dati della richiesta.
	 * @return int|false ID della richiesta inserita, oppure false in caso di errore.
	 */
	public static function inserisci_richiesta( $dati ) {
		global $wpdb;

		$inserito = $wpdb->insert(
			self::nome_tabella(),
			array(
				'numero_ordine'  => sanitize_text_field( $dati['numero_ordine'] ),
				'nome_cliente'   => sanitize_text_field( $dati['nome_cliente'] ),
				'email_cliente'  => sanitize_email( $dati['email_cliente'] ),
				'motivo'         => sanitize_textarea_field( $dati['motivo'] ),
				'data_acquisto'  => ! empty( $dati['data_acquisto'] ) ? $dati['data_acquisto'] : null,
				'data_richiesta' => current_time( 'mysql' ),
				'stato'          => 'in_attesa',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserito ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Aggiorna il percorso del file PDF associato a una richiesta.
	 *
	 * @param int    $id        ID della richiesta.
	 * @param string $file_pdf  Percorso/URL del file PDF.
	 * @return bool Esito dell'aggiornamento.
	 */
	public static function aggiorna_file_pdf( $id, $file_pdf ) {
		global $wpdb;
		return (bool) $wpdb->update(
			self::nome_tabella(),
			array( 'file_pdf' => $file_pdf ),
			array( 'id' => (int) $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Aggiorna lo stato di una richiesta.
	 *
	 * @param int    $id    ID della richiesta.
	 * @param string $stato Nuovo stato (in_attesa|approvata|rifiutata|completata).
	 * @param string $note  Note opzionali dell'amministratore.
	 * @return bool Esito dell'aggiornamento.
	 */
	public static function aggiorna_stato( $id, $stato, $note = '' ) {
		global $wpdb;

		$stati_validi = array_keys( self::stati_disponibili() );
		if ( ! in_array( $stato, $stati_validi, true ) ) {
			return false;
		}

		return (bool) $wpdb->update(
			self::nome_tabella(),
			array(
				'stato'      => $stato,
				'note_admin' => sanitize_textarea_field( $note ),
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Recupera una singola richiesta tramite ID.
	 *
	 * @param int $id ID della richiesta.
	 * @return object|null Oggetto richiesta, oppure null se non trovata.
	 */
	public static function ottieni_richiesta( $id ) {
		global $wpdb;
		$nome_tabella = self::nome_tabella();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$nome_tabella} WHERE id = %d", (int) $id )
		);
	}

	/**
	 * Recupera l'elenco delle richieste con filtri opzionali.
	 *
	 * @param array $argomenti Argomenti di filtro (stato, ricerca, orderby, order, limit, offset).
	 * @return array Elenco di oggetti richiesta.
	 */
	public static function ottieni_richieste( $argomenti = array() ) {
		global $wpdb;
		$nome_tabella = self::nome_tabella();

		$predefiniti = array(
			'stato'   => '',
			'ricerca' => '',
			'orderby' => 'data_richiesta',
			'order'   => 'DESC',
			'limit'   => 20,
			'offset'  => 0,
		);
		$argomenti = wp_parse_args( $argomenti, $predefiniti );

		$where  = ' WHERE 1=1';
		$valori = array();

		// Filtro per stato.
		if ( ! empty( $argomenti['stato'] ) ) {
			$where   .= ' AND stato = %s';
			$valori[] = $argomenti['stato'];
		}

		// Filtro di ricerca testuale (numero ordine, nome, email).
		if ( ! empty( $argomenti['ricerca'] ) ) {
			$like     = '%' . $wpdb->esc_like( $argomenti['ricerca'] ) . '%';
			$where   .= ' AND ( numero_ordine LIKE %s OR nome_cliente LIKE %s OR email_cliente LIKE %s )';
			$valori[] = $like;
			$valori[] = $like;
			$valori[] = $like;
		}

		// Whitelist delle colonne per l'ordinamento (sicurezza).
		$colonne_valide = array( 'id', 'numero_ordine', 'nome_cliente', 'data_richiesta', 'stato' );
		$orderby        = in_array( $argomenti['orderby'], $colonne_valide, true ) ? $argomenti['orderby'] : 'data_richiesta';
		$order          = ( 'ASC' === strtoupper( $argomenti['order'] ) ) ? 'ASC' : 'DESC';

		$sql  = "SELECT * FROM {$nome_tabella}" . $where;
		$sql .= " ORDER BY {$orderby} {$order}";
		$sql .= ' LIMIT %d OFFSET %d';
		$valori[] = (int) $argomenti['limit'];
		$valori[] = (int) $argomenti['offset'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $valori ) );
	}

	/**
	 * Conta il numero totale di richieste con filtri opzionali.
	 *
	 * @param array $argomenti Argomenti di filtro (stato, ricerca).
	 * @return int Numero totale di richieste.
	 */
	public static function conta_richieste( $argomenti = array() ) {
		global $wpdb;
		$nome_tabella = self::nome_tabella();

		$where  = ' WHERE 1=1';
		$valori = array();

		if ( ! empty( $argomenti['stato'] ) ) {
			$where   .= ' AND stato = %s';
			$valori[] = $argomenti['stato'];
		}

		if ( ! empty( $argomenti['ricerca'] ) ) {
			$like     = '%' . $wpdb->esc_like( $argomenti['ricerca'] ) . '%';
			$where   .= ' AND ( numero_ordine LIKE %s OR nome_cliente LIKE %s OR email_cliente LIKE %s )';
			$valori[] = $like;
			$valori[] = $like;
			$valori[] = $like;
		}

		$sql = "SELECT COUNT(*) FROM {$nome_tabella}" . $where;

		if ( empty( $valori ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $sql );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $valori ) );
	}

	/**
	 * Restituisce l'elenco degli stati disponibili con relative etichette in italiano.
	 *
	 * @return array Associazione codice => etichetta.
	 */
	public static function stati_disponibili() {
		return array(
			'in_attesa'  => __( 'In attesa', 'wc-diritto-recesso' ),
			'approvata'  => __( 'Approvata', 'wc-diritto-recesso' ),
			'rifiutata'  => __( 'Rifiutata', 'wc-diritto-recesso' ),
			'completata' => __( 'Completata', 'wc-diritto-recesso' ),
		);
	}
}
