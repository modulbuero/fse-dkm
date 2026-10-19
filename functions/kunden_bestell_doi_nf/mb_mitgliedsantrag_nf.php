<?php
/*
*	Plugin Name: Digitaler Mitgliedsantrag
* 	Description: In Zusammenarbeit mit dem Formular-Plugin Ninjaforms. Weitere Hinweise siehe...
*
*/
if(!defined('ABSPATH')) exit;

class NF_Member_Registration {

    private $target_form = 'Hosting Bestellung';

    /**
     * Feldkonfiguration – neues Feld = neuer Array-Eintrag, fertig.
     *
     * Optionen pro Feld:
     *   label       – Anzeigename im Profil
     *   section     – Gruppenüberschrift (erste Zeile einer neuen Gruppe)
     *   type        – 'text' | 'date' | 'select'
     *   sanitize    – 'text' | 'iban' | 'bic' | 'date' | 'select'
     *   options     – nur bei type=select: ['value' => 'Label', ...]
     *   maxlength   – optional, für type=text
     *   placeholder – optional
     *   form_key    – Feldname (key) im Ninja-Formular
     */
    private $custom_fields = [

        // ── Persönliche Daten ─────────────────────────────────────────────────
        'nf_plz' => [
            'label'       => 'PLZ',
            'type'        => 'text',
            'sanitize'    => 'text',
            'maxlength'   => 6,
            'placeholder' => '',
            'form_key'    => 'postleitzahl',
        ],
        'nf_ort' => [
            'label'       => 'Kundennummer',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '987654',
            'form_key'    => 'kundennummer',
        ],

        'nf_name' => [
            'label'       => 'Name',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'name',
        ],
        'nf_funktion' => [
            'label'       => 'Funktion',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'funktion',
        ],
        'nf_email' => [
            'label'       => 'Email',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'e-mail_fuer_die_bestaetigung',
        ],
        'nf_paket' => [
            'label'       => 'Paket',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'paket',
        ],
        'nf_wartung' => [
            'label'       => 'Wartung',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'wartung',
        ],
        'nf_adresse_der_webseite' => [
            'label'       => 'Webseite',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'adresse_der_webseite',
        ], 
        // ── Bankverbindung ────────────────────────────────────────────────────
        'nf_iban' => [
            'label'       => 'IBAN',
            'type'        => 'text',
            'sanitize'    => 'iban',
            'maxlength'   => 34,
            'placeholder' => 'DE89370400440532013000',
            'form_key'    => 'iban_fuer_den_lastschrifteinzug',
        ],        
        'nf_zahlungsart' => [
            'label'    => 'Zahlungsweise',
            'type'     => 'select',
            'sanitize' => 'select',
            'options'  => ['jahrlich' => 'Jährlich', 'monatlich' => 'Monatlich'],
            'form_key' => 'zahlungsweise',
        ],
        
    ];

