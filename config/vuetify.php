<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * @package     Gecko Client
 * @author      RunCoders
 * @license     Envato Market Regular License (https://1.envato.market/regular-license)
 * @copyright   Copyright (c) 2021 RunCoders (https://runcoders.net)
 * @since	    1.0.0
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/*
| -------------------------------------------------------------------------
| THEMES
| -------------------------------------------------------------------------
|
| The $vuetify['default_theme'] variable can be 'light' or 'dark' theme.
| But it can be changed by the visitor and the browser will retain the preference.
| Preference value will be saved in "Local Storage" with "geckoClient:theme" key.
|
| -------------------------------------------------------------------------
| EXPLANATION OF THEME PARAMETERS
| -------------------------------------------------------------------------
|
|   ['primary']         (string)    Primary color
|   ['secondary']       (string)    Secondary color
|   ['accent']          (string)    Accent color
|   ['error']           (string)    Error color
|   ['info']            (string)    Info color
|   ['success']         (string)    Success color
|   ['warning']         (string)    Warning color
|   ['high']            (string)    Up changes, high trust scores, sparklines, buy buttons color
|   ['high_text']       (string)    'high' contrasting text color
|   ['moderate']        (string)    Moderate trust scores
|   ['moderate_text']   (string)    'moderate' contrasting text color
|   ['low']             (string)    Down changes, low trust scores, sparklines, sell buttons color
|   ['low_text']        (string)    'low' contrasting text color
|
| -------------------------------------------------------------------------
| LIGHT THEME
| -------------------------------------------------------------------------
|
|   $vuetify['light_theme'] = [
|       'primary'       => '#1976D2',
|       'secondary'     => '#424242',
|       'accent'        => '#82B1FF',
|       'error'         => '#FF5252',
|       'info'          => '#2196F3',
|       'success'       => '#4CAF50',
|       'warning'       => '#FB8C00',
|       'high'          => '#4CAF50',
|       'high_text'     => '#FFFFFF',
|       'moderate'      => '#FB8C00',
|       'moderate_text' => '#FFFFFF',
|       'low'           => '#F44336',
|       'low_text'      => '#FFFFFF',
|   ];
|
| -------------------------------------------------------------------------
| DARK THEME
| -------------------------------------------------------------------------
|
|   $vuetify['dark_theme'] = [
|       'primary'       => '#2196F3',
|       'secondary'     => '#424242',
|       'accent'        => '#FF4081',
|       'error'         => '#FF5252',
|       'info'          => '#2196F3',
|       'success'       => '#4CAF50',
|       'warning'       => '#FB8C00',
|       'high'          => '#4CAF50',
|       'high_text'     => '#FFFFFF',
|       'moderate'      => '#FB8C00',
|       'moderate_text' => '#FFFFFF',
|       'low'           => '#F44336',
|       'low_text'      => '#FFFFFF',
|   ];
|
*/

$vuetify['default_theme'] = 'light';

$vuetify['light_theme'] = [
    'primary'       => '#1BB2DA',
    'secondary'     => '#22324E',
    'accent'        => '#F2B84B',
    'error'         => '#D84A4A',
    'info'          => '#2F80ED',
    'success'       => '#12A978',
    'warning'       => '#C77800',
    'high'          => '#12A978',
    'high_text'     => '#FFFFFF',
    'moderate'      => '#C77800',
    'moderate_text' => '#071018',
    'low'           => '#D84A4A',
    'low_text'      => '#FFFFFF',
    'surface'       => '#FFFFFF',
    'surface_alt'   => '#EEF6FB',
    'background'    => '#F6FAFD',
    'text_primary'  => '#0B1020',
    'text_muted'    => '#4C6178',
    'focus'         => '#F2B84B',
];

$vuetify['dark_theme'] = [
    'primary'       => '#54C8E8',
    'secondary'     => '#8BA5C7',
    'accent'        => '#FFD166',
    'error'         => '#FF6B6B',
    'info'          => '#63A4FF',
    'success'       => '#39D98A',
    'warning'       => '#E6A23C',
    'high'          => '#39D98A',
    'high_text'     => '#06130D',
    'moderate'      => '#E6A23C',
    'moderate_text' => '#071018',
    'low'           => '#FF6B6B',
    'low_text'      => '#071018',
    'surface'       => '#121A2B',
    'surface_alt'   => '#18233A',
    'background'    => '#0B1020',
    'text_primary'  => '#F5F9FF',
    'text_muted'    => '#A8B8CC',
    'focus'         => '#FFD166',
];


/*
| -------------------------------------------------------------------------
| TRANSLATION
| -------------------------------------------------------------------------
|
| TYPE: array
| DESCRIPTION: Vuetify text fields translation
|
*/
$vuetify['translation'] = [
    'badge' => 'Badge',
    'close' => 'Close',
    'dataIterator' => [
        'noResultsText' => 'No matching records found',
        'loadingText' => 'Loading items...',
    ],
    'dataTable' => [
        'itemsPerPageText' => 'Rows per page:',
        'ariaLabel' => [
            'sortDescending' => 'Sorted descending.',
            'sortAscending' => 'Sorted ascending.',
            'sortNone' => 'Not sorted.',
            'activateNone' => 'Activate to remove sorting.',
            'activateDescending' => 'Activate to sort descending.',
            'activateAscending' => 'Activate to sort ascending.',
        ],
        'sortBy' => 'Sort by',
    ],
    'dataFooter' => [
        'itemsPerPageText' => 'Items per page:',
        'itemsPerPageAll' => 'All',
        'nextPage' => 'Next page',
        'prevPage' => 'Previous page',
        'firstPage' => 'First page',
        'lastPage' => 'Last page',
        'pageText' => '{0}-{1} of {2}',
    ],
    'datePicker' => [
        'itemsSelected' => '{0} selected',
        'nextMonthAriaLabel' => 'Next month',
        'nextYearAriaLabel' => 'Next year',
        'prevMonthAriaLabel' => 'Previous month',
        'prevYearAriaLabel' => 'Previous year',
    ],
    'noDataText' => 'No data available',
    'carousel' => [
        'prev' => 'Previous visual',
        'next' => 'Next visual',
        'ariaLabel' => [
            'delimiter' => 'Carousel slide {0} of {1}',
        ],
    ],
    'calendar' => [
        'moreEvents' => '{0} more',
    ],
    'fileInput' => [
        'counter' => '{0} files',
        'counterSize' => '{0} files ({1} in total)',
    ],
    'timePicker' => [
        'am' => 'AM',
        'pm' => 'PM',
    ],
    'pagination' => [
        'ariaLabel' => [
            'wrapper' => 'Pagination Navigation',
            'next' => 'Next page',
            'previous' => 'Previous page',
            'page' => 'Goto Page {0}',
            'currentPage' => 'Current Page, Page {0}',
        ],
    ],
    'rating' => [
        'ariaLabel' => [
            'icon' => 'Rating {0} of {1}',
        ],
    ],
];
