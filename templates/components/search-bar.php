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
$component_search_bar['placeholder'] = __( 'Search coins, exchanges, TON assets...' );
$component_search_bar['aria_label'] = __( 'Search coins, exchanges, TON assets, categories, and quick actions' );
$component_search_bar['open_label'] = __( 'Open search' );
$component_search_bar['close_label'] = __( 'Close search' );

?>
<div class="gc-search-bar">
    <div class="gc-search-bar-inline" v-show="!compactSearch">
        <v-autocomplete
            ref="inlineSearch"
            class="gc-search-autocomplete"
            flat
            color="white"
            background-color="primary"
            v-model="model"
            :items="items"
            :loading="loading"
            :search-input.sync="search"
            :menu-props="menuProps"
            :no-data-text="noDataText"
            no-filter
            clearable
            hide-details
            item-text="name"
            item-value="searchId"
            return-object
            label="<?php echo esc_attr( $component_search_bar['placeholder'] ); ?>"
            aria-label="<?php echo esc_attr( $component_search_bar['aria_label'] ); ?>"
            prepend-inner-icon="mdi-magnify"
            dense
            solo
            @focus="onFocus"
            @click:clear="clearSearch"
            @keydown.esc.stop="closeSearch"
            @update:search-input="queueSearchItems"
        >
            <template v-slot:item="{ item }">
                <v-subheader v-if="item.header" class="gc-search-group-header" v-text="item.header"></v-subheader>
                <template v-else>
                    <v-list-item-icon v-if="item.recent || item.type === 'action' || item.type === 'ton_asset' || item.type === 'exchange' || item.type === 'category'">
                        <v-icon v-text="resultIcon(item)"></v-icon>
                    </v-list-item-icon>
                    <v-list-item-avatar v-else-if="itemImage(item)" color="white">
                        <v-img :src="itemImage(item)"></v-img>
                    </v-list-item-avatar>
                    <v-list-item-avatar v-else color="secondary" class="text-h6 white--text" v-text="avatarChar(item.name)"></v-list-item-avatar>
                    <v-list-item-content>
                        <v-list-item-title v-text="item.name"></v-list-item-title>
                        <v-list-item-subtitle>
                            <span v-if="item.subtitle" v-text="item.subtitle"></span>
                            <span v-else v-text="resultTypeLabel(item)"></span>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                    <v-list-item-action v-if="isWatchlistResult(item)" class="gc-search-watchlist-action">
                        <v-btn
                            icon
                            small
                            class="watchlist-icon-button"
                            :color="isWatched(item) ? 'primary' : undefined"
                            :aria-label="watchlistLabel(item)"
                            :title="watchlistLabel(item)"
                            @click.stop.prevent="toggleWatchlist(item)"
                        >
                            <v-icon v-text="watchlistIcon(item)"></v-icon>
                        </v-btn>
                    </v-list-item-action>
                    <v-list-item-action class="gc-search-result-meta">
                        <v-chip x-small label v-text="resultTypeLabel(item)"></v-chip>
                    </v-list-item-action>
                </template>
            </template>
        </v-autocomplete>
    </div>

    <v-btn
        icon
        class="gc-search-trigger"
        v-show="compactSearch"
        aria-label="<?php echo esc_attr( $component_search_bar['open_label'] ); ?>"
        @click="openSearch('button')"
    >
        <v-icon>mdi-magnify</v-icon>
    </v-btn>

    <v-dialog
        v-model="dialog"
        fullscreen
        hide-overlay
        transition="dialog-bottom-transition"
        content-class="gc-search-dialog"
    >
        <v-card tile class="gc-search-dialog-card">
            <v-toolbar flat dense class="gc-search-dialog-toolbar">
                <v-btn icon aria-label="<?php echo esc_attr( $component_search_bar['close_label'] ); ?>" @click="closeSearch">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <v-autocomplete
                    ref="dialogSearch"
                    class="gc-search-dialog-field"
                    flat
                    color="primary"
                    background-color="transparent"
                    v-model="model"
                    :items="items"
                    :loading="loading"
                    :search-input.sync="search"
                    :menu-props="dialogMenuProps"
                    :no-data-text="noDataText"
                    no-filter
                    clearable
                    hide-details
                    item-text="name"
                    item-value="searchId"
                    return-object
                    label="<?php echo esc_attr( $component_search_bar['placeholder'] ); ?>"
                    aria-label="<?php echo esc_attr( $component_search_bar['aria_label'] ); ?>"
                    prepend-inner-icon="mdi-magnify"
                    dense
                    solo
                    autofocus
                    @focus="onFocus"
                    @click:clear="clearSearch"
                    @keydown.esc.stop="closeSearch"
                    @update:search-input="queueSearchItems"
                >
                    <template v-slot:item="{ item }">
                        <v-subheader v-if="item.header" class="gc-search-group-header" v-text="item.header"></v-subheader>
                        <template v-else>
                            <v-list-item-icon v-if="item.recent || item.type === 'action' || item.type === 'ton_asset' || item.type === 'exchange' || item.type === 'category'">
                                <v-icon v-text="resultIcon(item)"></v-icon>
                            </v-list-item-icon>
                            <v-list-item-avatar v-else-if="itemImage(item)" color="white">
                                <v-img :src="itemImage(item)"></v-img>
                            </v-list-item-avatar>
                            <v-list-item-avatar v-else color="secondary" class="text-h6 white--text" v-text="avatarChar(item.name)"></v-list-item-avatar>
                            <v-list-item-content>
                                <v-list-item-title v-text="item.name"></v-list-item-title>
                                <v-list-item-subtitle>
                                    <span v-if="item.subtitle" v-text="item.subtitle"></span>
                                    <span v-else v-text="resultTypeLabel(item)"></span>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                            <v-list-item-action v-if="isWatchlistResult(item)" class="gc-search-watchlist-action">
                                <v-btn
                                    icon
                                    small
                                    class="watchlist-icon-button"
                                    :color="isWatched(item) ? 'primary' : undefined"
                                    :aria-label="watchlistLabel(item)"
                                    :title="watchlistLabel(item)"
                                    @click.stop.prevent="toggleWatchlist(item)"
                                >
                                    <v-icon v-text="watchlistIcon(item)"></v-icon>
                                </v-btn>
                            </v-list-item-action>
                            <v-list-item-action class="gc-search-result-meta">
                                <v-chip x-small label v-text="resultTypeLabel(item)"></v-chip>
                            </v-list-item-action>
                        </template>
                    </template>
                </v-autocomplete>
            </v-toolbar>
        </v-card>
    </v-dialog>
</div>
