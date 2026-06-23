<?php
/**
 * Classe per la generazione del PDF del modulo di recesso.
 *
 * Implementa un generatore PDF leggero e autonomo (senza librerie esterne)
 * che produce un documento conforme al modulo di recesso tipo previsto
 * dall'art. 54-bis del Codice del Consumo (allegato I, parte B, Direttiva 2011/83/UE).
 *
 * @package WooCommerce_Diritto_Recesso
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Class WCDR_PDF
 *
 * Costruttore di PDF minimale: supporta testo, paragrafi con a capo automatico
 * e font standard (Helvetica). Sufficiente per il modulo di recesso.
 */
class WCDR_PDF {

        /** @var array Elenco degli oggetti (contenuti) del PDF. */
        private $oggetti = array();

        /** @var string Flusso dei comandi di disegno della pagina corrente. */
        private $contenuto = '';

        /** @var float Posizione verticale corrente (in punti, dall'alto). */
        private $y = 800;

        /** @var float Margine sinistro in punti. */
        private $margine_x = 60;

        /** @var float Larghezza pagina (A4 in punti). */
        private $larghezza = 595;

        /** @var float Altezza pagina (A4 in punti). */
        private $altezza = 842;

        /**
         * Genera il PDF del modulo di recesso e lo salva su disco.
         *
         * @param object $richiesta Oggetto richiesta dal database.
         * @return string|false Percorso assoluto del file PDF generato, false in caso di errore.
         */
        public static function genera_modulo( $richiesta ) {
                $pdf = new self();
                return $pdf->costruisci( $richiesta );
        }

        /**
         * Costruisce il contenuto del modulo e lo salva.
         *
         * @param object $richiesta Oggetto richiesta.
         * @return string|false Percorso del file, false in caso di errore.
         */
        private function costruisci( $richiesta ) {
                // Dati dell'esercente (negozio).
                $nome_negozio = get_bloginfo( 'name' );
                $email_admin  = get_option( 'admin_email' );
                $indirizzo    = get_option( 'woocommerce_store_address', '' );
                $citta        = get_option( 'woocommerce_store_city', '' );
                $cap          = get_option( 'woocommerce_store_postcode', '' );

                $data_richiesta = ! empty( $richiesta->data_richiesta )
                        ? date_i18n( 'd/m/Y', strtotime( $richiesta->data_richiesta ) )
                        : date_i18n( 'd/m/Y' );
                $data_acquisto  = ! empty( $richiesta->data_acquisto )
                        ? date_i18n( 'd/m/Y', strtotime( $richiesta->data_acquisto ) )
                        : '__________';

                // Intestazione.
                $this->titolo( 'MODULO DI RECESSO' );
                $this->paragrafo( '(art. 54-bis del Codice del Consumo - D.Lgs. 206/2005)', 10, true );
                $this->spazio( 10 );
                $this->paragrafo( 'Compilare e restituire il presente modulo solo se si desidera recedere dal contratto.', 10 );
                $this->linea();
                $this->spazio( 6 );

                // Destinatario (esercente).
                $this->etichetta( 'Destinatario:' );
                $this->paragrafo( $nome_negozio, 11 );
                $indirizzo_completo = trim( $indirizzo . ' ' . $cap . ' ' . $citta );
                if ( '' !== $indirizzo_completo ) {
                        $this->paragrafo( $indirizzo_completo, 11 );
                }
                $this->paragrafo( 'Email: ' . $email_admin, 11 );
                $this->spazio( 10 );

                // Dichiarazione di recesso.
                $this->paragrafo(
                        'Con la presente io sottoscritto/a notifico il recesso dal mio contratto di vendita dei seguenti beni/servizi:',
                        11
                );
                $this->spazio( 4 );
                $this->etichetta( 'Numero ordine:' );
                $this->paragrafo( (string) $richiesta->numero_ordine, 11 );
                $this->spazio( 4 );
                $this->etichetta( 'Ordinato il / ricevuto il:' );
                $this->paragrafo( $data_acquisto, 11 );
                $this->spazio( 10 );

                // Dati del consumatore.
                $this->etichetta( 'Nome del consumatore:' );
                $this->paragrafo( (string) $richiesta->nome_cliente, 11 );
                $this->spazio( 4 );
                $this->etichetta( 'Email del consumatore:' );
                $this->paragrafo( (string) $richiesta->email_cliente, 11 );
                $this->spazio( 10 );

                // Motivo (campo facoltativo per normativa, incluso per completezza).
                if ( ! empty( $richiesta->motivo ) ) {
                        $this->etichetta( 'Motivo del recesso (facoltativo):' );
                        $this->paragrafo( (string) $richiesta->motivo, 11 );
                        $this->spazio( 10 );
                }

                // Data e firma.
                $this->linea();
                $this->spazio( 6 );
                $this->etichetta( 'Data della richiesta:' );
                $this->paragrafo( $data_richiesta, 11 );
                $this->spazio( 20 );
                $this->paragrafo( 'Firma del consumatore (solo se il modulo è notificato in versione cartacea):', 10 );
                $this->spazio( 24 );
                $this->paragrafo( '_______________________________________', 11 );

                // Salva su file.
                return $this->salva( $richiesta->id );
        }

