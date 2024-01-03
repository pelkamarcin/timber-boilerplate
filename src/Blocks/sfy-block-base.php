<?php

namespace Site\Blocks;

use Timber\Timber;

class SfyBlockBase {
    public string $name;
    public string $title;
    public string $icon     = '';
    public string $category = TEXT_DOMAIN . '-blocks';

    public array $supports = [];
    public array $keywords = [ TEXT_DOMAIN ];

    public function __construct() {

        add_action( 'acf/init', [ $this, 'register_block' ] );
    }

    public function register_block(): void {
        acf_register_block_type(
            array(
                'name' => $this->name,
                'title' => $this->title,
                'render_callback' => [ $this, 'callback' ],
                'category' => $this->category,
                'icon' => $this->icon,
                'keywords' => $this->keywords,
                'suports' => $this->supports,

            ) );
    }

    public function callback( $block, $content = '', $is_preview = false ): void {

    }


    public function base_callback( $block, $content = '', $is_preview = false ): array {
        $context = Timber::context();

        // Store block values.
        $context['block'] = $block;

        if ( function_exists( 'get_fields' ) ) {
            // Store field values.
            $context['fields'] = get_fields();
        }
        // Store $is_preview value.
        $context['is_preview'] = $is_preview;

        return $context;
    }


}
