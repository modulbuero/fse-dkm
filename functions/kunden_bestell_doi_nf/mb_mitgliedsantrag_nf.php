<?php
/*
*	Erweiterung: Bestellbestätigung
* 	Description: In Zusammenarbeit mit dem Formular-Plugin Ninjaforms.
*
*/
if(!defined('ABSPATH')) exit;

class NF_Member_Registration {

    private $target_form = 'Hosting Bestellung';    //für die Umstellung "2027" bestehender Kunden
    private $target_cancellation_form = 'Hosting Kündigung';
    private $target_hosting_form = 'Hosting'; //für neue Kunden

    private $cancellation_fields = [
        'cancellation_customer_number' => [
            'label'    => 'Kundennummer',
            'type'     => 'text',
            'sanitize' => 'text',
            'form_key' => 'kundennummer',
        ],
        'cancellation_postcode' => [
            'label'    => 'PLZ',
            'type'     => 'text',
            'sanitize' => 'text',
            'form_key' => 'postleitzahl',
        ],
        'cancellation_date' => [
            'label'    => 'Kündigungsdatum',
            'type'     => 'text',
            'sanitize' => 'text',
            'form_key' => 'kuendigungsdatum',
        ],
        'cancellation_email' => [
            'label'    => 'E-Mail',
            'type'     => 'email',
            'sanitize' => 'email',
            'form_key' => 'e-mail_fuer_die_bestaetigung',
        ],
        'adresse_der_webseite' => [
            'label'       => 'Webseite',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'adresse_der_webseite',
        ],
    ];

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
     **/