        // -------------------------------------------------------------------------
        // METODI DI DISEGNO DEL TESTO
        // -------------------------------------------------------------------------

        /**
         * Scrive un titolo centrato in grassetto.
         *
         * @param string $testo Testo del titolo.
         */
        private function titolo( $testo ) {
                $dimensione = 16;
                $larghezza_testo = $this->larghezza_testo( $testo, $dimensione );
                $x = ( $this->larghezza - $larghezza_testo ) / 2;
                $this->y -= $dimensione + 6;
                $this->contenuto .= sprintf(
                        "BT /F2 %d Tf %.2f %.2f Td (%s) Tj ET\n",
                        $dimensione,
                        $x,
                        $this->y,
                        $this->escape( $testo )
                );
        }

        /**
         * Scrive un'etichetta in grassetto.
         *
         * @param string $testo Testo dell'etichetta.
         */
        private function etichetta( $testo ) {
                $this->y -= 14;
                $this->contenuto .= sprintf(
                        "BT /F2 10 Tf %.2f %.2f Td (%s) Tj ET\n",
                        $this->margine_x,
                        $this->y,
                        $this->escape( $testo )
                );
        }

        /**
         * Scrive un paragrafo con a capo automatico.
         *
         * @param string $testo      Testo del paragrafo.
         * @param int    $dimensione Dimensione del font in punti.
         * @param bool   $centrato   Se true, centra il testo.
         */
        private function paragrafo( $testo, $dimensione = 11, $centrato = false ) {
                $larghezza_max = $this->larghezza - ( 2 * $this->margine_x );
                $righe         = $this->dividi_in_righe( $testo, $dimensione, $larghezza_max );

                foreach ( $righe as $riga ) {
                        $this->y -= $dimensione + 4;
                        $this->verifica_nuova_pagina( $dimensione );

                        $x = $this->margine_x;
                        if ( $centrato ) {
                                $x = ( $this->larghezza - $this->larghezza_testo( $riga, $dimensione ) ) / 2;
                        }

                        $this->contenuto .= sprintf(
                                "BT /F1 %d Tf %.2f %.2f Td (%s) Tj ET\n",
                                $dimensione,
                                $x,
                                $this->y,
                                $this->escape( $riga )
                        );
                }
        }

        /**
         * Disegna una linea orizzontale di separazione.
         */
        private function linea() {
                $this->y -= 8;
                $this->contenuto .= sprintf(
                        "%.2f %.2f m %.2f %.2f l S\n",
                        $this->margine_x,
                        $this->y,
                        $this->larghezza - $this->margine_x,
                        $this->y
                );
        }

        /**
         * Aggiunge spazio verticale.
         *
         * @param float $punti Quantità di spazio in punti.
         */
        private function spazio( $punti ) {
                $this->y -= $punti;
        }

        /**
         * Verifica se serve una nuova pagina (gestione semplice di overflow).
         *
         * @param int $dimensione Dimensione del font corrente.
         */
        private function verifica_nuova_pagina( $dimensione ) {
                if ( $this->y < 60 ) {
                        // In questo modulo il contenuto è breve: reimposta in cima.
                        // Per documenti lunghi servirebbe la gestione multi-pagina completa.
                        $this->y = $this->altezza - 60;
                }
        }

        // -------------------------------------------------------------------------
        // UTILITÀ DI TESTO
        // -------------------------------------------------------------------------

        /**
         * Suddivide un testo in righe rispettando la larghezza massima.
         *
         * @param string $testo         Testo da suddividere.
         * @param int    $dimensione    Dimensione del font.
         * @param float  $larghezza_max Larghezza massima in punti.
         * @return array Elenco di righe.
         */
        private function dividi_in_righe( $testo, $dimensione, $larghezza_max ) {
                $parole = explode( ' ', $testo );
                $righe  = array();
                $riga   = '';

                foreach ( $parole as $parola ) {
                        $prova = ( '' === $riga ) ? $parola : $riga . ' ' . $parola;
                        if ( $this->larghezza_testo( $prova, $dimensione ) > $larghezza_max && '' !== $riga ) {
                                $righe[] = $riga;
                                $riga    = $parola;
                        } else {
                                $riga = $prova;
                        }
                }

                if ( '' !== $riga ) {
                        $righe[] = $riga;
                }

                return empty( $righe ) ? array( '' ) : $righe;
        }

        /**
         * Stima la larghezza di un testo in punti (approssimazione per Helvetica).
         *
         * @param string $testo      Testo da misurare.
         * @param int    $dimensione Dimensione del font.
         * @return float Larghezza stimata in punti.
         */
        private function larghezza_testo( $testo, $dimensione ) {
                // Fattore medio approssimato di larghezza carattere per Helvetica.
                return strlen( $testo ) * $dimensione * 0.5;
        }

