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
| PLACEHOLDER
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Input placeholder.
|
*/
$component_search_bar['placeholder'] = __( 'Search...' );

?>
<v-autocomplete
    class="gc-search-bar"
    flat
    color="white"
    background-color="primary"
    v-model="model"
    :items="items"
    :loading="loading"
    :search-input.sync="search"
    no-filter
    clearable
    hide-no-data
    hide-details
    item-text="name"
    item-value="searchId"
    return-object
    label="<?php echo esc_attr( $component_search_bar['placeholder'] ); ?>"
    prepend-inner-icon="mdi-magnify"
    dense
    solo
    @focus="onFocus"
    @update:search-input="searchItems"
>
    <template v-slot:item="{ item }">
        <v-list-item-avatar v-if="item.large" color="white">
            <v-img :src="item.large"></v-img>
        </v-list-item-avatar>
        <v-list-item-avatar v-else color="secondary" class="text-h5 white--text" v-text="avatarChar(item.name)"></v-list-item-avatar>
        <v-list-item-content>
            <v-list-item-title v-text="item.name"></v-list-item-title>
            <v-list-item-subtitle v-if="item.symbol" class="text-uppercase" v-text="item.symbol"></v-list-item-subtitle>
        </v-list-item-content>
    </template>
</v-autocomplete>
