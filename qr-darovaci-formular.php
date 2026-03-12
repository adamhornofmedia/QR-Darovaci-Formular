<?php
/*
Plugin Name: QR Darovací Formulář
Plugin URI: https://github.com/adamhornofmedia/QR-Darovaci-Formular/tree/main
Description: Darovací formulář s QR platbou.
Version: 2.1.2
Author: Adam Hornof
Author URI: https://hornof.dev
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: qr-darovaci-formular
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Přidání odkazu "Nastavení" do seznamu pluginů
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=qr-darovaci-formular') . '">' . esc_html__('Nastavení', 'qr-darovaci-formular') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

class QR_Darovaci_Formular {

    private $option_name = 'qr_darovaci_formular_accounts';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('qr_darovaci_formular', [$this, 'shortcode']);
        add_action('admin_post_add_account', [$this, 'handle_add_account']);
        add_action('admin_post_delete_account', [$this, 'handle_delete_account']);
    }

    public function add_admin_menu() {
        add_menu_page(
            esc_html__('QR Darovací Formulář', 'qr-darovaci-formular'),
            esc_html__('QR Darovací Formulář', 'qr-darovaci-formular'),
            'manage_options',
            'qr-darovaci-formular',
            [$this, 'settings_page'],
            'dashicons-money-alt',
            30
        );
    }

    public function register_settings() {
        register_setting('qr_darovaci_formular_group', $this->option_name, [
            'sanitize_callback' => [$this, 'sanitize_accounts'],
        ]);
    }

    public function sanitize_accounts($input) {
        if (!is_array($input)) return [];
        $sanitized = [];
        foreach ($input as $acc) {
            if (!isset($acc['name'], $acc['account'], $acc['bank_code'], $acc['notes']) || !is_array($acc['notes'])) continue;
            $sanitized[] = [
                'name'      => sanitize_text_field($acc['name']),
                'account'   => preg_replace('/[^0-9]/', '', $acc['account']),
                'prefix'    => preg_replace('/[^0-9]/', '', $acc['prefix'] ?? ''),
                'bank_code' => preg_replace('/[^0-9]/', '', $acc['bank_code']),
                'notes'     => array_values(array_filter(array_map('sanitize_text_field', $acc['notes']))),
            ];
        }
        return $sanitized;
    }

    public function get_accounts() {
        $accounts = get_option($this->option_name, []);
        if (!is_array($accounts)) $accounts = [];
        return $accounts;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;

        $accounts = $this->get_accounts();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('QR Darovací Formulář – Nastavení', 'qr-darovaci-formular'); ?></h1>

            <div style="background:#fff;border:1px solid #c3c4c7;border-left:4px solid #2271b1;padding:16px 20px;margin:20px 0;max-width:800px;">
                <h2 style="margin-top:0;"><?php esc_html_e('Návod k použití', 'qr-darovaci-formular'); ?></h2>

                <h3><?php esc_html_e('1. Přidejte bankovní účet', 'qr-darovaci-formular'); ?></h3>
                <p><?php esc_html_e('V části „Přidat nový účet" níže vyplňte:', 'qr-darovaci-formular'); ?></p>
                <ul style="list-style:disc;margin-left:1.5em;">
                    <li><strong><?php esc_html_e('Jméno účtu', 'qr-darovaci-formular'); ?></strong> – <?php esc_html_e('zobrazí se návštěvníkům ve formuláři (např. „Hlavní sbírka").', 'qr-darovaci-formular'); ?></li>
                    <li><strong><?php esc_html_e('Číslo účtu', 'qr-darovaci-formular'); ?></strong> – <?php esc_html_e('pouze číslice, bez lomítka (např. 55552005).', 'qr-darovaci-formular'); ?></li>
                    <li><strong><?php esc_html_e('Prefix účtu', 'qr-darovaci-formular'); ?></strong> – <?php esc_html_e('nepovinné, předčíslí účtu (např. 19).', 'qr-darovaci-formular'); ?></li>
                    <li><strong><?php esc_html_e('Kód banky', 'qr-darovaci-formular'); ?></strong> – <?php esc_html_e('čtyřmístný kód banky (např. 2010 pro Fio banku).', 'qr-darovaci-formular'); ?></li>
                    <li><strong><?php esc_html_e('Poznámky', 'qr-darovaci-formular'); ?></strong> – <?php esc_html_e('předdefinované zprávy pro příjemce platby, každá na samostatném řádku.', 'qr-darovaci-formular'); ?>
                        <?php esc_html_e('Šablona může obsahovat proměnnou', 'qr-darovaci-formular'); ?> <code>{{jmeno}}</code>, <?php esc_html_e('která bude automaticky nahrazena jménem, které dárce zadá do formuláře (např. „Dar – {{jmeno}}").', 'qr-darovaci-formular'); ?></li>
                </ul>

                <h3><?php esc_html_e('2. Vložte formulář na stránku', 'qr-darovaci-formular'); ?></h3>
                <p><?php esc_html_e('Na libovolné stránce nebo příspěvku vložte shortcode:', 'qr-darovaci-formular'); ?></p>
                <pre style="background:#f0f0f1;padding:10px 14px;border-radius:3px;display:inline-block;">[qr_darovaci_formular]</pre>
                <p style="margin-top:10px;"><?php esc_html_e('Chcete-li zobrazit jen konkrétní účet, použijte parametr', 'qr-darovaci-formular'); ?> <code>ucet</code> <?php esc_html_e('s pořadovým číslem (počítáno od 0):', 'qr-darovaci-formular'); ?></p>
                <pre style="background:#f0f0f1;padding:10px 14px;border-radius:3px;display:inline-block;">[qr_darovaci_formular ucet=0]</pre>

                <h3><?php esc_html_e('3. Jak formulář funguje na webu', 'qr-darovaci-formular'); ?></h3>
                <ol style="list-style:decimal;margin-left:1.5em;">
                    <li><?php esc_html_e('Dárce vybere bankovní účet (pokud je jich více).', 'qr-darovaci-formular'); ?></li>
                    <li><?php esc_html_e('Vybere předdefinovanou poznámku (účel platby).', 'qr-darovaci-formular'); ?></li>
                    <li><?php esc_html_e('Zadá své jméno a příjmení.', 'qr-darovaci-formular'); ?></li>
                    <li><?php esc_html_e('Zadá částku v Kč.', 'qr-darovaci-formular'); ?></li>
                    <li><?php esc_html_e('Stiskne tlačítko – plugin vygeneruje QR kód ve formátu Czech QR platba (SPD), který lze naskenovat v mobilní bankovní aplikaci.', 'qr-darovaci-formular'); ?></li>
                </ol>
            </div>

            <h2><?php esc_html_e('Seznam účtů', 'qr-darovaci-formular'); ?></h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Jméno účtu', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Číslo účtu', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Prefix', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Kód banky', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Poznámky', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Akce', 'qr-darovaci-formular'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($accounts)) : ?>
                    <tr><td colspan="6"><?php echo esc_html__('Žádné účty nejsou nastaveny.', 'qr-darovaci-formular'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($accounts as $key => $acc) : ?>
                    <tr>
                        <td><?php echo esc_html($acc['name']); ?></td>
                        <td><?php echo esc_html($acc['account']); ?></td>
                        <td><?php echo esc_html($acc['prefix'] ?? ''); ?></td>
                        <td><?php echo esc_html($acc['bank_code']); ?></td>
                        <td><pre style="white-space: pre-wrap;"><?php echo esc_html(implode("\n", $acc['notes'] ?? [])); ?></pre></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                <?php wp_nonce_field('delete_account_' . $key); ?>
                                <input type="hidden" name="action" value="delete_account">
                                <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
                                <input type="submit" class="button button-link-delete" value="<?php esc_attr_e('Smazat', 'qr-darovaci-formular'); ?>" onclick="return confirm('<?php echo esc_js(__('Opravdu chcete smazat tento účet?', 'qr-darovaci-formular')); ?>');">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e('Přidat nový účet', 'qr-darovaci-formular'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('add_account'); ?>
                <input type="hidden" name="action" value="add_account">
                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php esc_html_e('Jméno účtu', 'qr-darovaci-formular'); ?></label></th>
                        <td><input name="name" type="text" id="name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="account"><?php esc_html_e('Číslo účtu (bez lomítka)', 'qr-darovaci-formular'); ?></label></th>
                        <td><input name="account" type="text" id="account" required class="regular-text" placeholder="<?php echo esc_attr__('55552005', 'qr-darovaci-formular'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="prefix"><?php esc_html_e('Prefix účtu (nepovinné)', 'qr-darovaci-formular'); ?></label></th>
                        <td><input name="prefix" type="text" id="prefix" class="regular-text" placeholder="<?php echo esc_attr__('19', 'qr-darovaci-formular'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="bank_code"><?php esc_html_e('Kód banky', 'qr-darovaci-formular'); ?></label></th>
                        <td><input name="bank_code" type="text" id="bank_code" required class="regular-text" placeholder="<?php echo esc_attr__('2010', 'qr-darovaci-formular'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="notes"><?php esc_html_e('Poznámky (jedna na řádek, použijte', 'qr-darovaci-formular'); ?> <code>{{jmeno}}</code> <?php esc_html_e('pro jméno dárce)', 'qr-darovaci-formular'); ?></label></th>
                        <td><textarea name="notes" id="notes" rows="5" class="large-text"></textarea></td>
                    </tr>
                </table>
                <?php submit_button(esc_html__('Přidat účet', 'qr-darovaci-formular')); ?>
            </form>

            <hr>
            <h2>🫶 <?php esc_html_e('Poděkování', 'qr-darovaci-formular'); ?></h2>
            <p><?php echo wp_kses(
                __('Plugin obsahuje <a href="https://github.com/davidshimjs/qrcodejs" target="_blank" rel="noopener noreferrer">qrcode.js od davidshimjs</a>.', 'qr-darovaci-formular'),
                ['a' => ['href' => [], 'target' => [], 'rel' => []]]
            ); ?></p>
        </div>
        <?php
    }

    public function handle_add_account() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemáte oprávnění.', 'qr-darovaci-formular'));
        }
        check_admin_referer('add_account');

        $name      = isset($_POST['name'])      ? sanitize_text_field(wp_unslash($_POST['name']))                   : '';
        $account   = isset($_POST['account'])   ? preg_replace('/[^0-9]/', '', wp_unslash($_POST['account']))   : '';
        $prefix    = isset($_POST['prefix'])    ? preg_replace('/[^0-9]/', '', wp_unslash($_POST['prefix']))    : '';
        $bank_code = isset($_POST['bank_code']) ? preg_replace('/[^0-9]/', '', wp_unslash($_POST['bank_code'])) : '';
        $notes_raw = isset($_POST['notes'])     ? wp_unslash($_POST['notes'])                                   : '';

        $notes = array_values(array_filter(array_map('sanitize_text_field', explode("\n", $notes_raw))));

        if (!$name || !$account || !$bank_code) {
            wp_redirect(add_query_arg('message', 'error', wp_get_referer()));
            exit;
        }

        $accounts = $this->get_accounts();

        $accounts[] = [
            'name'      => $name,
            'account'   => $account,
            'prefix'    => $prefix,
            'bank_code' => $bank_code,
            'notes'     => $notes,
        ];

        update_option($this->option_name, $accounts);

        wp_redirect(add_query_arg('message', 'added', wp_get_referer()));
        exit;
    }

    public function handle_delete_account() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemáte oprávnění.', 'qr-darovaci-formular'));
        }
        $key = intval($_POST['key'] ?? -1);
        check_admin_referer('delete_account_' . $key);

        $accounts = $this->get_accounts();

        if (isset($accounts[$key])) {
            unset($accounts[$key]);
            $accounts = array_values($accounts); // reindex
            update_option($this->option_name, $accounts);
        }

        wp_redirect(add_query_arg('message', 'deleted', wp_get_referer()));
        exit;
    }

    public function shortcode() {
        $accounts = $this->get_accounts();
        if (empty($accounts)) {
            return '<p>' . esc_html__('Darovací formulář není zatím nastaven. Kontaktujte správce webu.', 'qr-darovaci-formular') . '</p>';
        }

        wp_enqueue_script( 'qrcodejs', plugin_dir_url( __FILE__ ) . 'js/qrcode.min.js', [], '1.0.0', true );

        ob_start();
        ?>
        <style>
        /*
         * QR platba – grafická pravidla dle specifikace Czech QR platba (ČBA)
         * T = velikost nejmenšího elementu (modulu) QR kódu
         * qrcode.min.js generuje tabulku BEZ vlastní Tiché zóny.
         * Verze 5 → 37 modulů → T = 256/37 ≈ 7 px  |  4T ≈ 28 px  |  2T ≈ 14 px
         */
        .qr-platba-wrap {
            display: inline-block;
            text-align: left;
        }
        /*
         * Ohraničení QR kódu: 3 strany (top, left, right).
         * Spodní hrana je přerušená textem – realizována ve .qr-platba-bottom.
         * Tichá zóna (4T = 28 px) = padding.
         */
        .qr-platba-box {
            border-top:   1.5pt solid #000000;
            border-left:  1.5pt solid #000000;
            border-right: 1.5pt solid #000000;
            border-bottom: none;
            padding: 28px;
            display: block;
            background: #ffffff;
            line-height: 0;
        }
        /*
         * Spodní lišta (nahrazuje spodní border):
         * [čára 2T] [text "QR Platba"] [čára do konce]
         */
        .qr-platba-bottom {
            display: flex;
            align-items: center;
        }
        /* Levý segment spodní čáry – Mezera označení: 2T = 14 px */
        .qr-platba-corner-left {
            width: 14px;
            flex-shrink: 0;
            border-top: 1.5pt solid #000000;
            transform: translateY(-14px);
        }
        /* Pravý segment spodní čáry – vyplní zbytek šířky */
        .qr-platba-corner-right {
            flex: 1;
            border-top: 1.5pt solid #000000;
            transform: translateY(-14px);
        }
        /*
         * Označení QR kódu: Arial Bold, 16T × 4T
         * Vertikálně pod hranu ohraničení, horizontálně na hranu QR kódu.
         * Text sedí v mezeře spodní čáry.
         */
        .qr-platba-label {
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 20px;
            line-height: 28px;  /* 4T */
            color: #000000;
            white-space: nowrap;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            margin: 0 10px;
            transform: translateY(-14px);
        }
        </style>
        <form id="qr-dar-form" style="max-width: 400px; margin: 2rem auto; text-align: center;">
            <select id="account-select" required style="padding: 0.5rem; width: 100%; margin-bottom: 1rem;">
                <option value="" disabled selected><?php echo esc_html__('Vyberte účet', 'qr-darovaci-formular'); ?></option>
                <?php foreach ($accounts as $key => $acc) : ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($acc['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="note-select" required style="padding: 0.5rem; width: 100%; margin-bottom: 1rem;" disabled>
                <option value="" disabled selected><?php echo esc_html__('Vyberte poznámku', 'qr-darovaci-formular'); ?></option>
            </select>

            <input type="text" id="jmeno" placeholder="<?php echo esc_attr__('Jméno a příjmení', 'qr-darovaci-formular'); ?>" required style="padding: 0.5rem; width: 100%; margin-bottom: 1rem;">

            <input type="number" id="castka" placeholder="<?php echo esc_attr__('Částka (Kč)', 'qr-darovaci-formular'); ?>" required min="1" style="padding: 0.5rem; width: 100%; margin-bottom: 1rem;">

            <button type="submit" style="padding: 0.5rem 1rem; background-color: #0073aa; color: #fff; border: none; cursor: pointer;"><?php echo esc_html__('Vygenerovat QR kód', 'qr-darovaci-formular'); ?></button>
        </form>
        <div id="qr-output" style="text-align: center; margin-top: 1.5rem;"></div>

        <script>
        (function(){
            const accounts      = <?php echo wp_json_encode( array_values( $accounts ) ); ?>;
            const accountSelect = document.getElementById('account-select');
            const noteSelect    = document.getElementById('note-select');
            const form          = document.getElementById('qr-dar-form');
            const qrOutput      = document.getElementById('qr-output');

            // Výpočet mod97 pro IBAN (ISO 7064)
            function mod97(numStr) {
                let r = 0;
                for (let i = 0; i < numStr.length; i++) r = (r * 10 + parseInt(numStr[i], 10)) % 97;
                return r;
            }

            // Převod číslo účtu / kód banky → IBAN (CZ)
            function accountToIBAN(accountNumber, bankCode, prefix) {
                prefix = (prefix || '').replace(/\D/g, '');
                const bban  = bankCode.padStart(4, '0') + prefix.padStart(6, '0') + accountNumber.padStart(10, '0');
                const check = (98 - mod97(bban + '1235' + '00')).toString().padStart(2, '0');
                return 'CZ' + check + bban;
            }

            // Odstranění diakritiky (QR knihovna kóduje jako Latin-1, kde ě/š/č/ř/ž/ů/… nejsou)
            function removeDiacritics(str) {
                return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }

            // Sestavení řetězce ve formátu Czech QR platba (SPD)
            function buildSPD(iban, amount, message) {
                const msg   = removeDiacritics(message).replace(/\*/g, '').substring(0, 60);
                const parts = ['SPD', '1.0', 'ACC:' + iban, 'AM:' + parseFloat(amount).toFixed(2), 'CC:CZK'];
                if (msg) parts.push('MSG:' + msg);
                return parts.join('*');
            }

            accountSelect.addEventListener('change', () => {
                const selected = accountSelect.value;
                noteSelect.innerHTML = '';
                noteSelect.disabled = true;

                if(selected !== '') {
                    const notes = accounts[selected].notes || [];
                    if(notes.length) {
                        notes.forEach(note => {
                            const opt = document.createElement('option');
                            opt.value = note;
                            opt.textContent = note.replace(/\s*\{\{jmeno\}\}\s*/gi, ' ').trim();
                            noteSelect.appendChild(opt);
                        });
                        noteSelect.disabled = false;
                    } else {
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = '<?php echo esc_js(esc_html__('Žádné poznámky', 'qr-darovaci-formular')); ?>';
                        noteSelect.appendChild(opt);
                        noteSelect.disabled = true;
                    }
                }
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const accKey       = accountSelect.value;
                const noteTemplate = noteSelect.value || '';
                const jmeno        = document.getElementById('jmeno').value.trim();
                const castka       = document.getElementById('castka').value.trim();

                if (!accKey || !noteTemplate || !jmeno || !castka) return;

                const acc     = accounts[parseInt(accKey, 10)];
                const message = noteTemplate.replace(/\{\{jmeno\}\}/gi, jmeno);
                const iban    = accountToIBAN(acc.account, acc.bank_code, acc.prefix || '');
                const spd     = buildSPD(iban, castka, message);

                // Vyčistíme výstup a přidáme elementy bez innerHTML (prevence XSS)
                qrOutput.innerHTML = '';

                const heading = document.createElement('p');
                const strong  = document.createElement('strong');
                strong.textContent = '<?php echo esc_js( esc_html__( 'Naskenujte QR kód v bankovní aplikaci:', 'qr-darovaci-formular' ) ); ?>';
                heading.appendChild(strong);
                qrOutput.appendChild(heading);

                // Wrapper dle grafické specifikace QR platba
                const wrap = document.createElement('div');
                wrap.className = 'qr-platba-wrap';

                const box = document.createElement('div');
                box.className = 'qr-platba-box';
                wrap.appendChild(box);

                // Spodní lišta: [2T čára] [QR Platba] [čára do konce]
                const bottom = document.createElement('div');
                bottom.className = 'qr-platba-bottom';

                const cornerL = document.createElement('span');
                cornerL.className = 'qr-platba-corner-left';
                bottom.appendChild(cornerL);

                const lbl = document.createElement('span');
                lbl.className = 'qr-platba-label';
                lbl.textContent = 'QR Platba';
                bottom.appendChild(lbl);

                const cornerR = document.createElement('span');
                cornerR.className = 'qr-platba-corner-right';
                bottom.appendChild(cornerR);

                wrap.appendChild(bottom);
                qrOutput.appendChild(wrap);

                const msgP = document.createElement('p');
                msgP.appendChild(document.createTextNode('<?php echo esc_js( esc_html__( 'Zpráva:', 'qr-darovaci-formular' ) ); ?> '));
                msgP.appendChild(document.createTextNode(message));
                qrOutput.appendChild(msgP);

                new QRCode(box, {
                    text:         spd,
                    width:        256,
                    height:       256,
                    colorDark:    '#000000',
                    colorLight:   '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}

new QR_Darovaci_Formular();