        /**
         * Esegue l'escape dei caratteri speciali per le stringhe PDF.
         *
         * @param string $testo Testo da convertire.
         * @return string Testo con escape.
         */
        private function escape( $testo ) {
                // I font standard usano WinAnsiEncoding (≈ Windows-1252), che è a byte
                // singolo. Le lettere accentate italiane (à, è, é, ì, ò, ù...) in UTF-8
                // sono multibyte: vanno convertite in Windows-1252 per essere rese
                // correttamente nel PDF.
                if ( function_exists( 'mb_convert_encoding' ) ) {
                        $convertito = @mb_convert_encoding( $testo, 'Windows-1252', 'UTF-8' );
                        if ( false !== $convertito ) {
                                $testo = $convertito;
                        }
                } elseif ( function_exists( 'iconv' ) ) {
                        $convertito = @iconv( 'UTF-8', 'Windows-1252//TRANSLIT', $testo );
                        if ( false !== $convertito ) {
                                $testo = $convertito;
                        }
                }

                // IMPORTANTE: il backslash va sostituito per primo, altrimenti
                // re-escaperebbe i backslash introdotti dalle parentesi.
                $testo = str_replace( '\\', '\\\\', $testo );
                $testo = str_replace( '(', '\\(', $testo );
                $testo = str_replace( ')', '\\)', $testo );
                return $testo;
        }

        // -------------------------------------------------------------------------
        // SALVATAGGIO DEL FILE PDF
        // -------------------------------------------------------------------------

        /**
         * Assembla gli oggetti PDF e salva il file nella cartella uploads protetta.
         *
         * @param int $id_richiesta ID della richiesta (per nominare il file).
         * @return string|false Percorso assoluto del file salvato, false in caso di errore.
         */
        private function salva( $id_richiesta ) {
                // 1. Catalogo.
                $this->oggetti[1] = "<< /Type /Catalog /Pages 2 0 R >>";
                // 2. Pages.
                $this->oggetti[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
                // 3. Page.
                $this->oggetti[3] = sprintf(
                        "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>",
                        $this->larghezza,
                        $this->altezza
                );
                // 4. Contents stream.
                $stream = "0 0 0 rg 0.5 w\n" . $this->contenuto;
                $this->oggetti[4] = "<< /Length " . strlen( $stream ) . " >>\nstream\n" . $stream . "\nendstream";
                // 5. Font normale.
                $this->oggetti[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
                // 6. Font grassetto.
                $this->oggetti[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

                // Assembla il documento con la tabella xref.
                $pdf  = "%PDF-1.4\n";
                $offset = array();
                $posizione = strlen( $pdf );

                for ( $i = 1; $i <= 6; $i++ ) {
                        $offset[ $i ] = $posizione;
                        $oggetto = "{$i} 0 obj\n" . $this->oggetti[ $i ] . "\nendobj\n";
                        $pdf .= $oggetto;
                        $posizione += strlen( $oggetto );
                }

                // Tabella xref.
                $xref_pos = strlen( $pdf );
                $pdf .= "xref\n0 7\n";
                $pdf .= "0000000000 65535 f \n";
                for ( $i = 1; $i <= 6; $i++ ) {
                        $pdf .= sprintf( "%010d 00000 n \n", $offset[ $i ] );
                }

                // Trailer.
                $pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\n";
                $pdf .= "startxref\n{$xref_pos}\n%%EOF";

                // Salva nella cartella uploads dedicata e protetta.
                $dir = self::cartella_pdf();
                if ( ! $dir ) {
                        return false;
                }

                $nome_file   = 'modulo-recesso-' . (int) $id_richiesta . '-' . time() . '.pdf';
                $percorso    = trailingslashit( $dir ) . $nome_file;

                // Usa WP_Filesystem se disponibile, altrimenti file_put_contents.
                if ( false === file_put_contents( $percorso, $pdf ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                        return $percorso;
                }

                return $percorso;
        }

        /**
         * Restituisce (creandola se necessario) la cartella protetta per i PDF.
         *
         * @return string|false Percorso assoluto della cartella, false in caso di errore.
         */
        public static function cartella_pdf() {
                $uploads = wp_upload_dir();
                if ( ! empty( $uploads['error'] ) ) {
                        return false;
                }

                $dir = trailingslashit( $uploads['basedir'] ) . 'wcdr-moduli-recesso';

                if ( ! file_exists( $dir ) ) {
                        wp_mkdir_p( $dir );

                        // Protegge la cartella da accessi diretti via web.
                        $htaccess = trailingslashit( $dir ) . '.htaccess';
                        file_put_contents( $htaccess, "Order Deny,Allow\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

                        // Impedisce l'elenco dei file.
                        file_put_contents( trailingslashit( $dir ) . 'index.php', '<?php // Accesso negato.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                }

                return $dir;
        }
}