    private $hosting_fields = [
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
            'label'       => 'Ort',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'ort',
        ],
        'nf_name' => [
            'label'       => 'Name',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'name',
        ],
        'nf_strasze' => [
            'label'       => 'Straße',
            'type'        => 'text',
            'sanitize'    => 'text',
            'placeholder' => '',
            'form_key'    => 'strasze',
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
        'nf_kundennummer' => [
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
        add_action('ninja_forms_after_submission', [$this, 'handle_cancellation_submission'], 10, 1);
        add_action('init',                         [$this, 'handle_confirmation']);

        // Backend-Menü
        add_action('admin_menu',            [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('admin_post_nf_export_orders_csv', [$this, 'export_orders_csv']);
        add_action('admin_post_nf_export_hosting_csv', [$this, 'export_hosting_csv']);
        add_action('admin_post_nf_export_cancellations_csv', [$this, 'export_cancellations_csv']);
        add_action('admin_post_nf_delete_submission', [$this, 'delete_submission']);
        add_action('admin_post_nf_update_submission', [$this, 'update_submission']);

        // Erweiterbare Felder
        $this->custom_fields = apply_filters('nf_member_custom_fields', $this->custom_fields);
        $this->target_hosting_form = apply_filters('nf_hosting_form_title', $this->target_hosting_form);
        $this->hosting_fields = apply_filters('nf_hosting_custom_fields', $this->hosting_fields);
        $this->target_cancellation_form = apply_filters('nf_cancellation_form_title', $this->target_cancellation_form);
        $this->cancellation_fields = apply_filters('nf_cancellation_custom_fields', $this->cancellation_fields);
    }

    // =========================================================================
    // Sanitize-Helfer
    // =========================================================================
    private function sanitize_field(string $type, string $value, array $config = []): string {
        switch($type){
            case 'iban':
                return strtoupper(sanitize_text_field(str_replace(' ', '', $value)));
            case 'email':
                return sanitize_email($value);
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
    private function resolve_meta_key(array $item, ?array $field_configs = null): ?array {
        $field_configs = $field_configs ?? $this->custom_fields;
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
            foreach($field_configs as $meta_key => $cfg){
                $fk = $cfg['form_key'] ?? '';
                if($fk === '') continue;
                if($c === $fk) return [$meta_key, null];
                if($base === $fk) return [$meta_key, $row];
            }
        }
        return null;
    }

    private function store_meta(string $meta_key, $raw, ?int $row, array &$meta, ?array $field_configs = null): void {
        $field_configs = $field_configs ?? $this->custom_fields;
        $value = $this->normalize_form_value($raw);
        if($value === '') return;
        $cfg = $field_configs[$meta_key];
        $value = $this->sanitize_field($cfg['sanitize'] ?? 'text', $value, $cfg);

        // Bei der Kündigung alle wiederholten Webseiten sammeln; andere Felder
        // bleiben wie bisher auf den ersten Gruppeneintrag beschränkt.
        if($meta_key === 'adresse_der_webseite' && isset($field_configs[$meta_key])){
            $websites = !empty($meta[$meta_key]) ? explode(', ', $meta[$meta_key]) : [];
            $websites[] = $value;
            $websites = array_values(array_unique(array_filter(array_map('trim', $websites))));
            $meta[$meta_key] = implode(', ', $websites);
            return;
        }

        if($row !== null && $row !== 0) return;
        if(array_key_exists($meta_key, $meta)) return;
        $meta[$meta_key] = $value;
    }


    private function collect_nested_custom_fields($node, array &$meta, ?array $field_configs = null): void {
        //Wiederholbare Feldgruppe
        if(!is_array($node)) return;
        $field_configs = $field_configs ?? $this->custom_fields;

        // Feldobjekt mit value
        if(array_key_exists('value', $node) && (isset($node['key']) || isset($node['name']) || isset($node['id']))){
            $r = $this->resolve_meta_key($node, $field_configs);
            if($r !== null){
                $this->store_meta($r[0], $node['value'], $r[1], $meta, $field_configs);
                return;
            }
            $this->collect_nested_custom_fields($node['value'], $meta, $field_configs);
            return;
        }

        foreach($node as $k => $child){
            if(is_array($child)){
                $this->collect_nested_custom_fields($child, $meta, $field_configs);
            } elseif(is_string($k) && $k !== 'value'){
                $r = $this->resolve_meta_key(['key' => $k], $field_configs);
                if($r !== null) {
                    $this->store_meta($r[0], $child, $r[1], $meta, $field_configs);
                }
            }
        }
    }

    private function display_custom_field_value(string $meta_key, $value, ?array $field_configs = null): string {
        if(!is_scalar($value)) return '';
        $value = (string)$value;

        $field_configs = $field_configs ?? $this->get_member_fields();
        if(isset($field_configs[$meta_key]['options'][$value])){
            return $field_configs[$meta_key]['options'][$value];
        }

        return $value;
    }

    private function get_member_fields(): array {
        return array_merge($this->custom_fields, $this->hosting_fields);
    }

    private function get_submission_fields(array $data): array {
        return ($data['field_set'] ?? '') === 'hosting'
            ? $this->hosting_fields
            : $this->custom_fields;
    }

    private function format_custom_fields_for_email(
        array $values,
        string $email = '',
        bool $html = false,
        ?array $field_configs = null
    ): string {
        return $this->format_fields_for_email($values, $field_configs ?? $this->custom_fields, $html, $email);
    }

    private function format_fields_for_email(array $values, array $field_configs, bool $html = true, string $email = ''): string {
        $lines = [];

        foreach($field_configs as $meta_key => $cfg){
            if($meta_key === 'nf_iban'){
                continue;
            }

            $value = $values[$meta_key] ?? '';
            if(in_array($meta_key, ['nf_email', 'cancellation_email'], true) && $value === ''){
                $value = $email;
            }
            if(!is_scalar($value)){
                $value = '';
            } elseif(isset($cfg['options'][(string)$value])){
                $value = $cfg['options'][$value];
            } else {
                $value = (string)$value;
            }
            if($value === '') continue;

            if($html){
                $lines[] = esc_html($cfg['label']).': '.esc_html($value);
            } else {
                $lines[] = $cfg['label'].': '.$value;
            }
        }

        return implode($html ? '<br>' : "\n", $lines);
    }

    private function confirm_cancellation(array $data): void {
        $email = sanitize_email($data['email'] ?? '');
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $berlin_timezone = new DateTimeZone('Europe/Berlin');
        $confirmed_datetime = new DateTimeImmutable('now', $berlin_timezone);

        $cancellations = get_option('nf_member_confirmed_cancellations', []);
        if(!is_array($cancellations)) $cancellations = [];
        $cancellations[] = [
            'confirmed_at' => $confirmed_datetime->format('Y-m-d H:i:s'),
            'email'        => $email,
            'meta'         => $meta,
        ];
        update_option('nf_member_confirmed_cancellations', $cancellations, false);

        $fields = $this->format_fields_for_email($meta, $this->cancellation_fields);
        $customer_body = "Sehr geehrte Kundin, sehr geehrter Kunde,<br><br>"
            ."Ihre Kündigung wurde bestätigt und protokolliert.<br>"
            ."Sie erhalten in kürze den Authcode zu Ihrer Domain.<br><br>"
            .($fields !== '' ? "Ihre Angaben:\n".$fields."<br><br>" : '')
            ."Bestätigt am: ".$confirmed_datetime->format('d.m.Y H:i:s')
            .$this->mailfooter();
        wp_mail($email, 'Kündigung bestätigt', $customer_body, ['Content-Type: text/html; charset=UTF-8']);

        $admin_email = get_option('nf_member_admin_email');
        if(is_email($admin_email)){
            $admin_body = "Eine Kündigung wurde bestätigt:<br><br>"
                .$fields."<br>"
                ."Bestätigt am: ".$confirmed_datetime->format('d.m.Y H:i:s')
                .$this->mailfooter();

            wp_mail($admin_email, 'Neue bestätigte Kündigung', $admin_body, ['Content-Type: text/html; charset=UTF-8']);
        }
    }

    /*  =========================================================================
        Ninja Forms – Formularverarbeitung
        =========================================================================
    */
    public function handle_submission($form_data){
        $form_title = $form_data['settings']['title'] ?? '';
        if($form_title === $this->target_form){
            $field_configs = $this->custom_fields;
            $field_set = 'custom';
        } elseif($form_title === $this->target_hosting_form){
            $field_configs = $this->hosting_fields;
            $field_set = 'hosting';
        } else {
            return;
        }

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
            $this->collect_nested_custom_fields($field, $meta, $field_configs);
        }

        if(!empty($meta['nf_email']) && is_email($meta['nf_email'])){
            $email = sanitize_email($meta['nf_email']);
        }

        if(!is_email($email)) return;

        if(isset($field_configs['nf_email'])){
            $meta['nf_email'] = $email;
        }

        $token    = wp_generate_uuid4();

        add_option('nf_member_'.$token, [
            'email'      => $email,
            'meta'       => $meta,
            'field_set'  => $field_set,
        ]);

        $confirm_url      = esc_url(add_query_arg(['nf_confirm' => $token], home_url('/hosting/bestellung-bestaetigen/')));
        $headers          = array('Content-Type: text/html; charset=UTF-8');
        $submitted_fields = $this->format_custom_fields_for_email($meta, $email, true, $field_configs);
        $link_text        = "Bestellung bestätigen";
        $confirmbutton = "
            <p>
                <!--[if mso]>
                <v:rect xmlns:v='urn:schemas-microsoft-com:vml'
                    href='".$confirm_url."'
                    style='height:44px; width:180px; v-text-anchor:middle;'
                    fillcolor='#ADFCC2'
                    strokecolor='#000000'
                    strokeweight='1pt'>
                    <w:anchorlock/>
                    <center style='color:#000000; font-family:Arial; font-size:16px;'>
                        ".$link_text."
                    </center>
                </v:rect>
                <![endif]-->

                <!--[if !mso]><!-->
                <a href='".$confirm_url."' style='background-color:#ADFCC2; color:#000000;
                    display:inline-block; padding:12px 24px; text-decoration:none;
                    font-family:Arial, sans-serif; font-size:16px;
                    border:1px solid #000000;'>".$link_text."</a>
                <!--<![endif]-->
            </p>";

        wp_mail(
        	$email, 
        	'Bestellung bestätigen',
            "Sehr geehrte Kundin, sehr geehrter Kunde,<br><br>"
            ."bitte bestätigen Sie Ihre Bestellung bei ".esc_html(get_option('blogname'))." im Modulbüro mit Klick auf folgenden Button:<br><br>".$confirmbutton
                .($submitted_fields !== '' ? '<br><br>Deine Angaben:<br>'.$submitted_fields : '')
                .$this->mailfooter(),
            $headers
            );
    }

    public function handle_cancellation_submission($form_data){
        if(($form_data['settings']['title'] ?? '') !== $this->target_cancellation_form) return;

        $meta = [];
        $this->field_id_map = [];
        $this->build_id_map($form_data['fields'] ?? []);

        foreach(($form_data['fields'] ?? []) as $field){
            if(is_array($field)){
                $this->collect_nested_custom_fields($field, $meta, $this->cancellation_fields);
            }
        }

        $email = sanitize_email($meta['cancellation_email'] ?? '');
        if(!is_email($email)) return;

        $token = wp_generate_uuid4();
        add_option('nf_cancellation_'.$token, [
            'type'  => 'cancellation',
            'email' => $email,
            'meta'  => $meta,
        ]);

        $confirm_url      = esc_url(add_query_arg(['nf_confirm' => $token], home_url('/kuendigung/bestaetigen/')));
        $headers          = ['Content-Type: text/html; charset=UTF-8'];
        $submitted_fields = $this->format_fields_for_email($meta, $this->cancellation_fields, true);
        $link_text        = "Kündigung bestätigen";
        $confirmbutton = "
            <p>
                <!--[if mso]>
                <v:rect xmlns:v='urn:schemas-microsoft-com:vml'
                    href='".$confirm_url."'
                    style='height:44px; width:180px; v-text-anchor:middle;'
                    fillcolor='#ADFCC2'
                    strokecolor='#000000'
                    strokeweight='1pt'>
                    <w:anchorlock/>
                    <center style='color:#000000; font-family:Arial; font-size:16px;'>
                        ".$link_text."
                    </center>
                </v:rect>
                <![endif]-->

                <!--[if !mso]><!-->
                <a href='".$confirm_url."' style='background-color:#ADFCC2; color:#000000;
                    display:inline-block; padding:12px 24px; text-decoration:none;
                    font-family:Arial, sans-serif; font-size:16px;
                    border:1px solid #000000;'>".$link_text."</a>
                <!--<![endif]-->
            </p>";

        wp_mail(
            $email,
            'Kündigung bestätigen',
            'Bitte bestätigen Sie Ihre Kündigung bei '.esc_html(get_option('blogname'))
                .' über folgenden Button:<br><br>'.$confirmbutton
                .($submitted_fields !== '' ? '<br><br>Ihre Angaben:<br>'.$submitted_fields : '')
                .$this->mailfooter(),
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
        $option_name = 'nf_member_'.$token;
        if(!$data){
            $option_name = 'nf_cancellation_'.$token;
            $data = get_option($option_name);
        }
        if(!$data) wp_die('Ungültiger Bestätigungslink.');

        if(($data['type'] ?? '') === 'cancellation'){
            $this->confirm_cancellation($data);
            delete_option($option_name);
            wp_safe_redirect(home_url('/kuendigung/bestaetigen/'));
            exit;
        }

        // Bestätigte Anträge ohne WordPress-Benutzerkonto protokollieren.
        $submission_option = ($data['field_set'] ?? '') === 'hosting'
            ? 'nf_member_confirmed_hosting_submissions'
            : 'nf_member_confirmed_submissions';
        $submissions = get_option($submission_option, []);
        if(!is_array($submissions)) $submissions = [];
        $berlin_timezone    = new DateTimeZone('Europe/Berlin');
        $confirmed_datetime = new DateTimeImmutable('now', $berlin_timezone);
        $confirmed_at       = $confirmed_datetime->format('Y-m-d H:i:s');
        $submissions[]      = [
            'confirmed_at' => $confirmed_at,
            'email'        => sanitize_email($data['email'] ?? ''),
            'meta'         => is_array($data['meta'] ?? null) ? $data['meta'] : [],
            'field_set'    => $data['field_set'] ?? 'custom',
        ];
        update_option($submission_option, $submissions, false);

        $field_configs = $this->get_submission_fields($data);
        $customer_fields = $this->format_custom_fields_for_email(
            is_array($data['meta'] ?? null) ? $data['meta'] : [],
            sanitize_email($data['email'] ?? ''),
            false,
            $field_configs
        );

        $customer_email_body = "Sehr geehrte Kundin, sehr geehrter Kunde,<br><br>"
            ."vielen Dank für Ihre Bestellung.\n"
            ."Ihre Angaben wurden erfolgreich über unsere Website übermittelt und bestätigt."
            .$this->mailfooter();
        wp_mail($data['email'], 'Bestellung bestätigt', $customer_email_body, ['Content-Type: text/html; charset=UTF-8']);

        // Admin-Benachrichtigung versenden
        $admin_email = get_option('nf_member_admin_email');
        if($admin_email){
            $current_date   = $confirmed_datetime->format('d.m.Y H:i:s');
            $email_subject  = 'Neuer bestätigter Bestellantrag';
            $email_body     = "Eine Bestellung wurde bestätigt:<br><br>"
                .$this->format_custom_fields_for_email(
                    is_array($data['meta'] ?? null) ? $data['meta'] : [],
                    sanitize_email($data['email'] ?? ''),
                    false,
                    $field_configs
                )."<br>"
                ."Bestätigt am: ".$current_date.$this->mailfooter();
            
            wp_mail($admin_email, $email_subject, $email_body, ['Content-Type: text/html; charset=UTF-8']);
        }

        delete_option($option_name);
        wp_redirect(home_url('/hosting/bestellung-bestaetigen/'));
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
            'nf-mitglieder', 'Bestätigte Umzugs-Bestellungen', 'Umzug',
            'manage_options', 'nf-mitglieder',
            [$this, 'page_mitglieder_liste']
        );
        add_submenu_page(
            'nf-mitglieder', 'Bestätigte Hosting-Bestellungen', 'Hosting',
            'manage_options', 'nf-kunden',
            [$this, 'page_kunden_liste']
        );
        add_submenu_page(
            'nf-mitglieder', 'Bestätigte Kündigungen', 'Kündigungen',
            'manage_options', 'nf-kuendigungen',
            [$this, 'page_cancellations']
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

    public function delete_submission(){
        if(!current_user_can('manage_options')){
            wp_die('Keine Berechtigung.');
        }

        $list = isset($_POST['list']) && is_scalar($_POST['list'])
            ? sanitize_key(wp_unslash((string)$_POST['list']))
            : '';
        $index = isset($_POST['index']) && is_scalar($_POST['index'])
            ? sanitize_text_field(wp_unslash((string)$_POST['index']))
            : '';
        $lists = [
            'members' => [
                'option' => 'nf_member_confirmed_submissions',
                'page'   => 'nf-mitglieder',
            ],
            'hosting' => [
                'option' => 'nf_member_confirmed_hosting_submissions',
                'page'   => 'nf-kunden',
            ],
            'hosting_legacy' => [
                'option' => 'nf_member_confirmed_submissions',
                'page'   => 'nf-kunden',
            ],
            'cancellations' => [
                'option' => 'nf_member_confirmed_cancellations',
                'page'   => 'nf-kuendigungen',
            ],
        ];

        if(!isset($lists[$list]) || $index === ''){
            wp_die('Ungültiger Eintrag.');
        }
        check_admin_referer('nf_delete_submission_' . $list . '_' . $index, 'nf_delete_nonce');

        $option_name = $lists[$list]['option'];
        $submissions = get_option($option_name, []);
        if(!is_array($submissions) || !array_key_exists($index, $submissions)){
            wp_die('Der Eintrag wurde nicht gefunden.');
        }
        if($list === 'hosting_legacy'
            && (!is_array($submissions[$index]) || ($submissions[$index]['field_set'] ?? '') !== 'hosting')){
            wp_die('Der Eintrag gehört nicht zu den Hosting-Bestellungen.');
        }

        unset($submissions[$index]);
        if(!update_option($option_name, $submissions)){
            wp_die('Der Eintrag konnte nicht gelöscht werden.');
        }

        wp_safe_redirect(admin_url('admin.php?page=' . $lists[$list]['page'] . '&deleted=1'));
        exit;
    }

    public function update_submission(){
        if(!current_user_can('manage_options')){
            wp_die('Keine Berechtigung.');
        }

        $list = isset($_POST['list']) && is_scalar($_POST['list'])
            ? sanitize_key(wp_unslash((string)$_POST['list']))
            : '';
        $index = isset($_POST['index']) && is_scalar($_POST['index'])
            ? sanitize_text_field(wp_unslash((string)$_POST['index']))
            : '';
        $list_config = $this->get_submission_list_config($list);
        if($list_config === null || $index === ''){
            wp_die('Ungültiger Eintrag.');
        }
        check_admin_referer('nf_update_submission_' . $list . '_' . $index, 'nf_update_nonce');

        $submissions = get_option($list_config['option'], []);
        if(!is_array($submissions) || !array_key_exists($index, $submissions) || !is_array($submissions[$index])){
            wp_die('Der Eintrag wurde nicht gefunden.');
        }
        if($list === 'hosting_legacy' && ($submissions[$index]['field_set'] ?? '') !== 'hosting'){
            wp_die('Der Eintrag gehört nicht zu den Hosting-Bestellungen.');
        }

        $fields = $this->get_editable_submission_fields($list);
        $posted_fields = isset($_POST['fields']) && is_array($_POST['fields'])
            ? wp_unslash($_POST['fields'])
            : [];
        $meta = is_array($submissions[$index]['meta'] ?? null) ? $submissions[$index]['meta'] : [];

        foreach($fields as $meta_key => $field_config){
            if(!array_key_exists($meta_key, $posted_fields)){
                wp_die('Ein erforderliches Formularfeld fehlt.');
            }
            $raw_value = $posted_fields[$meta_key] ?? '';
            if(!is_scalar($raw_value)){
                wp_die('Ungültiger Feldwert.');
            }
            $value = $this->sanitize_field(
                $field_config['sanitize'] ?? 'text',
                (string)$raw_value,
                $field_config
            );
            if(!empty($field_config['options']) && $value !== ''
                && !array_key_exists($value, $field_config['options'])){
                wp_die('Ungültige Auswahl.');
            }
            if(($field_config['type'] ?? '') === 'email' && $value !== '' && !is_email($value)){
                wp_die('Bitte gib eine gültige E-Mail-Adresse ein.');
            }
            $meta[$meta_key] = $value;
        }

        $email_key = $list === 'cancellations' ? 'cancellation_email' : 'nf_email';
        $email = sanitize_email($meta[$email_key] ?? $submissions[$index]['email'] ?? '');
        if(!is_email($email)){
            wp_die('Bitte gib eine gültige E-Mail-Adresse ein.');
        }
        $meta[$email_key] = $email;
        $confirmed_at_input = isset($_POST['confirmed_at']) && is_scalar($_POST['confirmed_at'])
            ? sanitize_text_field(wp_unslash((string)$_POST['confirmed_at']))
            : '';
        $confirmed_at = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            $confirmed_at_input,
            new DateTimeZone('Europe/Berlin')
        );
        $date_errors = DateTimeImmutable::getLastErrors();
        if(!$confirmed_at || ($date_errors !== false && ($date_errors['warning_count'] || $date_errors['error_count']))
            || $confirmed_at->format('Y-m-d\TH:i') !== $confirmed_at_input){
            wp_die('Bitte gib ein gültiges Bestätigungsdatum ein.');
        }

        $submission = $submissions[$index];
        $submission['confirmed_at'] = $confirmed_at->format('Y-m-d H:i:s');
        $submission['email'] = $email;
        $submission['meta'] = $meta;
        $submissions[$index] = $submission;
        $saved = update_option($list_config['option'], $submissions, false);
        if(!$saved && get_option($list_config['option'], []) !== $submissions){
            wp_die('Die Änderungen konnten nicht gespeichert werden.');
        }

        wp_safe_redirect(admin_url('admin.php?page=' . $list_config['page'] . '&updated=1'));
        exit;
    }

    private function get_submission_list_config(string $list): ?array {
        $lists = [
            'members' => [
                'option' => 'nf_member_confirmed_submissions',
                'page' => 'nf-mitglieder',
            ],
            'hosting' => [
                'option' => 'nf_member_confirmed_hosting_submissions',
                'page' => 'nf-kunden',
            ],
            'hosting_legacy' => [
                'option' => 'nf_member_confirmed_submissions',
                'page' => 'nf-kunden',
            ],
            'cancellations' => [
                'option' => 'nf_member_confirmed_cancellations',
                'page' => 'nf-kuendigungen',
            ],
        ];
        return $lists[$list] ?? null;
    }

    private function get_editable_submission_fields(string $list): array {
        if($list === 'cancellations'){
            return $this->cancellation_fields;
        }
        if($list === 'hosting' || $list === 'hosting_legacy'){
            return $this->hosting_fields;
        }
        return array_diff_key(
            $this->get_member_fields(),
            array_flip(['nf_ort', 'nf_strasze'])
        );
    }

    private function render_submission_editor(string $page_list): bool {
        if(!isset($_GET['action']) || !is_scalar($_GET['action']) || wp_unslash($_GET['action']) !== 'edit'){
            return false;
        }
        if(!current_user_can('manage_options')){
            wp_die('Keine Berechtigung.');
        }

        $allowed_lists = $page_list === 'hosting'
            ? ['hosting', 'hosting_legacy']
            : [$page_list];
        $list = isset($_GET['list']) && is_scalar($_GET['list'])
            ? sanitize_key(wp_unslash((string)$_GET['list']))
            : '';
        $list_config = $this->get_submission_list_config($list);
        $index = isset($_GET['index']) && is_scalar($_GET['index'])
            ? sanitize_text_field(wp_unslash((string)$_GET['index']))
            : '';
        if(!in_array($list, $allowed_lists, true) || $list_config === null
            || $list_config['page'] !== $this->get_submission_list_config($page_list)['page']
            || $index === ''){
            wp_die('Ungültiger Eintrag.');
        }
        check_admin_referer('nf_edit_submission_' . $list . '_' . $index);

        $submissions = get_option($list_config['option'], []);
        if(!is_array($submissions) || !isset($submissions[$index]) || !is_array($submissions[$index])){
            wp_die('Der Eintrag wurde nicht gefunden.');
        }
        $submission = $submissions[$index];
        if($list === 'hosting_legacy' && ($submission['field_set'] ?? '') !== 'hosting'){
            wp_die('Der Eintrag gehört nicht zu den Hosting-Bestellungen.');
        }

        $fields = $this->get_editable_submission_fields($list);
        $meta = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
        $email_key = $list === 'cancellations' ? 'cancellation_email' : 'nf_email';
        $email = $submission['email'] ?? ($meta[$email_key] ?? '');
        $confirmed_at = !empty($submission['confirmed_at'])
            ? (new DateTimeImmutable($submission['confirmed_at'], new DateTimeZone('Europe/Berlin')))->format('Y-m-d\TH:i')
            : '';
        $back_url = admin_url('admin.php?page=' . $list_config['page']);
        ?>
        <div class="wrap nf-members-wrap">
            <h1>Eintrag bearbeiten</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="nf_update_submission">
                <input type="hidden" name="list" value="<?php echo esc_attr($list); ?>">
                <input type="hidden" name="index" value="<?php echo esc_attr($index); ?>">
                <?php wp_nonce_field('nf_update_submission_' . $list . '_' . $index, 'nf_update_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="nf-confirmed-at">Bestätigt am</label></th>
                        <td><input type="datetime-local" id="nf-confirmed-at" name="confirmed_at" value="<?php echo esc_attr($confirmed_at); ?>" required></td>
                    </tr>
                    <?php if($list === 'cancellations'): ?>
                        <tr>
                            <th><label for="nf-submission-email">E-Mail</label></th>
                            <td><input type="email" id="nf-submission-email" name="fields[cancellation_email]" value="<?php echo esc_attr($email); ?>" class="regular-text" required></td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach($fields as $meta_key => $field_config):
                        if($list === 'cancellations' && $meta_key === 'cancellation_email') continue;
                        $value = $meta[$meta_key] ?? '';
                        if($meta_key === 'nf_email' && $value === ''){
                            $value = $email;
                        }
                        if(!is_scalar($value)) $value = '';
                        $input_id = 'nf-field-' . sanitize_html_class($meta_key);
                        ?>
                        <tr>
                            <th><label for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($field_config['label']); ?></label></th>
                            <td>
                                <?php if(!empty($field_config['options'])): ?>
                                    <select id="<?php echo esc_attr($input_id); ?>" name="fields[<?php echo esc_attr($meta_key); ?>]">
                                        <option value="">–</option>
                                        <?php foreach($field_config['options'] as $option_value => $option_label): ?>
                                            <option value="<?php echo esc_attr($option_value); ?>" <?php selected((string)$value, (string)$option_value); ?>>
                                                <?php echo esc_html($option_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input
                                        type="<?php echo esc_attr(($field_config['type'] ?? '') === 'email' || $meta_key === 'nf_email' ? 'email' : 'text'); ?>"
                                        id="<?php echo esc_attr($input_id); ?>"
                                        name="fields[<?php echo esc_attr($meta_key); ?>]"
                                        value="<?php echo esc_attr((string)$value); ?>"
                                        class="regular-text"
                                        <?php if(!empty($field_config['maxlength'])): ?>maxlength="<?php echo esc_attr((string)$field_config['maxlength']); ?>"<?php endif; ?>
                                    >
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button('Änderungen speichern'); ?>
                <a class="button" href="<?php echo esc_url($back_url); ?>">Abbrechen</a>
            </form>
        </div>
        <?php
        return true;
    }

    private function render_row_actions(string $list, $index): void {
        $index = (string)$index;
        $edit_url = add_query_arg([
            'page' => $this->get_submission_list_config($list)['page'],
            'action' => 'edit',
            'list' => $list,
            'index' => $index,
        ], admin_url('admin.php'));
        ?>
        <a class="button" href="<?php echo esc_url(wp_nonce_url($edit_url, 'nf_edit_submission_' . $list . '_' . $index)); ?>">
            Bearbeiten
        </a>
        <?php $this->render_delete_form($list, $index); ?>
        <?php
    }

    private function render_delete_form(string $list, $index): void {
        $index = (string)$index;
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="nf_delete_submission">
            <input type="hidden" name="list" value="<?php echo esc_attr($list); ?>">
            <input type="hidden" name="index" value="<?php echo esc_attr($index); ?>">
            <?php wp_nonce_field('nf_delete_submission_' . $list . '_' . $index, 'nf_delete_nonce'); ?>
            <button type="submit" class="button button-link-delete" onclick="return confirm('Diesen Eintrag wirklich löschen?');">
                Löschen
            </button>
        </form>
        <?php
    }

    private function render_delete_notice(): void {
        if(isset($_GET['deleted']) && is_scalar($_GET['deleted']) && (string)wp_unslash($_GET['deleted']) === '1'){
            echo '<div class="notice notice-success is-dismissible"><p>Der Eintrag wurde gelöscht.</p></div>';
        }
        if(isset($_GET['updated']) && is_scalar($_GET['updated']) && (string)wp_unslash($_GET['updated']) === '1'){
            echo '<div class="notice notice-success is-dismissible"><p>Der Eintrag wurde aktualisiert.</p></div>';
        }
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
        Unterseite 1: Bestätigte Bestellungen
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
        $member_fields = array_diff_key(
            $this->get_member_fields(),
            array_flip(['nf_ort', 'nf_strasze'])
        );
        $headers = ['Bestätigt am'];
        foreach($member_fields as $cfg){
            $headers[] = $cfg['label'] === 'Kundennummer' ? 'Kdr' : $cfg['label'];
        }

        $rows = [];
        foreach($submissions as $submission){
            $fields = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
            $row = [$this->format_confirmed_at($submission)];
            foreach($member_fields as $meta_key => $cfg){
                $value = $fields[$meta_key] ?? '';
                if($meta_key === 'nf_email' && $value === ''){
                    $value = $submission['email'] ?? '';
                }
                $value = $this->display_custom_field_value($meta_key, $value, $member_fields);
                $row[] = $value !== '' ? $value : '–';
            }
            $rows[] = $row;
        }

        $this->send_csv_download('bestellungen', $headers, $rows);
    }

    public function export_hosting_csv(){
        if(!current_user_can('manage_options')){
            wp_die('Keine Berechtigung zum Exportieren der Hosting-Bestellungen.');
        }
        check_admin_referer('nf_export_hosting_csv');

        $submissions = $this->get_hosting_submissions();
        $headers = ['Bestätigt am'];
        foreach($this->hosting_fields as $cfg){
            $headers[] = $cfg['label'];
        }

        $rows = [];
        foreach($submissions as $entry){
            $submission = $entry['submission'];
            $fields = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
            $row = [$this->format_confirmed_at($submission)];
            foreach($this->hosting_fields as $meta_key => $cfg){
                $value = $fields[$meta_key] ?? '';
                if($meta_key === 'nf_email' && $value === ''){
                    $value = $submission['email'] ?? '';
                }
                $value = $this->display_custom_field_value($meta_key, $value, $this->hosting_fields);
                $row[] = $value !== '' ? $value : '–';
            }
            $rows[] = $row;
        }

        $this->send_csv_download('hosting-bestellungen', $headers, $rows);
    }

    public function export_cancellations_csv(){
        if(!current_user_can('manage_options')){
            wp_die('Keine Berechtigung zum Exportieren der Kündigungen.');
        }
        check_admin_referer('nf_export_cancellations_csv');

        $cancellations = get_option('nf_member_confirmed_cancellations', []);
        if(!is_array($cancellations)) $cancellations = [];
        $cancellations = array_reverse($cancellations);
        $headers = ['Bestätigt am', 'E-Mail'];
        foreach($this->cancellation_fields as $key => $cfg){
            if($key !== 'cancellation_email'){
                $headers[] = $cfg['label'];
            }
        }

        $rows = [];
        foreach($cancellations as $cancellation){
            $fields = is_array($cancellation['meta'] ?? null) ? $cancellation['meta'] : [];
            $row = [
                $this->format_confirmed_at($cancellation),
                $cancellation['email'] ?? '–',
            ];
            foreach($this->cancellation_fields as $key => $cfg){
                if($key !== 'cancellation_email'){
                    $value = $fields[$key] ?? '';
                    $row[] = $value !== '' ? $value : '–';
                }
            }
            $rows[] = $row;
        }

        $this->send_csv_download('kuendigungen', $headers, $rows);
    }

    private function get_hosting_submissions(): array {
        $hostings = get_option('nf_member_confirmed_hosting_submissions', []);
        if(!is_array($hostings)) $hostings = [];
        $submissions = [];
        foreach($hostings as $index => $submission){
            $submissions[] = [
                'list' => 'hosting',
                'index' => $index,
                'submission' => $submission,
            ];
        }

        $legacy_submissions = get_option('nf_member_confirmed_submissions', []);
        if(is_array($legacy_submissions)){
            foreach($legacy_submissions as $index => $submission){
                if(is_array($submission) && ($submission['field_set'] ?? '') === 'hosting'){
                    $submissions[] = [
                        'list' => 'hosting_legacy',
                        'index' => $index,
                        'submission' => $submission,
                    ];
                }
            }
        }

        usort($submissions, function($a, $b){
            return strcmp(
                (string)($b['submission']['confirmed_at'] ?? ''),
                (string)($a['submission']['confirmed_at'] ?? '')
            );
        });
        return $submissions;
    }

    private function format_confirmed_at(array $submission): string {
        return !empty($submission['confirmed_at'])
            ? (new DateTimeImmutable($submission['confirmed_at'], new DateTimeZone('Europe/Berlin')))->format('d.m.Y H:i')
            : '–';
    }

    private function send_csv_download(string $filename, array $headers, array $rows): void {
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename . '-' . gmdate('Y-m-d') . '.csv') . '"');

        $output = fopen('php://output', 'w');
        if($output === false){
            wp_die('Die CSV-Datei konnte nicht erstellt werden.');
        }
        fwrite($output, "\xEF\xBB\xBF");
        $this->write_csv_row($output, $headers);
        foreach($rows as $row){
            $this->write_csv_row($output, $row);
        }

        fclose($output);
        exit;
    }

    private function write_csv_row($output, array $row): void {
        foreach($row as &$value){
            if(is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value)){
                $value = "'".$value;
            }
        }
        unset($value);
        fputcsv($output, $row, ';', '"', '\\');
    }

    public function page_mitglieder_liste(){
        if($this->render_submission_editor('members')) return;
        $submissions = get_option('nf_member_confirmed_submissions', []);
        if(!is_array($submissions)) $submissions = [];
        $submissions = array_reverse($submissions, true);
        $count = count($submissions);
        $member_fields = array_diff_key(
            $this->get_member_fields(),
            array_flip(['nf_ort', 'nf_strasze'])
        );
        ?>
        <div class="wrap nf-members-wrap">
            <?php $this->render_delete_notice(); ?>
            <h1>
                <span class="dashicons dashicons-groups" style="font-size:26px;vertical-align:middle;margin-right:6px;"></span>
                Bestätigte Bestellungen
            </h1>
            <div class="nf-stat-row">
                <div class="nf-stat">
                    <strong><?php echo esc_html($count); ?></strong>
                    <span>Bestellungen</span>
                </div>
                <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=nf_export_orders_csv'), 'nf_export_orders_csv')); ?>">
                    CSV herunterladen
                </a>
            </div>
            <table class="nf-members-table">
                <thead>
                    <tr>
                        <th>Bestätigt am</th>
                        <?php foreach ($member_fields as $cfg): ?>
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
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($submissions)): ?>
                    <tr><td colspan="<?php echo esc_attr(count($member_fields) + 2); ?>" class="nf-empty">Noch keine bestätigten Anträge vorhanden.</td></tr>
                <?php else: foreach($submissions as $index => $submission):
                    $fields = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
                    $confirmed_at = !empty($submission['confirmed_at'])
                        ? (new DateTimeImmutable($submission['confirmed_at'], new DateTimeZone('Europe/Berlin')))->format('d.m.Y H:i')
                        : '–';
                    ?>
                    <tr>
                        <td><?php echo esc_html($confirmed_at); ?></td>
                        <?php foreach($member_fields as $meta_key => $cfg):
                            $value = $fields[$meta_key] ?? '';
                            if($meta_key === 'nf_email' && $value === '') {
                                $value = $submission['email'] ?? '';
                            }
                            $value = $this->display_custom_field_value($meta_key, $value, $member_fields);
                            ?>
                            <td><?php echo esc_html($value !== '' ? $value : '–'); ?></td>
                        <?php endforeach; ?>
                        <td><?php $this->render_row_actions('members', $index); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function page_kunden_liste(){
        if($this->render_submission_editor('hosting')) return;
        $submissions = $this->get_hosting_submissions();
        $member_fields = $this->hosting_fields;
        ?>
        <div class="wrap nf-members-wrap">
            <?php $this->render_delete_notice(); ?>
            <h1>
                <span class="dashicons dashicons-groups" style="font-size:26px;vertical-align:middle;margin-right:6px;"></span>
                Bestätigte Hosting-Bestellungen
            </h1>
            <div class="nf-stat-row">
                <div class="nf-stat">
                    <strong><?php echo esc_html(count($submissions)); ?></strong>
                    <span>Hosting-Bestellungen</span>
                </div>
                <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=nf_export_hosting_csv'), 'nf_export_hosting_csv')); ?>">
                    CSV herunterladen
                </a>
            </div>
            <table class="nf-members-table">
                <thead>
                    <tr>
                        <th>Bestätigt am</th>
                        <?php foreach($member_fields as $cfg): ?>
                            <th><?php echo esc_html($cfg['label']); ?></th>
                        <?php endforeach; ?>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($submissions)): ?>
                    <tr><td colspan="<?php echo esc_attr(count($member_fields) + 2); ?>" class="nf-empty">Noch keine Hosting-Bestellungen bestätigt.</td></tr>
                <?php else: foreach($submissions as $entry):
                    $submission = $entry['submission'];
                    $fields = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
                    $confirmed_at = !empty($submission['confirmed_at'])
                        ? (new DateTimeImmutable($submission['confirmed_at'], new DateTimeZone('Europe/Berlin')))->format('d.m.Y H:i')
                        : '–';
                    ?>
                    <tr>
                        <td><?php echo esc_html($confirmed_at); ?></td>
                        <?php foreach($member_fields as $meta_key => $cfg):
                            $value = $fields[$meta_key] ?? '';
                            if($meta_key === 'nf_email' && $value === ''){
                                $value = $submission['email'] ?? '';
                            }
                            $value = $this->display_custom_field_value($meta_key, $value, $member_fields);
                            ?>
                            <td><?php echo esc_html($value !== '' ? $value : '–'); ?></td>
                        <?php endforeach; ?>
                        <td><?php $this->render_row_actions($entry['list'], $entry['index']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function page_cancellations(){
        if(!current_user_can('manage_options')){
            wp_die('Keine Berechtigung.');
        }
        if($this->render_submission_editor('cancellations')) return;

        $cancellations = get_option('nf_member_confirmed_cancellations', []);
        if(!is_array($cancellations)) $cancellations = [];
        $cancellations = array_reverse($cancellations, true);
        ?>
        <div class="wrap nf-members-wrap">
            <?php $this->render_delete_notice(); ?>
            <h1>Bestätigte Kündigungen</h1>
            <div class="nf-stat-row">
                <div class="nf-stat">
                    <strong><?php echo esc_html(count($cancellations)); ?></strong>
                    <span>Bestätigte Kündigungen</span>
                </div>
                <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=nf_export_cancellations_csv'), 'nf_export_cancellations_csv')); ?>">
                    CSV herunterladen
                </a>
            </div>
            <table class="nf-members-table">
                <thead>
                    <tr>
                        <th>Bestätigt am</th>
                        <th>E-Mail</th>
                        <?php foreach($this->cancellation_fields as $key => $cfg):
                            if($key === 'cancellation_email') continue;
                            ?>
                            <th><?php echo esc_html($cfg['label']); ?></th>
                        <?php endforeach; ?>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($cancellations)): ?>
                        <tr>
                            <td colspan="<?php echo esc_attr(count($this->cancellation_fields) + 2); ?>" class="nf-empty">
                                Noch keine Kündigungen bestätigt.
                            </td>
                        </tr>
                    <?php else: foreach($cancellations as $index => $cancellation):
                        $fields = is_array($cancellation['meta'] ?? null) ? $cancellation['meta'] : [];
                        $confirmed_at = !empty($cancellation['confirmed_at'])
                            ? (new DateTimeImmutable($cancellation['confirmed_at'], new DateTimeZone('Europe/Berlin')))->format('d.m.Y H:i')
                            : '–';
                        ?>
                        <tr>
                            <td><?php echo esc_html($confirmed_at); ?></td>
                            <td><?php echo esc_html($cancellation['email'] ?? '–'); ?></td>
                            <?php foreach($this->cancellation_fields as $key => $cfg):
                                if($key === 'cancellation_email') continue;
                                $value = $fields[$key] ?? '';
                                ?>
                                <td><?php echo esc_html($value !== '' ? $value : '–'); ?></td>
                            <?php endforeach; ?>
                            <td><?php $this->render_row_actions('cancellations', $index); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mailfooter(){
        $footer = "<br><br>Mit freundlichen Grüßen<br>
        Maik Pluschke<br>
        <a href='https://dkm.team/' style='font-size:12px;color:#000000 !important; text-decoration:none;'>
            <strong>DESIGN &amp; KOMMUNIKATION</strong><br>IM MODULBÜRO
        </a>";

        return $footer;
    }
}

new NF_Member_Registration();