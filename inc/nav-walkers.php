<?php
/**
 * Nav Walkers
 *
 * Denver17_Nav_Walker        — desktop mega menu
 * Denver17_Mobile_Nav_Walker — mobile drawer accordions
 */

class Denver17_Nav_Walker extends Walker_Nav_Menu {

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            $output .= '<div class="mega"><div class="mega-card"><div class="mega-col"><div class="mega-group">';
        }
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            $output .= '</div></div></div></div>';
        }
    }

    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        if ( $depth === 0 ) {
            $has_children = in_array( 'menu-item-has-children', $data_object->classes );
            $output .= '<div class="nav-item' . ( $has_children ? ' has-mega' : '' ) . '">';
            $output .= '<span>' . esc_html( $data_object->title ) . '</span>';
        } else {
            $output .= '<a class="mega-link" href="' . esc_url( $data_object->url ) . '">' . esc_html( $data_object->title ) . '</a>';
        }
    }

    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            $output .= '</div>';
        }
    }
}

class Denver17_Mobile_Nav_Walker extends Walker_Nav_Menu {

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            $output .= '<div class="m-acc-panel">';
        }
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            $output .= '</div>';
        }
    }

    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $has_children = in_array( 'menu-item-has-children', $data_object->classes );
        if ( $depth === 0 ) {
            if ( $has_children ) {
                $output .= '<div class="m-acc">';
                $output .= '<button class="m-acc-trigger" aria-expanded="false">';
                $output .= esc_html( $data_object->title );
                $output .= '<span class="m-acc-chevron" aria-hidden="true">&#x25BE;</span>';
                $output .= '</button>';
            } else {
                $output .= '<a class="m-toplink" href="' . esc_url( $data_object->url ) . '">' . esc_html( $data_object->title ) . '</a>';
            }
        } else {
            $output .= '<a class="m-sublink" href="' . esc_url( $data_object->url ) . '">' . esc_html( $data_object->title ) . '</a>';
        }
    }

    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        $has_children = in_array( 'menu-item-has-children', $data_object->classes );
        if ( $depth === 0 && $has_children ) {
            $output .= '</div>';
        }
    }
}
