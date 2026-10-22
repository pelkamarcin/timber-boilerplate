<?php

/**
 * Wspolne narzedzia testow BlockPreviewGuard oraz stub add_action().
 *
 * Plik NIE konczy sie na `Test.php`, wiec PHPUnit go nie skanuje - laduje sie
 * przez autoload `Tests\` z composer.json (autoload-dev), kiedy ktorakolwiek
 * klasa testowa uzywa tego traitu. Dzieki temu stub add_action() powstaje
 * dokladnie raz, mimo ze korzystaja z niego dwa pliki testow.
 *
 * DLACZEGO OSOBNY PLIK, A NIE DRUGA KLASA W PLIKU TESTOW:
 * PHPUnit bierze z pliku `FooTest.php` wylacznie klase `Foo...Test` zgodna
 * z nazwa pliku. Druga klasa testowa w tym samym pliku jest po cichu
 * IGNOROWANA - nie uruchamia sie i nie pojawia w `--list-tests`, czyli daje
 * zerowe pokrycie przy zielonym wyniku. Sprawdzone na PHPUnit 10.5.66.
 * Kazda klasa testowa musi wiec miec wlasny plik.
 */

namespace Tests {

    use Site\App\Content\Blocks\BlockPreviewGuard;

    trait BlockPreviewGuardHelpers {

        private function guard(): BlockPreviewGuard {
            $GLOBALS['sfy_test_actions'] = [];

            return new BlockPreviewGuard();
        }

        /**
         * Przepuszcza HTML przez pare akcji ACF-a tak, jak robi to
         * acf_block_render_template(): wyjscie szablonu leci do echo pomiedzy
         * pre_block_template_render i post_block_template_render.
         *
         * @param mixed                $is_preview Wartosc, ktora ACF przekazuje do obu akcji.
         * @param array<string, mixed> $block      Tablica wlasciwosci bloku.
         */
        private function render_through_guard(
            BlockPreviewGuard $guard,
            string $html,
            $is_preview,
            array $block = []
        ): string {
            ob_start();
            $guard->start_capture( $block, '', $is_preview );
            // Symulacja `include $path` z acf_block_render_template() - HTML
            // testowy jest literalem w pliku testu, nie wejsciem uzytkownika.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $html;
            $guard->finish_capture( $block, '', $is_preview );

            return (string) ob_get_clean();
        }

        /**
         * Wola prywatna metode klasy. Potrzebne, zeby przypiac zachowanie
         * SCIEZKI ZAPASOWEJ (mark_with_tag_processor) niezaleznie od tego, ze
         * w normalnym przebiegu wygrywa sciezka glowna - inaczej regresja
         * w recznym liczniku glebokosci bylaby zamaskowana.
         *
         * @param array<int, mixed> $args Argumenty metody.
         * @return mixed
         */
        private function call_private( BlockPreviewGuard $guard, string $method, array $args ) {
            $reflection = new \ReflectionMethod( BlockPreviewGuard::class, $method );
            $reflection->setAccessible( true );

            return $reflection->invokeArgs( $guard, $args );
        }
    }
}

namespace Site\App\Content\Blocks {

    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        $GLOBALS['sfy_test_actions'][] = [ $hook, $callback, $priority, $accepted_args ];
    }
}
