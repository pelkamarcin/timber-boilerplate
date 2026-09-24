<?php

namespace Site\App\Features;

/**
 * Maintenance mode — blocks non-logged-in visitors with a branded splash.
 *
 * Toggle (in priority order):
 *   1. wp-config.php constant: define( 'SFY_MAINTENANCE_MODE', true );
 *   2. WP option: wp option update sfy_maintenance_mode 1
 *   3. Admin: Ustawienia → Tryb konserwacji
 *
 * Constant takes precedence (deploy-friendly).
 *
 * Bypassed for: admin, login pages, AJAX, REST, cron, robots.txt,
 * and logged-in users with edit_posts capability.
 *
 * Customization:
 *   add_filter( 'sfy_maintenance_message', fn() => 'Twoja wiadomość' );
 *   add_filter( 'sfy_maintenance_title',   fn() => 'Twój tytuł' );
 *   add_filter( 'sfy_maintenance_eyebrow', fn() => 'Etykieta' );
 */
class MaintenanceMode {

    private const OPTION_KEY = 'sfy_maintenance_mode';

    public function __construct() {
        add_action( 'template_redirect',  [ $this, 'maybe_show_splash' ], 1 );
        add_action( 'admin_menu',         [ $this, 'register_settings_page' ] );
        add_action( 'admin_init',         [ $this, 'register_setting' ] );
        add_action( 'admin_notices',      [ $this, 'admin_notice' ] );
        add_action( 'admin_bar_menu',     [ $this, 'admin_bar_indicator' ], 100 );
    }

    public function is_enabled(): bool {
        if ( defined( 'SFY_MAINTENANCE_MODE' ) ) {
            return (bool) SFY_MAINTENANCE_MODE;
        }
        return (bool) get_option( self::OPTION_KEY, 0 );
    }

    private function should_bypass(): bool {
        if ( is_admin() ) return true;
        if ( wp_doing_ajax() ) return true;
        if ( wp_doing_cron() ) return true;
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return true;

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ( [ '/wp-login.php', '/wp-register.php', '/wp-cron.php', '/robots.txt', '/favicon.ico' ] as $path ) {
            if ( strpos( $request_uri, $path ) !== false ) return true;
        }

        if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
            return true;
        }

