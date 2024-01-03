<?php

namespace Site;

class SfyAjax {
    public array $actions = [
//        'action_name',
    ];

    public function __construct() {
        foreach ( $this->actions as $action ) {
            $this->register_ajax_action( $action );
        }
    }

    private function register_ajax_action( string $action ): void {
        add_action( "wp_ajax_$action", [ $this, $action . '_ajax' ] );
        add_action( "wp_ajax_nopriv_$action", [ $this, $action . '_ajax' ] );
    }

    public function action_name_ajax() {

    }

}