    // =========================================================================
    public function __construct(){
        add_action('ninja_forms_after_submission', [$this, 'handle_submission'], 10, 1);
        add_action('init',                         [$this, 'handle_confirmation']);

        // Backend-Menü
        add_action('admin_menu',            [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);

        // Filter für Formular CSS Klassen
        // Verwendet in Ninja Forms, um CSS-Klassen hinzuzufügen
        add_filter('ninja_forms_display_form_class', [$this, 'add_form_css_classes'], 10, 2);

        // Erweiterbare Felder
        $this->custom_fields = apply_filters('nf_member_custom_fields', $this->custom_fields);
    }

    // =========================================================================
    // CSS-Klassen zum Formular hinzufügen
    // =========================================================================
    public function add_form_css_classes($classes, $form) {
        // Nur auf das Mitgliedsantrag-Formular anwenden
        if (isset($form['settings']['title']) && $form['settings']['title'] === $this->target_form) {
            // Standardklasse hinzufügen
            $classes .= ' mitgliedsform';
            
            // Filter für zusätzliche CSS-Klassen (optional erweiterbar)
            $custom_classes = apply_filters('nf_member_registration_form_css_classes', '', $form);
            if ($custom_classes) {
                $classes .= ' ' . $custom_classes;
            }
        }
        return $classes;
    }

    // =========================================================================
    // Sanitize-Helfer
    // =========================================================================
    private function sanitize_field(string $type, string $value, array $config = []): string {
        switch($type){
            case 'iban':
                return strtoupper(sanitize_text_field(str_replace(' ', '', $value)));
            case 'select':
                $value = sanitize_text_field($value);
                foreach(($config['options'] ?? []) as $option_value => $option_label){
                    if((string)$option_value === $value || strcasecmp((string)$option_label, $value) === 0){
                        return (string)$option_value;
                    }
                }
                return $value;
            default:
                return sanitize_text_field($value);
        }
    }

    private function normalize_form_value($value): string {
        if(is_scalar($value)){
            return trim((string)$value);
        }

        if(!is_array($value)){
            return '';
        }

        // Ninja Forms kann Auswahlfelder als Datenobjekt zurückgeben.
        foreach(['value', 'label'] as $preferred_key){
            if(isset($value[$preferred_key]) && is_scalar($value[$preferred_key])){
                return trim((string)$value[$preferred_key]);
            }
        }

        $parts = [];
        foreach($value as $part){
            $normalized = $this->normalize_form_value($part);
            if($normalized !== ''){
                $parts[] = $normalized;
            }
        }

        return implode(', ', array_unique($parts));
    }

    private function collect_nested_custom_fields($node, array &$meta): void {
        if(!is_array($node)) return;

        // Ninja Forms kann ein Feld als ['key' => ..., 'value' => ...] liefern.
        if((isset($node['key']) || isset($node['name'])) && array_key_exists('value', $node)){
            $field_key = (string)($node['key'] ?? $node['name']);
            foreach($this->custom_fields as $meta_key => $cfg){
                if($field_key === ($cfg['form_key'] ?? '')){
                    if(!array_key_exists($meta_key, $meta)){
                        $field_value = $this->normalize_form_value($node['value']);
                        if($field_value !== ''){
                            $meta[$meta_key] = $this->sanitize_field($cfg['sanitize'] ?? 'text', $field_value, $cfg);
                        }
                    }
                    return;
                }
            }

            $this->collect_nested_custom_fields($node['value'], $meta);
            return;
        }

        // Ein Feld-Array einer Gruppe enthält mehrere Feldobjekte derselben Zeile;
        // diese werden alle gelesen, während die äußere Wiederholung unten begrenzt wird.
        $is_field_list = !empty($node);
        foreach($node as $item){
            if(!is_array($item) || (!isset($item['key']) && !isset($item['name'])) || !array_key_exists('value', $item)){
                $is_field_list = false;
                break;
            }
        }
        if($is_field_list){
            foreach($node as $item){
                $this->collect_nested_custom_fields($item, $meta);
            }
            return;
        }

        // Bei einer Liste aus Wiederholungszeilen ausschließlich die erste Zeile verwenden.
        if(array_keys($node) === range(0, count($node) - 1)){
            if(isset($node[0]) && is_array($node[0])){
                $this->collect_nested_custom_fields($node[0], $meta);
            }
            return;
        }

        // Manche Ninja-Forms-Erweiterungen liefern Gruppenfelder als assoziatives Array.
        foreach($node as $field_key => $value){
            foreach($this->custom_fields as $meta_key => $cfg){
                if((string)$field_key === ($cfg['form_key'] ?? '') && !array_key_exists($meta_key, $meta)){
                    $field_value = $this->normalize_form_value($value);
                    if($field_value !== ''){
                        $meta[$meta_key] = $this->sanitize_field($cfg['sanitize'] ?? 'text', $field_value, $cfg);
                    }
                    continue 2;
                }
            }

            if(is_array($value)){
                $this->collect_nested_custom_fields($value, $meta);
            }
        }
    }

    private function display_custom_field_value(string $meta_key, $value): string {
        if(!is_scalar($value)) return '';
        $value = (string)$value;

        if(isset($this->custom_fields[$meta_key]['options'][$value])){
            return $this->custom_fields[$meta_key]['options'][$value];
        }

        return $value;
    }

    private function format_custom_fields_for_email(array $values, string $email = '', bool $html = false): string {
        $lines = [];

        foreach($this->custom_fields as $meta_key => $cfg){
            if($meta_key === 'nf_iban'){
                continue;
            }

            $value = $values[$meta_key] ?? '';
            if($meta_key === 'nf_email' && $value === ''){
                $value = $email;
            }
            $value = $this->display_custom_field_value($meta_key, $value);
            if($value === '') continue;

            if($html){
                $lines[] = esc_html($cfg['label']).': '.esc_html($value);
            } else {
                $lines[] = $cfg['label'].': '.$value;
            }
        }

        return implode($html ? '<br>' : "\n", $lines);
    }

    // =========================================================================
    // Ninja Forms – Formularverarbeitung
    // =========================================================================
    public function handle_submission($form_data){
        if(($form_data['settings']['title'] ?? '') !== $this->target_form) return;

        $email      = '';
        $meta       = [];

        foreach(($form_data['fields'] ?? []) as $field){
            if(!is_array($field)) continue;
            $field_value = $this->normalize_form_value($field['value'] ?? '');

            if(($field['type'] ?? '') === 'email' && $field_value !== '') {
                $email = sanitize_email($field_value);
            }
            $this->collect_nested_custom_fields($field, $meta);
        }

        if(!empty($meta['nf_email']) && is_email($meta['nf_email'])){
            $email = sanitize_email($meta['nf_email']);
        }

        if(!is_email($email)) return;

        if(isset($this->custom_fields['nf_email'])){
            $meta['nf_email'] = $email;
        }

        $token    = wp_generate_uuid4();

        add_option('nf_member_'.$token, [
            'email'      => $email,
            'meta'       => $meta,
        ]);

        $confirm_url =  add_query_arg(['nf_confirm' => $token], home_url('/bestellung-bestaetigen/')) ;
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $submitted_fields = $this->format_custom_fields_for_email($meta, $email, true);
        wp_mail(
        	$email, 
        	'Registrierung bestätigen',
            "Bitte bestätige deinen Antrag bei ".esc_html(get_option('blogname'))." mit Klick auf folgendem Link:<br><br>".$confirm_url
                .($submitted_fields !== '' ? '<br><br>Deine Angaben:<br>'.$submitted_fields : ''),
            $headers
            );
    }

    // =========================================================================
    // Bestätigungslink – Antrag protokollieren
    // =========================================================================
    public function handle_confirmation(){
        if(!isset($_GET['nf_confirm'])) return;

        $token = sanitize_text_field(wp_unslash($_GET['nf_confirm']));
        $data  = get_option('nf_member_'.$token);
        if(!$data) wp_die('Ungültiger Bestätigungslink.');

        // Bestätigte Anträge als Protokoll speichern, ohne WordPress-Benutzerkonto.
        $submissions = get_option('nf_member_confirmed_submissions', []);
        if(!is_array($submissions)) $submissions = [];
        $berlin_timezone = new DateTimeZone('Europe/Berlin');
        $confirmed_datetime = new DateTimeImmutable('now', $berlin_timezone);
        $confirmed_at = $confirmed_datetime->format('Y-m-d H:i:s');
        $submissions[] = [
            'confirmed_at' => $confirmed_at,
            'email'        => sanitize_email($data['email'] ?? ''),
            'meta'          => is_array($data['meta'] ?? null) ? $data['meta'] : [],
        ];
        update_option('nf_member_confirmed_submissions', $submissions, false);

        $customer_fields = $this->format_custom_fields_for_email(
            is_array($data['meta'] ?? null) ? $data['meta'] : [],
            sanitize_email($data['email'] ?? '')
        );
        $customer_email_body = "Sehr geehrte(r) Kunde,\n\n"
            ."vielen Dank für Ihre Bestellung. Ihre Angaben wurden erfolgreich über unsere Website übermittelt und bestätigt.\n\n"
            .($customer_fields !== '' ? "Ihre Angaben:\n".$customer_fields."\n\n" : '')
            ."Mit freundlichen Grüßen\nMaik Pluschle";
        wp_mail($data['email'], 'Bestellung bestätigt', $customer_email_body);

        // Admin-Benachrichtigung versenden
        $admin_email = get_option('nf_member_admin_email');
        if($admin_email){
            $current_date = $confirmed_datetime->format('d.m.Y H:i:s');
            $email_subject = 'Neuer bestätigter Mitgliedsantrag';
            $email_body = "Ein Mitgliedsantrag wurde bestätigt:\n\n"
                .$this->format_custom_fields_for_email(
                    is_array($data['meta'] ?? null) ? $data['meta'] : [],
                    sanitize_email($data['email'] ?? '')
                )."\n"
                ."Bestätigt am: ".$current_date."\n\n";
            
            wp_mail($admin_email, $email_subject, $email_body);
        }

        delete_option('nf_member_'.$token);
        wp_redirect(home_url('/bestellung-bestaetigen/'));
        exit;
    }

    // =========================================================================
    // Backend-Menü registrieren
    // =========================================================================
    public function register_admin_menu(){
        add_menu_page(
            'Bestätigte Anträge', 'Anträge', 'manage_options',
            'nf-mitglieder', [$this, 'page_mitglieder_liste'],
            'dashicons-groups', 26
        );
        add_submenu_page(
            'nf-mitglieder', 'Bestätigte Anträge', 'Bestätigte Anträge',
            'manage_options', 'nf-mitglieder',
            [$this, 'page_mitglieder_liste']
        );
        add_submenu_page(
            'nf-mitglieder', 'E-Mail-Einstellungen', 'E-Mail-Einstellungen',
            'manage_options', 'nf-mitglieder-email',
            [$this, 'page_mitglieder_email']
        );
        add_submenu_page(
            'nf-mitglieder', 'Informationen', 'Informationen',
            'manage_options', 'nf-mitglieder-info',
            [$this, 'page_mitglieder_info']
        );
    }

    public function enqueue_admin_styles($hook){
        // Nur auf unseren eigenen Seiten laden
        if(!in_array($hook, ['toplevel_page_nf-mitglieder', 'mitglieder_page_nf-mitglieder-info'])){
            //return;
        }
        
        wp_enqueue_style(
            'editor-custom-css', // Handle
            plugin_dir_url(__FILE__) . 'editor-style.css', // Pfad zur CSS-Datei
            array(), // Abhängigkeiten
            '1.0' // Version
        );
    }


    // =========================================================================
    // Unterseite: E-Mail-Einstellungen. Admin-Email, Empfänger der Bestätigten
    // =========================================================================
    public function page_mitglieder_email(){
        // Nonce und Speichern verarbeiten
        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nf_email_nonce'])){
            if(wp_verify_nonce($_POST['nf_email_nonce'], 'nf_email_settings')){
                if(!current_user_can('manage_options')) wp_die('Keine Berechtigung.');
                
                $admin_email = sanitize_email($_POST['nf_member_admin_email'] ?? '');
                
                if(is_email($admin_email)){
                    update_option('nf_member_admin_email', $admin_email);
                    echo '<div class="notice notice-success"><p><strong>Erfolgreich gespeichert!</strong> Die Benachrichtigungs-E-Mail wurde aktualisiert.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p><strong>Fehler!</strong> Bitte gib eine gültige E-Mail-Adresse ein.</p></div>';
                }
            }
        }
        
        $current_email = get_option('nf_member_admin_email', '');
        ?>
        <div class="wrap nf-members-wrap">
            <h1>
                <span class="dashicons dashicons-email" style="font-size:26px;vertical-align:middle;margin-right:6px;"></span>
                E-Mail-Einstellungen
            </h1>
            
            <div class="nf-settings-box">
                <h2>Antragsbenachrichtigungen</h2>
                <p>Gib die E-Mail-Adresse ein, an die Benachrichtigungen über neue bestätigte Mitglieder versendet werden sollen.</p>
                
                <form method="post" class="nf-email-form">
                    <?php wp_nonce_field('nf_email_settings', 'nf_email_nonce'); ?>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><label for="nf_member_admin_email">Benachrichtigungs-E-Mail</label></th>
                            <td>
                                <input 
                                    type="email" 
                                    id="nf_member_admin_email" 
                                    name="nf_member_admin_email" 
                                    value="<?php echo esc_attr($current_email); ?>" 
                                    class="regular-text" 
                                    placeholder="admin@example.com"
                                    required
                                />
                                <p class="description">Diese Adresse erhält eine Benachrichtigung, wenn ein Antrag bestätigt wird.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Speichern', 'primary', 'submit'); ?>
                </form>
            </div>

            <div class="nf-info-box">
                <h2>Informationen</h2>
                <p>Nach der Bestätigung eines Mitgliedsantrags werden die konfigurierten Formularfelder im Backend protokolliert und eine Benachrichtigung versendet.</p>
                <ul>
                    <li>E-Mail-Adresse des Mitglieds</li>
                    <li>Datum und Zeit der Bestätigung</li>
                </ul>
            </div>
        </div>
       
        <?php
    }


