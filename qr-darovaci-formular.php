<?php
/*
Plugin Name: QR Darovací Formulář
Plugin URI: https://github.com/adamhornofmedia/QR-Darovaci-Formular/tree/main
Description: Darovací formulář s QR platbou.
Version: 1.1
Author: Adam Hornof
Author URI: https://adamhornof.cz
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: qr-darovaci-formular
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
*/

// Přidání odkazu "Nastavení" do seznamu pluginů
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=qr-darovaci-formular') . '">' . esc_html__('Nastavení', 'qr-darovaci-formular') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
        add_options_page(
            esc_html__('QR Darovací Formulář', 'qr-darovaci-formular'),
            esc_html__('QR Darovací Formulář', 'qr-darovaci-formular'),
            'manage_options',
            'qr-darovaci-formular',
            [$this, 'settings_page']
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
                'name' => sanitize_text_field($acc['name']),
                'account' => preg_replace('/[^0-9]/', '', $acc['account']),
                'bank_code' => preg_replace('/[^0-9]/', '', $acc['bank_code']),
                'notes' => array_filter(array_map('sanitize_text_field', $acc['notes'])),
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
            <h1><?php esc_html_e('QR Darovací Formulář – Nastavení účtů', 'qr-darovaci-formular'); ?></h1>
            <p><?php esc_html_e('Zde můžete přidávat a upravovat bankovní účty a předdefinované poznámky pro QR platbu.', 'qr-darovaci-formular'); ?></p>
            <p><strong><?php esc_html_e('Jak to funguje:', 'qr-darovaci-formular'); ?></strong></p>
            <ul>
                <li><?php esc_html_e('Každý účet má své číslo účtu a kód banky.', 'qr-darovaci-formular'); ?></li>
                <li><?php esc_html_e('Ke každému účtu můžete nadefinovat více poznámek, každou na nový řádek.', 'qr-darovaci-formular'); ?></li>
                <li><?php esc_html_e('Při použití shortcode [qr_darovaci_formular ucet] může návštěvník vybrat účet a poznámku, zadat své jméno a částku.', 'qr-darovaci-formular'); ?></li>
                <li><?php esc_html_e('V poznámce může být proměnná', 'qr-darovaci-formular'); ?> <code>{{jmeno}}</code>, <?php esc_html_e('která bude nahrazena zadaným jménem.', 'qr-darovaci-formular'); ?></li>
            </ul>

            <h2><?php esc_html_e('Seznam účtů', 'qr-darovaci-formular'); ?></h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Jméno účtu', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Číslo účtu', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Kód banky', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Poznámky', 'qr-darovaci-formular'); ?></th>
                        <th><?php echo esc_html__('Akce', 'qr-darovaci-formular'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($accounts)) : ?>
                    <tr><td colspan="5"><?php echo esc_html__('Žádné účty nejsou nastaveny.', 'qr-darovaci-formular'); ?></td></tr>
                <?php else: ?>
                    <?php foreach ($accounts as $key => $acc) : ?>
                    <tr>
                        <td><?php echo esc_html($acc['name']); ?></td>
                        <td><?php echo esc_html($acc['account']); ?></td>
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
        </div>
        <?php
    }

    public function handle_add_account() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Nemáte oprávnění.', 'qr-darovaci-formular'));
        }
        check_admin_referer('add_account');

        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $account = isset($_POST['account']) ? preg_replace('/[^0-9]/', '', wp_unslash($_POST['account'])) : '';
        $bank_code = isset($_POST['bank_code']) ? preg_replace('/[^0-9]/', '', wp_unslash($_POST['bank_code'])) : '';
        $notes_raw = isset($_POST['notes']) ? wp_unslash($_POST['notes']) : '';

        $notes = array_filter(array_map('sanitize_text_field', explode("\n", $notes_raw)));

        if (!$name || !$account || !$bank_code) {
            wp_redirect(add_query_arg('message', 'error', wp_get_referer()));
            exit;
        }

        $accounts = $this->get_accounts();

        $accounts[] = [
            'name' => $name,
            'account' => $account,
            'bank_code' => $bank_code,
            'notes' => $notes,
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

        ob_start();
        ?>
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
            const accounts = <?php echo json_encode($accounts); ?>;
            const accountSelect = document.getElementById('account-select');
            const noteSelect = document.getElementById('note-select');
            const form = document.getElementById('qr-dar-form');
            const qrOutput = document.getElementById('qr-output');

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
                            opt.textContent = note;
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

            form.addEventListener('submit', e => {
                e.preventDefault();
                const accKey = accountSelect.value;
                const noteTemplate = noteSelect.value || '';
                const jmeno = document.getElementById('jmeno').value.trim();
                const castka = document.getElementById('castka').value.trim();

                if (!accKey || !noteTemplate || !jmeno || !castka) return;

                const acc = accounts[accKey];
                // Nahradíme {{jmeno}} v poznámce
                let message = noteTemplate.replace(/{{jmeno}}/gi, jmeno);

                // Sestavíme QR API URL pro paylibo
                // Formát zprávy musí být url-encoded a bez mezer kolem -
                message = message.replace(/\s*–\s*/g, '–'); // mezera před i za pomlčkou na en-dash

                const url = `https://api.paylibo.com/paylibo/generator/czech/image?accountNumber=${acc.account}&bankCode=${acc.bank_code}&amount=${parseFloat(castka).toFixed(2)}&currency=CZK&message=${encodeURIComponent(message)}`;

                qrOutput.innerHTML = `
                    <p><strong><?php echo esc_html__('Naskenujte QR kód v bankovní aplikaci:', 'qr-darovaci-formular'); ?></strong></p>
                    <img src="${url}" alt="QR Platba" style="max-width: 100%; height: auto; margin-top: 1rem;">
                    <p>Zpráva: ${message}</p>
                `;
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}

new QR_Darovaci_Formular();