        return false;
    }

    public function maybe_show_splash(): void {
        if ( ! $this->is_enabled() ) return;
        if ( $this->should_bypass() ) return;

        status_header( 503 );
        nocache_headers();
        header( 'Retry-After: 3600' );
        header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

        $this->render_splash();
        exit;
    }

    private function render_splash(): void {
        $theme_uri  = get_template_directory_uri();
        $site_name  = get_bloginfo( 'name' );
        $admin_mail = get_bloginfo( 'admin_email' );

        // Pick up main CSS bundle (Vite manifest) so splash inherits theme tokens
        $css_url = '';
        $manifest_path = get_theme_file_path( 'dist/.vite/manifest.json' );
        if ( file_exists( $manifest_path ) ) {
            $manifest = json_decode( file_get_contents( $manifest_path ), true );
            if ( ! empty( $manifest['resources/js/index.ts']['css'][0] ) ) {
                $css_url = $theme_uri . '/dist/' . $manifest['resources/js/index.ts']['css'][0];
            }
        }

        // Logo path — try logo.svg, fallback to favicon
        $logo_url = file_exists( get_theme_file_path( 'logo.svg' ) )
            ? $theme_uri . '/logo.svg'
            : ( file_exists( get_theme_file_path( 'favicon.svg' ) ) ? $theme_uri . '/favicon.svg' : '' );

        $eyebrow = apply_filters( 'sfy_maintenance_eyebrow', __( 'Tryb konserwacji', 'sfy' ) );
        $title   = apply_filters( 'sfy_maintenance_title',   __( 'Pracujemy nad ulepszeniami.', 'sfy' ) );
        $message = apply_filters( 'sfy_maintenance_message', __( 'Strona jest tymczasowo niedostępna — przygotowujemy ją do otwarcia. Wrócimy wkrótce.', 'sfy' ) );

        ?><!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title><?php echo esc_html( $site_name . ' — ' . $eyebrow ); ?></title>
    <meta name="robots" content="noindex"/>
    <?php if ( $css_url ) : ?>
        <link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>"/>
    <?php endif; ?>
    <style>
        html, body { margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: var(--wp--preset--font-family--inter, system-ui, sans-serif);
            color: var(--wp--preset--color--ink, #2E2A26);
            background: var(--wp--preset--color--bg, #FBF8F2);
            display: flex; align-items: center; justify-content: center;
            padding: 40px 20px;
        }
        .sfy-maint { max-width: 640px; width: 100%; text-align: center; }
        .sfy-maint__logo img { height: 56px; width: auto; display: block; margin: 0 auto 48px; }
        .sfy-maint__eyebrow {
            font-size: 11px; font-weight: 500;
            letter-spacing: 0.18em; text-transform: uppercase;
            color: var(--wp--preset--color--sage-deep, #68AFAF);
            margin-bottom: 20px;
        }
        .sfy-maint__title {
            font-family: var(--wp--preset--font-family--fraunces, Georgia, serif);
            font-weight: 400;
            font-size: clamp(36px, 7vw, 64px);
            line-height: 1.05; letter-spacing: -0.02em;
            margin: 0 0 24px;
        }
        .sfy-maint__lead {
            font-family: var(--wp--preset--font-family--fraunces, Georgia, serif);
            font-size: clamp(17px, 2vw, 20px);
            line-height: 1.5;
            color: var(--wp--preset--color--ink-soft, #55504A);
            max-width: 520px;
            margin: 0 auto 40px;
        }
        .sfy-maint__contact {
            padding-top: 28px;
            border-top: 1px solid rgba(0,0,0,0.08);
            font-size: 13px;
            color: var(--wp--preset--color--ink-soft, #55504A);
        }
        .sfy-maint__contact a {
            color: var(--wp--preset--color--sage-deep, #68AFAF);
            text-decoration: none;
            border-bottom: 1px solid currentColor;
        }
    </style>
</head>
<body>
    <main class="sfy-maint" role="main">
        <?php if ( $logo_url ) : ?>
            <a class="sfy-maint__logo" href="/" aria-label="<?php echo esc_attr( $site_name ); ?>">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>"/>
            </a>
        <?php endif; ?>

        <div class="sfy-maint__eyebrow"><?php echo esc_html( $eyebrow ); ?></div>

        <h1 class="sfy-maint__title"><?php echo esc_html( $title ); ?></h1>

        <p class="sfy-maint__lead"><?php echo esc_html( $message ); ?></p>

        <?php if ( $admin_mail ) : ?>
            <div class="sfy-maint__contact">
                <?php esc_html_e( 'W sprawach pilnych:', 'sfy' ); ?>
                <a href="mailto:<?php echo esc_attr( $admin_mail ); ?>"><?php echo esc_html( $admin_mail ); ?></a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
        <?php
    }

    // ─── Admin: Settings page ─────────────────────────────────
    public function register_setting(): void {
        register_setting( 'general', self::OPTION_KEY, [
            'type'              => 'boolean',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ] );
    }

    public function register_settings_page(): void {
        add_options_page(
            __( 'Tryb konserwacji', 'sfy' ),
            __( 'Tryb konserwacji', 'sfy' ),
            'manage_options',
            'sfy-maintenance',
            [ $this, 'render_settings_page' ]
        );
    }

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $constant_set = defined( 'SFY_MAINTENANCE_MODE' );
        $enabled      = $this->is_enabled();

        if ( isset( $_POST['sfy_maintenance_save'] )
             && check_admin_referer( 'sfy_maintenance_save', 'sfy_maintenance_nonce' )
             && ! $constant_set ) {
            $val = ! empty( $_POST[ self::OPTION_KEY ] ) ? 1 : 0;
            update_option( self::OPTION_KEY, $val );
            $enabled = (bool) $val;
            echo '<div class="notice notice-success is-dismissible"><p>'
               . esc_html__( 'Zapisano ustawienia.', 'sfy' )
               . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Tryb konserwacji', 'sfy' ); ?></h1>

            <?php if ( $constant_set ) : ?>
                <div class="notice notice-info">
                    <p>
                        <strong><?php esc_html_e( 'Stała SFY_MAINTENANCE_MODE jest zdefiniowana w wp-config.php', 'sfy' ); ?></strong>
                        — <?php esc_html_e( 'kontroluje ona tryb konserwacji i ma pierwszeństwo przed tym ustawieniem.', 'sfy' ); ?>
                    </p>
                    <p><?php esc_html_e( 'Aktualny stan:', 'sfy' ); ?>
                        <strong><?php echo $enabled ? '✓ Włączony (przez stałą)' : '✗ Wyłączony (przez stałą)'; ?></strong>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'sfy_maintenance_save', 'sfy_maintenance_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Status', 'sfy' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="<?php echo esc_attr( self::OPTION_KEY ); ?>"
                                       value="1"
                                       <?php checked( get_option( self::OPTION_KEY, 0 ), 1 ); ?>
                                       <?php disabled( $constant_set ); ?>/>
                                <?php esc_html_e( 'Włącz tryb konserwacji', 'sfy' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Niezalogowani użytkownicy zobaczą splash zamiast strony. Adminowie i redaktorzy widzą stronę normalnie.', 'sfy' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php if ( ! $constant_set ) : ?>
                    <p class="submit">
                        <input type="submit" name="sfy_maintenance_save"
                               class="button button-primary"
                               value="<?php esc_attr_e( 'Zapisz ustawienia', 'sfy' ); ?>"/>
                    </p>
                <?php endif; ?>
            </form>

            <h2><?php esc_html_e( 'Wskazówki dla deweloperów', 'sfy' ); ?></h2>
            <p><?php esc_html_e( 'Aby na stałe włączyć tryb konserwacji w wp-config.php (np. podczas deploya):', 'sfy' ); ?></p>
            <pre><code>define( 'SFY_MAINTENANCE_MODE', true );</code></pre>
            <p><?php esc_html_e( 'Lub przez WP-CLI:', 'sfy' ); ?></p>
            <pre><code>wp option update sfy_maintenance_mode 1
wp option update sfy_maintenance_mode 0</code></pre>
            <h2><?php esc_html_e( 'Customizacja treści splash-a', 'sfy' ); ?></h2>
            <pre><code>add_filter( 'sfy_maintenance_eyebrow', fn() => 'Pre-launch' );
add_filter( 'sfy_maintenance_title',   fn() => 'Coming soon.' );
add_filter( 'sfy_maintenance_message', fn() => 'Wracamy 1 maja.' );</code></pre>
        </div>
        <?php
    }

    // ─── Admin notice + toolbar indicator when active ─────────
    public function admin_notice(): void {
        if ( ! $this->is_enabled() ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        echo '<div class="notice notice-warning">';
        echo '<p><strong>' . esc_html__( 'Tryb konserwacji jest włączony.', 'sfy' ) . '</strong> ';
        echo esc_html__( 'Niezalogowani użytkownicy widzą splash. Adminowie i redaktorzy mają normalny dostęp.', 'sfy' );
        echo ' <a href="' . esc_url( admin_url( 'options-general.php?page=sfy-maintenance' ) ) . '">'
           . esc_html__( 'Zarządzaj', 'sfy' ) . '</a></p>';
        echo '</div>';
    }

    public function admin_bar_indicator( $wp_admin_bar ): void {
        if ( ! $this->is_enabled() ) return;
        if ( ! is_object( $wp_admin_bar ) ) return;

        $wp_admin_bar->add_node( [
            'id'    => 'sfy-maintenance',
            'title' => '🔧 ' . __( 'Tryb konserwacji', 'sfy' ),
            'href'  => admin_url( 'options-general.php?page=sfy-maintenance' ),
            'meta'  => [
                'class' => 'sfy-maintenance-indicator',
                'title' => __( 'Strona w trybie konserwacji — widoczna tylko dla zalogowanych redaktorów', 'sfy' ),
            ],
        ] );
    }
}