    // =========================================================================
    // Unterseite 1: Bestätigte Anträge
    // =========================================================================
    public function page_mitglieder_liste(){
        $submissions = get_option('nf_member_confirmed_submissions', []);
        if(!is_array($submissions)) $submissions = [];
        $submissions = array_reverse($submissions);
        $count = count($submissions);
        ?>
        <div class="wrap nf-members-wrap">
            <h1>
                <span class="dashicons dashicons-groups" style="font-size:26px;vertical-align:middle;margin-right:6px;"></span>
                Bestätigte Anträge
            </h1>
            <div class="nf-stat-row">
                <div class="nf-stat">
                    <strong><?php echo esc_html($count); ?></strong>
                    <span>Bestätigte Anträge</span>
                </div>
            </div>
            <table class="nf-members-table">
                <thead>
                    <tr>
                        <th>Bestätigt am</th>
                        <?php foreach($this->custom_fields as $cfg): ?>
                            <th><?php echo esc_html($cfg['label']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($submissions)): ?>
                    <tr><td colspan="<?php echo esc_attr(count($this->custom_fields) + 1); ?>" class="nf-empty">Noch keine bestätigten Anträge vorhanden.</td></tr>
                <?php else: foreach($submissions as $submission):
                    $fields = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
                    $confirmed_at = !empty($submission['confirmed_at'])
                        ? (new DateTimeImmutable($submission['confirmed_at'], new DateTimeZone('Europe/Berlin')))->format('d.m.Y H:i')
                        : '–';
                    ?>
                    <tr>
                        <td><?php echo esc_html($confirmed_at); ?></td>
                        <?php foreach($this->custom_fields as $meta_key => $cfg):
                            $value = $fields[$meta_key] ?? '';
                            if($meta_key === 'nf_email' && $value === '') {
                                $value = $submission['email'] ?? '';
                            }
                            $value = $this->display_custom_field_value($meta_key, $value);
                            ?>
                            <td><?php echo esc_html($value !== '' ? $value : '–'); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // =========================================================================
    // Unterseite 2: Informationen
    // =========================================================================
    public function page_mitglieder_info(){
        ?>
        <div class="wrap nf-members-wrap">
            <h1>
                <span class="dashicons dashicons-info" style="font-size:26px;vertical-align:middle;margin-right:6px;"></span>
                Informationen zum Plugin
            </h1>
            <div class="nf-info-box">
                <h2>Digitaler Mitgliedsantrag</h2>
                <p>Dieses Erweiterung ermöglicht die digitale Verarbeitung von Mitgliedsanträgen in Zusammenarbeit mit dem Formular-Plugin <strong>Ninja Forms</strong>.</p>
                <h3>Ablauf</h3>
                <ol>
                    <li>Ein Besucher füllt das Formular <em>„Mitgliedsantrag"</em> aus.</li>
                    <li>Eine Bestätigungs-E-Mail mit einem einmaligen Bestätigungslink wird gesendet.</li>
                    <li>Eine Informations-Email mit den Ausgefüllte Daten werden an die hier hinterlegte Email-Adresse sowie an den Antragssteller gesendet.</li>
                    <li>Nach Klick auf den Bestätigungs-Link werden die Angaben im Backend protokolliert und eine Informations-E-Mail an den Administrator gesendet.</li>
                    <li>Der Antragsteller erhält eine Bestätigungs-E-Mail. Es wird kein WordPress-Benutzerkonto angelegt.</li>
                </ol>
                <hr>
                <h2>Rechtliche Hinweise</h2>
                <p>Dieses Antragsformular ist rechtskräftig aufgrund folgender Bedingungen</p>
                <ol>
                    <li>Digital abgesendetes Formular</li>
                    <li>Die Zustimmung zur Satzung und Datenschutz via Checkbox als Pflichtfeld</li>
                    <li>Die Speicherung der übermittelten Daten im System oder per E-Mail</li>
                    <li>Ein Zeitstempel der Bestätigung je Mitglied</li>
                    <li>Einwilligung via Double-Opt-In Verfahren</li>
                    <li>Hinweis in Datenschutzerklärung und Satzung</li>
                </ol>
                <p>Achten Sie auf die Hinweise in der Datenschutzerklärung sowie der Satzung</p>
                <p>Folgende Formulierungen können dazu verwendet werden:</p>
                <p><strong>Satzung:</strong></p>
                <p>§ X Mitgliedsantrag</p>
                <ul>
                    <li>Die Mitgliedschaft im Verein wird durch Antrag erworben.</li>
                    <li>Der Antrag kann schriftlich oder über ein vom Verein bereitgestelltes digitales Formular gestellt werden.</li>
                    <li>Mit dem Antrag erkennt der Antragsteller die Satzung sowie die Ordnungen des Vereins in ihrer jeweils gültigen Fassung an.</li>
                    <li>Der Antrag gilt als angenommen, sofern der Verein ihn nicht innerhalb einer angemessenen Frist ablehnt, sofern die Satzung nichts anderes bestimmt.</li>
                </ul>
                <p>§ X Beiträge und SEPA-Lastschrift</p>
                <ol>
                    <li>Zur Erfüllung der Vereinszwecke erhebt der Verein Mitgliedsbeiträge. Höhe und Fälligkeit werden durch die Mitgliederversammlung oder eine Beitragsordnung festgelegt.</li>
                    <li>Der Einzug der Mitgliedsbeiträge erfolgt in der Regel im SEPA-Lastschriftverfahren.</li>
                    <li>Mit Stellung des Mitgliedsantrags kann dem Verein ein SEPA-Lastschriftmandat zum Einzug der Beiträge erteilt werden. Dieses Mandat kann auch digital über ein vom Verein bereitgestelltes Online-Formular abgegeben werden.</li>
                    <li>Das Mitglied verpflichtet sich, dem Verein Änderungen der Bankverbindung unverzüglich mitzuteilen.</li>
                </ol>
                <br>
                <p><strong>Datenschutzerklärung:</strong></p>
                <p>Verarbeitung personenbezogener Daten im Rahmen des digitalen Mitgliedsantrags<br>
                    Über das auf unserer Website bereitgestellte Online-Formular haben Interessierte die Möglichkeit, eine Mitgliedschaft in unserem Verein zu beantragen. Dabei werden die von Ihnen eingegebenen personenbezogenen Daten zum Zweck der Bearbeitung des Mitgliedsantrags sowie zur Durchführung der Mitgliedschaft verarbeitet.
                    <br>
                    Zu den verarbeiteten Daten gehören insbesondere:
                    <br>
                    <ul>
                    <li>Kundennummer und Name</li>
                    <li>Adressanschrift</li>
                    <li>Geburtsdatum</li>
                    <li>Kontaktdaten (E-Mail-Adresse, Telefonnummer)</li>
                    <li>Bankverbindung (IBAN, Kontoinhaber) zum Zweck des Beitragseinzugs</li>
                    <li>Datum und Inhalt der abgegebenen Einwilligungen</li>
                    </ul>
                    <br>
                    Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO (Durchführung vorvertraglicher Maßnahmen bzw. Erfüllung der Mitgliedschaft) sowie Art. 6 Abs. 1 lit. c DSGVO (gesetzliche Aufbewahrungspflichten). Soweit eine Einwilligung abgegeben wird (z. B. für den Beitragseinzug per SEPA-Lastschrift), erfolgt die Verarbeitung zusätzlich auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO.
                    <br>
                    Die im Mitgliedsantrag angegebenen Daten werden ausschließlich zum Zweck der Mitgliederverwaltung, Organisation des Vereinsbetriebs sowie zum Einzug der Mitgliedsbeiträge verarbeitet. Eine Weitergabe an Dritte erfolgt nur, soweit dies zur Durchführung der Mitgliedschaft erforderlich ist, insbesondere an Kreditinstitute zum Zwecke des SEPA-Lastschrifteinzugs.
                    <br>
                    Die Daten werden für die Dauer der Mitgliedschaft gespeichert. Nach Beendigung der Mitgliedschaft werden die personenbezogenen Daten gelöscht, soweit keine gesetzlichen Aufbewahrungspflichten entgegenstehen.
                    <br>
                    Die Übermittlung der Daten über das Online-Formular erfolgt verschlüsselt (HTTPS).
                    <br>
                    Betroffene Personen haben jederzeit das Recht auf Auskunft über ihre gespeicherten personenbezogenen Daten sowie auf Berichtigung, Löschung oder Einschränkung der Verarbeitung im Rahmen der gesetzlichen Bestimmungen.
                </p>
                <hr>
                <h2>Technische Details</h2>
                <h3>Formularfelder (Ninja Forms → Key)</h3>
                <table class="nf-members-table" style="max-width:500px;margin-bottom:20px;">
                    <thead><tr><th>Feldbezeichnung</th><th>Key im Formular</th></tr></thead>
                    <tbody>
                    <?php foreach($this->custom_fields as $cfg): ?>
                        <tr>
                            <td><?php echo esc_html($cfg['label']); ?></td>
                            <td><code><?php echo esc_html($cfg['form_key']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <h3>Hinweise</h3>
                <ul>
                    <li>Das Ninja-Formular muss den exakten Titel <strong>„Mitgliedsantrag"</strong> tragen.</li>
                    <li>Nach erfolgreicher Registrierung wird auf Ihre Webseite weitergeleitet.</li>
                    <li>Bestätigungs-Tokens werden als Site-Option gespeichert und nach Verwendung automatisch gelöscht.</li>
                    <li>IBAN und BIC werden automatisch in Großbuchstaben ohne Leerzeichen gespeichert.</li>
                    <li>Das Geburtsdatum wird intern als <code>YYYY-MM-DD</code> gespeichert, im Profil als <code>TT.MM.JJJJ</code> angezeigt.</li>
                </ul>
                <p style="margin-top:24px;color:#888;font-size:12px;">Plugin: Digitaler Mitgliedsantrag &nbsp;|&nbsp; Entwickelt für den internen Gebrauch</p>
            </div>
        </div>
        <?php
    }
}

new NF_Member_Registration();