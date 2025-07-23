<?php
/*
Plugin Name: QR Darovací Formulář
Description: Darovací formulář s QR kódem, správu účtů a předdefinovaných poznámek. Vytvořeno pro https://stastny-usmev.cz
Version: 1.0
Author: Adam Hornof
Author URI: https://adamhornof.cz
*/

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
            'QR Darovací Formulář',
            'QR Darovací Formulář',
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
        // sanitize input before saving if needed
        return $input;
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
            <h1>QR Darovací Formulář – Nastavení účtů</h1>
            <p>Zde můžete přidávat a upravovat bankovní účty a předdefinované poznámky pro QR platbu.</p>
            <p><strong>Jak to funguje:</strong></p>
            <ul>
                <li>Každý účet má své číslo účtu a kód banky.</li>
                <li>Ke každému účtu můžete nadefinovat více poznámek, každou na nový řádek.</li>
                <li>Při použití shortcode [qr_darovaci_formular ucet] může návštěvník vybrat účet a poznámku, zadat své jméno a částku.</li>
                <li>V poznámce může být proměnná <code>{{jmeno}}</code>, která bude nahrazena zadaným jménem.</li>
            </ul>

            <h2>Seznam účtů</h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Jméno účtu</th>
                        <th>Číslo účtu</th>
                        <th>Kód banky</th>
                        <th>Poznámky</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($accounts)) : ?>
                    <tr><td colspan="5">Žádné účty nejsou nastaveny.</td></tr>
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
                                <input type="submit" class="button button-link-delete" value="Smazat" onclick="return confirm('Opravdu chcete smazat tento účet?');">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <h2>Přidat nový účet</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('add_account'); ?>
                <input type="hidden" name="action" value="add_account">
                <table class="form-table">
                    <tr>
                        <th><label for="name">Jméno účtu</label></th>
                        <td><input name="name" type="text" id="name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="account">Číslo účtu (bez lomítka)</label></th>
                        <td><input name="account" type="text" id="account" required class="regular-text" placeholder="55552005"></td>
                    </tr>
                    <tr>
                        <th><label for="bank_code">Kód banky</label></th>
                        <td><input name="bank_code" type="text" id="bank_code" required class="regular-text" placeholder="2010"></td>
                    </tr>
                    <tr>
                        <th><label for="notes">Poznámky (jedna na řádek, použijte <code>{{jmeno}}</code> pro jméno dárce)</label></th>
                        <td><textarea name="notes" id="notes" rows="5" class="large-text"></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Přidat účet'); ?>
            </form>
        </div>
        <?php
    }

    public function handle_add_account() {
        if (!current_user_can('manage_options')) {
            wp_die('Nemáte oprávnění.');
        }
        check_admin_referer('add_account');

        $name = sanitize_text_field($_POST['name'] ?? '');
        $account = preg_replace('/[^0-9]/', '', $_POST['account'] ?? '');
        $bank_code = preg_replace('/[^0-9]/', '', $_POST['bank_code'] ?? '');
        $notes_raw = $_POST['notes'] ?? '';
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
            wp_die('Nemáte oprávnění.');
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
            return '<p>Darovací formulář není zatím nastaven. Kontaktujte správce webu.</p>';
        }

        ob_start();
        ?>
        <form id="qr-dar-form" style="max-width: 400px; margin: 2rem auto; text-align: center;">
            <select id="account-select" required style="padding: 0.5rem; width: 100%; margin-bottom: 1rem;">
                <option value="" disabled selected>Vyberte účet</option>
                <?php foreach ($accounts as $key => $acc) : ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($acc['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <select id="note-select" required style="padding: 0.5rem; width: 100%; margin-bottom: 1rem;" disabled>
                <option value="" disabled selected>Vyberte poznámku</option>
            </select>

            <input type="text" id="jmeno" placeholder="Jméno a příjmení" required style="padding: 0.5rem; width: 100%; margin-bottom: 1rem;">

            <input type="number" id="castka" placeholder="Částka (Kč)" required min="1" style="padding: 0.5rem; width: 100%; margin-bottom: 1rem;">

            <button type="submit" style="padding: 0.5rem 1rem; background-color: #0073aa; color: #fff; border: none; cursor: pointer;">Vygenerovat QR kód</button>
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
                        opt.textContent = 'Žádné poznámky';
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
                    <p><strong>Naskenujte QR kód v bankovní aplikaci:</strong></p>
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