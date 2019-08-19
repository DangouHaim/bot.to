<?php

/**
 * Listing type search tab structure.
 *
 * @since 1.5.1
 */
return [
    'advanced' => [
    	'facets' => [],
    ],

    'basic' => [
    	'facets' => [],
    ],

    'order' => [
    	/**
		 * List of options by which listing can be ordered.
		 * Each option can contain one or more ordering clauses.
         * string options[][label]
         * array options[][clauses]
    	 */
    	'options' => [],
    	'default' => 'date',
    ],

    /**
     * List of tabs to be shown in Explore page sidebar.
     *
     * @since 2.1
     * @type array
     *     string tab[label]
     *     string tab[icon]
     *     string tab[type]
     *     string tab[orderby]
     *     string tab[order]
     *     bool   tab[hierarchical]
     *     bool   tab[hide_empty]
     */
    'explore_tabs' => [],
];