<?php
/**
 * Page title — the renderer.
 *
 * The only place the module writes HTML. Takes a list of crumbs (crumbs.php)
 * and returns one line of markup: the fixed header line, a listing's entry
 * title, or the plain-text label a thumbnail shows on hover. Every value is
 * escaped here; callers print the result as it is.
 *
 * @see README.md for the output of each option.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tiagsspace_render_crumbs' ) ) {
    /**
     * Render a crumb list as HTML.
     *
     * @param array[] $crumbs  From tiagsspace_page_crumbs() or tiagsspace_entry_crumbs().
     * @param array   $args {
     *     @type string       $heading        'h1', 'h2' or 'none'. With a heading crumb in the list, only that
     *                                        crumb becomes the heading element, inline inside a <div>; without
     *                                        one, the whole line is the heading element. 'none' wraps in a <p>.
     *     @type bool         $links          false: no anchors (the hover label).
     *     @type string|false $responsive     Bootstrap breakpoint ('sm', 'md', 'lg', 'xl') from which the long
     *                                        form of a crumb shows; false prints the long form only.
     *     @type string       $before         Text before everything, e.g. ' > Space' (the old label root).
     *     @type bool         $leading_sep    Start with the separator (a label appended after the header text).
     *     @type string       $separator      Between crumbs. ' / ' by default.
     *     @type string[]     $heading_attrs  Attributes for the heading element, e.g. [ 'itemprop' => 'headline' ].
     *     @type bool         $over_text      Emit the hover-label target span inside the wrapper, before its end.
     *     @type string       $class          Wrapper class; the prefix span is "{class}-prefix", the heading
     *                                        "{class}-heading", the group "{class}-entry".
     *     @type int|null     $group_from     Wrap the crumbs from this index to the end in a span, so a script
     *                                        can swap them as one (the single page's header swaps everything
     *                                        after the site crumb).
     *     @type bool         $wrap           false returns the crumbs only, no wrapper element (labels).
     * }
     * @return string HTML.
     */
    function tiagsspace_render_crumbs( $crumbs, $args = array() ) {
        $args = array_merge( array(
            'heading'       => 'none',
            'links'         => true,
            'responsive'    => 'sm',
            'before'        => '',
            'leading_sep'   => false,
            'separator'     => ' / ',
            'heading_attrs' => array(),
            'over_text'     => false,
            'class'         => 'site-title',
            'group_from'    => null,
            'wrap'          => true,
        ), $args );

        $crumbs      = array_values( array_filter( (array) $crumbs, 'is_array' ) );
        $count       = count( $crumbs );
        $sep         = esc_html( $args['separator'] );
        $class       = sanitize_html_class( $args['class'] );
        $has_heading = false;
        foreach ( $crumbs as $crumb ) {
            if ( ! empty( $crumb['heading'] ) ) {
                $has_heading = true;
            }
        }
        $heading_tag = in_array( $args['heading'], array( 'h1', 'h2' ), true ) ? $args['heading'] : '';
        // The prefix and group spans only mean something around a heading element;
        // a plain line (a label, a <p>) is words and separators alone.
        $structured  = $has_heading && '' !== $heading_tag;

        $out = esc_html( $args['before'] );
        if ( $args['leading_sep'] ) {
            $out .= $sep;
        }

        $prefix_open = false;
        $group_open  = false;
        foreach ( $crumbs as $i => $crumb ) {
            $is_heading = ! empty( $crumb['heading'] );
            $in_group   = null === $args['group_from'] || $i >= (int) $args['group_from'];

            if ( $structured && null !== $args['group_from'] && $i === (int) $args['group_from'] ) {
                $out       .= '<span class="' . $class . '-entry">';
                $group_open = true;
            }
            if ( $structured && $in_group && ! $is_heading && ! $prefix_open ) {
                $out        .= '<span class="' . $class . '-prefix">';
                $prefix_open = true;
            }
            if ( $is_heading && $prefix_open ) {
                $out        .= '</span>';
                $prefix_open = false;
            }

            $html = tiagsspace_render_crumb( $crumb, $args['links'], $args['responsive'] );
            if ( $is_heading && $heading_tag ) {
                $attrs = '';
                foreach ( (array) $args['heading_attrs'] as $name => $value ) {
                    $attrs .= ' ' . sanitize_key( $name ) . '="' . esc_attr( $value ) . '"';
                }
                $html = '<' . $heading_tag . ' class="' . $class . '-heading"' . $attrs . '>' . $html . '</' . $heading_tag . '>';
            }
            $out .= $html;

            if ( $i < $count - 1 ) {
                $out .= $sep;
            }
        }
        if ( $prefix_open ) {
            $out .= '</span>';
        }
        if ( $group_open ) {
            $out .= '</span>';
        }
        if ( $args['over_text'] ) {
            $out .= '<span id="over-text" class="d-none d-sm-inline"></span>';
        }

        if ( ! $args['wrap'] ) {
            return $out;
        }
        if ( $has_heading || ! $heading_tag ) {
            $tag = $has_heading ? 'div' : 'p';
        } else {
            $tag = $heading_tag;
        }
        return '<' . $tag . ' class="' . $class . '">' . $out . '</' . $tag . '>';
    }
}

if ( ! function_exists( 'tiagsspace_render_crumb' ) ) {
    /**
     * Render one crumb: its words, with the responsive short form, linked or not.
     *
     * Two responsive forms, both from the theme's markup: when the short form is
     * the start of the label ("Dusk" / "Dusk Series") only the rest is hidden on
     * small screens; otherwise ("S" / "Tiags' Space") the two forms are swapped.
     *
     * @param array        $crumb
     * @param bool         $links
     * @param string|false $responsive
     * @return string HTML.
     */
    function tiagsspace_render_crumb( $crumb, $links = true, $responsive = 'sm' ) {
        $label = isset( $crumb['label'] ) ? (string) $crumb['label'] : '';
        $short = isset( $crumb['short'] ) ? (string) $crumb['short'] : '';
        $bp    = in_array( $responsive, array( 'sm', 'md', 'lg', 'xl' ), true ) ? $responsive : '';

        if ( '' === $bp || '' === $short || $short === $label ) {
            $html = esc_html( $label );
        } elseif ( 0 === strpos( $label, $short ) ) {
            $html = esc_html( $short ) . '<span class="d-none d-' . $bp . '-inline">' . esc_html( substr( $label, strlen( $short ) ) ) . '</span>';
        } else {
            $html = '<span class="d-inline d-' . $bp . '-none">' . esc_html( $short ) . '</span>'
                  . '<span class="d-none d-' . $bp . '-inline">' . esc_html( $label ) . '</span>';
        }

        if ( $links && ! empty( $crumb['url'] ) ) {
            $html = '<a href="' . esc_url( $crumb['url'] ) . '">' . $html . '</a>';
        }
        return $html;
    }
}
