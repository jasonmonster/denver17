<?php
/**
 * Nav Walkers
 *
 * Denver17_Nav_Walker        — desktop mega menu
 * Denver17_Mobile_Nav_Walker — mobile drawer accordion
 *
 * Mega menu structure is driven by CSS classes on menu items:
 *
 *   mega-label        — Custom Link (URL: #). Renders as a section header
 *                       (mega-group-label), opens a new group. Not a link.
 *
 *   mega-col-break    — Opens a new column. If combined with mega-label, the item
 *                       title becomes the column's first group header (not a link).
 *                       Without mega-label, the item renders as a normal link and
 *                       simply starts a new column.
 *
 *   mega-group-break  — Opens a new group within the current column. The item
 *                       itself renders as a normal mega-link.
 *
 * Items with mega-label are skipped entirely in the mobile walker (they're
 * Custom Links with URL "#" — purely structural markers).
 */

// =============================================================================
// Desktop Walker
// =============================================================================

class Denver17_Nav_Walker extends Walker_Nav_Menu {

    /** Track open state across start_el calls within one sub-menu. */
    protected $col_open   = false;
    protected $group_open = false;

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            $this->col_open   = false;
            $this->group_open = false;
            $output .= '<div class="mega"><div class="mega-card">';
        }
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            if ( $this->group_open ) { $output .= '</div>'; $this->group_open = false; }
            if ( $this->col_open   ) { $output .= '</div>'; $this->col_open   = false; }
            $output .= '</div></div>'; // .mega-card, .mega
        }
    }

    public function start_el( &$output, $item, $depth = 0, $args = null, $current_object_id = 0 ) {

        // ----- Top-level nav items -----
        if ( $depth === 0 ) {
            $has_children = in_array( 'menu-item-has-children', (array) $item->classes );
            $output .= '<div class="nav-item">';
            if ( $has_children ) {
                // Hover opens the mega menu — span is intentional (not a link)
                $output .= '<span class="nav-item-label">' . esc_html( $item->title ) . '</span>';
            } else {
                // No children: must be a real link (Events, Contact)
                $output .= '<a class="nav-item-label" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
            }
            return;
        }

        // ----- Mega menu children (depth 1) -----
        $classes        = (array) $item->classes;
        $is_label       = in_array( 'mega-label',       $classes );
        $is_col_break   = in_array( 'mega-col-break',   $classes );
        $is_group_break = in_array( 'mega-group-break', $classes );

        // --- New column ---
        if ( $is_col_break ) {
            if ( $this->group_open ) { $output .= '</div>'; $this->group_open = false; }
            if ( $this->col_open   ) { $output .= '</div>'; $this->col_open   = false; }
            $output .= '<div class="mega-col">';
            $this->col_open = true;
            $output .= '<div class="mega-group">';
            $this->group_open = true;
            if ( $is_label ) {
                // Title becomes the group header — not rendered as a link
                $output .= '<div class="mega-group-label">' . esc_html( $item->title ) . '</div>';
            } else {
                // Real page link that happens to start a new column
                $output .= '<a class="mega-link" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
            }
            return;
        }

        // --- Section label (new group, same column) ---
        if ( $is_label ) {
            if ( $this->group_open ) { $output .= '</div>'; $this->group_open = false; }
            if ( ! $this->col_open ) {
                $output .= '<div class="mega-col">';
                $this->col_open = true;
            }
            $output .= '<div class="mega-group">';
            $this->group_open = true;
            $output .= '<div class="mega-group-label">' . esc_html( $item->title ) . '</div>';
            return;
        }

        // --- Group break (new group, same column, renders as link) ---
        if ( $is_group_break ) {
            if ( $this->group_open ) { $output .= '</div>'; $this->group_open = false; }
            if ( ! $this->col_open ) {
                $output .= '<div class="mega-col">';
                $this->col_open = true;
            }
            $output .= '<div class="mega-group">';
            $this->group_open = true;
            $output .= '<a class="mega-link" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
            return;
        }

        // --- Regular link ---
        if ( ! $this->col_open ) {
            $output .= '<div class="mega-col">';
            $this->col_open = true;
        }
        if ( ! $this->group_open ) {
            $output .= '<div class="mega-group">';
            $this->group_open = true;
        }
        $output .= '<a class="mega-link" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            $output .= '</div>'; // .nav-item
        }
        // depth 1: groups and cols are managed in start_el / end_lvl
    }
}


// =============================================================================
// Mobile Walker
// =============================================================================

class Denver17_Mobile_Nav_Walker extends Walker_Nav_Menu {

    /** Tracks whether the current item was skipped so end_el can match. */
    private $skip_current = false;

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

    public function start_el( &$output, $item, $depth = 0, $args = null, $current_object_id = 0 ) {
        $this->skip_current = false;
        $classes      = (array) $item->classes;
        $has_children = in_array( 'menu-item-has-children', $classes );

        // Skip structural label items — they're Custom Links used only by the
        // desktop mega menu walker and have no meaningful mobile equivalent.
        if ( $depth > 0 && in_array( 'mega-label', $classes ) ) {
            $this->skip_current = true;
            return;
        }

        if ( $depth === 0 ) {
            if ( $has_children ) {
                $output .= '<div class="m-acc">';
                $output .= '<button class="m-acc-trigger" aria-expanded="false">';
                $output .= esc_html( $item->title );
                $output .= '<span class="m-acc-chevron" aria-hidden="true">&#x25BE;</span>';
                $output .= '</button>';
            } else {
                $output .= '<a class="m-toplink" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
            }
        } else {
            $output .= '<a class="m-sublink" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
        }
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        if ( $this->skip_current ) {
            $this->skip_current = false;
            return;
        }
        $has_children = in_array( 'menu-item-has-children', (array) $item->classes );
        if ( $depth === 0 && $has_children ) {
            $output .= '</div>'; // .m-acc
        }
    }
}
