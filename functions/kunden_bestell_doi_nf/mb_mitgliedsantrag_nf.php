<?php
/*
*	Erweiterung: Bestellbestätigung
* 	Description: In Zusammenarbeit mit dem Formular-Plugin Ninjaforms.
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

        // ── Kunden Daten ─────────────────────────────────────────────────
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
        // ── Persönliche Daten ─────────────────────────────────────────────────
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
        // ── Webseite Daten ─────────────────────────────────────────────────
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
        // ── Zahlungs Daten ────────────────────────────────────────────────────
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
        add_action('admin_post_nf_export_orders_csv', [$this, 'export_orders_csv']);

        // Erweiterbare Felder
        $this->custom_fields = apply_filters('nf_member_custom_fields', $this->custom_fields);
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

    private $field_id_map = [];

    private function build_id_map($node): void {
        if(!is_array($node)) return;
        if(isset($node['id'], $node['key']) && is_scalar($node['id']) && is_scalar($node['key'])){
            $this->field_id_map[(string)$node['id']] = (string)$node['key'];
        }
        foreach($node as $child){
            if(is_array($child)) $this->build_id_map($child);
        }
    }

    /**
     * Liefert [meta_key, zeilenindex|null] oder null.
     * Zeilenindex stammt aus Suffixen wie "15_0" oder "paket_1".
    */
    private function resolve_meta_key(array $item): ?array {
        $candidates = [];

        foreach(['key', 'name'] as $k){
            if(isset($item[$k]) && is_scalar($item[$k])){
                $candidates[] = (string)$item[$k];
            }
        }
        if(isset($item['id']) && is_scalar($item['id'])){
            $id = (string)$item['id'];
            $candidates[] = $id;
            if(isset($this->field_id_map[$id])){
                $candidates[] = $this->field_id_map[$id] . (preg_match('/_(\d+)$/', $id, $m) ? '_' . $m[1] : '');
            }
            $base = preg_replace('/_\d+$/', '', $id);
            if(isset($this->field_id_map[$base])){
                $candidates[] = $this->field_id_map[$base] . (preg_match('/_(\d+)$/', $id, $m) ? '_' . $m[1] : '');
            }
        }

        foreach($candidates as $c){
            $row  = null;
            $base = $c;
            if(preg_match('/^(.*)_(\d+)$/', $c, $m)){
                $base = $m[1];
                $row  = (int)$m[2];
            }
            foreach($this->custom_fields as $meta_key => $cfg){
                $fk = $cfg['form_key'] ?? '';
                if($fk === '') continue;
                if($c === $fk) return [$meta_key, null];
                if($base === $fk) return [$meta_key, $row];
            }
        }
        return null;
    }

    private function store_meta(string $meta_key, $raw, ?int $row, array &$meta): void {
        if($row !== null && $row !== 0) return;          // nur erste Zeile
        if(array_key_exists($meta_key, $meta)) return;   // erster Treffer gewinnt
        $value = $this->normalize_form_value($raw);
        if($value === '') return;
        $cfg = $this->custom_fields[$meta_key];
        $meta[$meta_key] = $this->sanitize_field($cfg['sanitize'] ?? 'text', $value, $cfg);
    }


    private function collect_nested_custom_fields($node, array &$meta): void {
        //Wiederholbare Feldgruppe
        if(!is_array($node)) return;

        // Feldobjekt mit value
        if(array_key_exists('value', $node) && (isset($node['key']) || isset($node['name']) || isset($node['id']))){
            $r = $this->resolve_meta_key($node);
            if($r !== null){
                $this->store_meta($r[0], $node['value'], $r[1], $meta);
                return;
            }
            $this->collect_nested_custom_fields($node['value'], $meta);
            return;
        }

        foreach($node as $k => $child){
            if(is_array($child)){
                $this->collect_nested_custom_fields($child, $meta);
            } elseif(is_string($k) && $k !== 'value'){
                $r = $this->resolve_meta_key(['key' => $k]);
                if($r !== null) {
                    $this->store_meta($r[0], $child, $r[1], $meta);
                }
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

    /*  =========================================================================
        Ninja Forms – Formularverarbeitung
        =========================================================================
    */
    public function handle_submission($form_data){
        if(($form_data['settings']['title'] ?? '') !== $this->target_form) return;

        $email      = '';
        $meta       = [];
        $this->field_id_map = [];
        $this->build_id_map($form_data['fields'] ?? []);

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
        	'Bestellung bestätigen',
            "Bitte bestätige deine Bestellung bei ".esc_html(get_option('blogname'))." mit Klick auf folgendem Link:<br><br>".$confirm_url
                .($submitted_fields !== '' ? '<br><br>Deine Angaben:<br>'.$submitted_fields : ''),
            $headers
            );
    }

    /*  =========================================================================
        Bestätigungslink – Antrag protokollieren
        =========================================================================
    */
    public function handle_confirmation(){
        if(!isset($_GET['nf_confirm'])) return;

        $token = sanitize_text_field(wp_unslash($_GET['nf_confirm']));
        $data  = get_option('nf_member_'.$token);
        if(!$data) wp_die('Ungültiger Bestätigungslink.');

        // Bestätigte Anträge als Protokoll speichern, ohne WordPress-Benutzerkonto.
        $submissions = get_option('nf_member_confirmed_submissions', []);
        if(!is_array($submissions)) $submissions = [];
        $berlin_timezone    = new DateTimeZone('Europe/Berlin');
        $confirmed_datetime = new DateTimeImmutable('now', $berlin_timezone);
        $confirmed_at       = $confirmed_datetime->format('Y-m-d H:i:s');
        $submissions[]      = [
            'confirmed_at' => $confirmed_at,
            'email'        => sanitize_email($data['email'] ?? ''),
            'meta'         => is_array($data['meta'] ?? null) ? $data['meta'] : [],
        ];
        update_option('nf_member_confirmed_submissions', $submissions, false);

        $customer_fields = $this->format_custom_fields_for_email(
            is_array($data['meta'] ?? null) ? $data['meta'] : [],
            sanitize_email($data['email'] ?? '')
        );

        $customer_email_body = "Sehr geehrte Kundin, sehr geehrter Kunde,\n\n"
            ."vielen Dank für Ihre Bestellung. Ihre Angaben wurden erfolgreich über unsere Website übermittelt und bestätigt.\n\n"
            .($customer_fields !== '' ? "Ihre Angaben:\n".$customer_fields."\n\n" : '')
            ."Mit freundlichen Grüßen\nMaik Pluschle";
        wp_mail($data['email'], 'Bestellung bestätigt', $customer_email_body);

        // Admin-Benachrichtigung versenden
        $admin_email = get_option('nf_member_admin_email');
        if($admin_email){
            $current_date   = $confirmed_datetime->format('d.m.Y H:i:s');
            $email_subject  = 'Neuer bestätigter Bestellantrag';
            $email_body     = "Eine Beställung wurde bestätigt:\n\n"
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

    /*  =========================================================================
        Backend-Menü registrieren
        =========================================================================
    */
    public function register_admin_menu(){
        add_menu_page(
            'Bestätigte Bestellungen', 'Bestellungen', 'manage_options',
            'nf-mitglieder', [$this, 'page_mitglieder_liste'],
            'dashicons-superhero', 26
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
    }

    public function enqueue_admin_styles($hook){
        wp_enqueue_style(
            'editor-custom-css', // Handle
            get_stylesheet_directory_uri() . '/functions/kunden_bestell_doi_nf/editor-style.css', // Pfad zur CSS-Datei
            array(), // Abhängigkeiten
            '1.0' // Version
        );
    }

    /*  =========================================================================
        Unterseite: E-Mail-Einstellungen. Admin-Email, Empfänger der Bestätigten
        =========================================================================
    */
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
                <p>Gib die E-Mail-Adresse ein, an die Benachrichtigungen über neue bestätigte Bestellungen versendet werden sollen.</p>
                
                <form method="post" class="nf-email-form">
                    <?php wp_nonce_field('nf_email_settings', 'nf_email_nonce'); ?>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th>
                                <label for="nf_member_admin_email">Benachrichtigungs-E-Mail</label>
                            </th>
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
                                <p class="description">Diese Adresse erhält eine Benachrichtigung, wenn eine Bestellung bestätigt wird.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Speichern', 'primary', 'submit'); ?>
                </form>
            </div>

            <div class="nf-info-box">
                <h2>
                    Informationen
                </h2>
                <p>
                    Nach der Bestätigung eines Mitgliedsantrags werden die konfigurierten Formularfelder im Backend protokolliert und eine Benachrichtigung versendet.
                </p>
                <ul>
                    <li>E-Mail-Adresse des Mitglieds</li>
                    <li>Datum und Zeit der Bestätigung</li>
                </ul>
            </div>
        </div>
       
        <?php
    }

    /*  =========================================================================
        Unterseite 1: Bestätigte Anträge
        =========================================================================
    */
    public function export_orders_csv(){
        if(!current_user_can('manage_options')){
            wp_die('Keine Berechtigung zum Exportieren der Bestellungen.');
        }
        check_admin_referer('nf_export_orders_csv');

        $submissions = get_option('nf_member_confirmed_submissions', []);
        if(!is_array($submissions)) $submissions = [];
        $submissions = array_reverse($submissions);

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bestellungen-' . gmdate('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM sorgt dafür, dass Excel Umlaute korrekt erkennt.
        fwrite($output, "\xEF\xBB\xBF");

        $headers = ['Bestätigt am'];
        foreach($this->custom_fields as $cfg){
            $headers[] = $cfg['label'] === 'Kundennummer' ? 'Kdr' : $cfg['label'];
        }
        fputcsv($output, $headers, ';', '"', '\\');

        foreach($submissions as $submission){
            $fields = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
            $confirmed_at = !empty($submission['confirmed_at'])
                ? (new DateTimeImmutable($submission['confirmed_at'], new DateTimeZone('Europe/Berlin')))->format('d.m.Y H:i')
                : '–';
            $row = [$confirmed_at];

            foreach($this->custom_fields as $meta_key => $cfg){
                $value = $fields[$meta_key] ?? '';
                if($meta_key === 'nf_email' && $value === ''){
                    $value = $submission['email'] ?? '';
                }
                $value = $this->display_custom_field_value($meta_key, $value);

                // Verhindert, dass Tabellenkalkulationen Zellwerte als Formeln ausführen.
                if(is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value)){
                    $value = "'".$value;
                }
                $row[] = $value !== '' ? $value : '–';
            }

            fputcsv($output, $row, ';', '"', '\\');
        }

        fclose($output);
        exit;
    }

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
                <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=nf_export_orders_csv'), 'nf_export_orders_csv')); ?>">
                    CSV herunterladen
                </a>
            </div>
            <table class="nf-members-table">
                <thead>
                    <tr>
                        <th>Bestätigt am</th>
                        <?php foreach ($this->custom_fields as $cfg): ?>
                            <th>
                                <?php
                                echo esc_html(
                                    $cfg['label'] === 'Kundennummer'
                                        ? 'Kdr'
                                        : $cfg['label']
                                );
                                ?>
                            </th>
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
}

new NF_Member_Registration();