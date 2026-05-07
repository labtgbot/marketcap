<?php
/**
 * TONBANKCARD V2 achievements and streaks route.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

$frontend_options['achievements']['title'] = __( 'Achievements' );

?>
<section class="py-8 py-sm-10">
    <v-container id="achievements" fluid>
        <div class="achievements-header mb-5">
            <div>
                <h1 class="text-h4 text-sm-h3 font-weight-bold mb-2">
                    <?php echo esc_html( $frontend_options['achievements']['title'] ); ?>
                </h1>
                <div class="d-flex flex-wrap align-center">
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-trophy-outline</v-icon>
                        {{ unlockedCount }} / {{ definitions.length }} <?php echo esc_html( __( 'badges' ) ); ?>
                    </v-chip>
                    <v-chip small label class="mr-2 mb-2">
                        <v-icon small left>mdi-fire</v-icon>
                        {{ streak.current_count || 0 }} <?php echo esc_html( __( 'day streak' ) ); ?>
                    </v-chip>
                    <v-chip small label :color="optInModel ? 'success' : 'grey'" text-color="white" class="mb-2">
                        <v-icon small left>{{ optInModel ? 'mdi-check-circle-outline' : 'mdi-circle-outline' }}</v-icon>
                        {{ optInModel ? '<?php echo esc_attr( __( 'Opted in' ) ); ?>' : '<?php echo esc_attr( __( 'Opt-in' ) ); ?>' }}
                    </v-chip>
                </div>
            </div>
            <div class="achievements-route-actions">
                <v-btn color="primary" depressed :to="{name:'currencies'}">
                    <v-icon left>mdi-pulse</v-icon>
                    <?php echo esc_html( __( 'Market Pulse' ) ); ?>
                </v-btn>
                <v-btn outlined color="primary" :to="{name:'watchlist'}">
                    <v-icon left>mdi-star-outline</v-icon>
                    <?php echo esc_html( __( 'Watchlist' ) ); ?>
                </v-btn>
                <v-btn outlined color="primary" :to="{name:'alerts'}">
                    <v-icon left>mdi-bell-outline</v-icon>
                    <?php echo esc_html( __( 'Alerts' ) ); ?>
                </v-btn>
            </div>
        </div>

        <v-alert v-if="!featureEnabled" type="info" outlined class="mb-4">
            <?php echo esc_html( __( 'Gamification is currently disabled by the admin feature flag. Progress cards stay available for preview.' ) ); ?>
        </v-alert>

        <div class="achievement-control-grid mb-4">
            <v-card outlined class="achievement-control-card">
                <v-card-title class="text-subtitle-1 font-weight-bold">
                    <v-icon left color="primary">mdi-toggle-switch-outline</v-icon>
                    <?php echo esc_html( __( 'Progress' ) ); ?>
                    <v-spacer></v-spacer>
                    <v-switch
                        v-model="optInModel"
                        :disabled="!featureEnabled"
                        inset
                        dense
                        hide-details
                        color="success"
                        label="<?php echo esc_attr( __( 'Achievements' ) ); ?>"
                    ></v-switch>
                </v-card-title>
                <v-card-text>
                    <div class="achievement-stat-grid">
                        <div>
                            <span class="text-caption text--secondary"><?php echo esc_html( __( 'Unlocked' ) ); ?></span>
                            <strong>{{ unlockedCount }}</strong>
                        </div>
                        <div>
                            <span class="text-caption text--secondary"><?php echo esc_html( __( 'Streak' ) ); ?></span>
                            <strong>{{ streak.current_count || 0 }}</strong>
                        </div>
                        <div>
                            <span class="text-caption text--secondary"><?php echo esc_html( __( 'Timezone' ) ); ?></span>
                            <strong>{{ streak.timezone || 'UTC' }}</strong>
                        </div>
                    </div>
                </v-card-text>
            </v-card>

            <gc-share-card dense :card="progressShareCard"></gc-share-card>
        </div>

        <div class="achievement-prompt mb-4" v-if="activePrompts.length">
            <v-alert
                v-for="definition in activePrompts"
                :key="definition.id"
                type="success"
                outlined
                border="left"
                class="mb-3"
            >
                <div class="d-flex align-start">
                    <v-icon color="success" class="mr-3" v-text="definition.badge_icon"></v-icon>
                    <div class="min-width-0">
                        <div class="font-weight-bold" v-text="definition.title"></div>
                        <div class="text-body-2" v-text="definition.description"></div>
                    </div>
                    <v-spacer></v-spacer>
                    <div class="achievement-prompt-actions">
                        <v-btn
                            icon
                            small
                            color="primary"
                            :loading="sharingId === definition.id"
                            :aria-label="'Share ' + definition.title"
                            :title="'Share ' + definition.title"
                            @click="shareAchievement(definition)"
                        >
                            <v-icon>mdi-share-variant</v-icon>
                        </v-btn>
                        <v-btn
                            icon
                            small
                            color="grey"
                            :aria-label="'Dismiss ' + definition.title"
                            :title="'Dismiss ' + definition.title"
                            @click="dismissPrompt(definition)"
                        >
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </div>
                </div>
            </v-alert>
        </div>

        <div class="achievement-badge-grid">
            <v-card
                v-for="definition in definitions"
                :key="definition.id"
                class="achievement-badge-card"
                :class="{'achievement-badge-card--locked': !isUnlocked(definition)}"
                outlined
            >
                <v-card-title class="achievement-badge-card-title">
                    <v-avatar size="44" :color="badgeColor(definition)" class="mr-3">
                        <v-icon color="white" v-text="definition.badge_icon"></v-icon>
                    </v-avatar>
                    <div class="min-width-0">
                        <div class="text-subtitle-1 font-weight-bold text-truncate" v-text="definition.title"></div>
                        <div class="text-caption text--secondary" v-text="unlockedLabel(definition)"></div>
                    </div>
                    <v-spacer></v-spacer>
                    <v-btn
                        icon
                        small
                        color="primary"
                        :disabled="!isUnlocked(definition)"
                        :loading="sharingId === definition.id"
                        :aria-label="'Share ' + definition.title"
                        :title="'Share ' + definition.title"
                        @click="shareAchievement(definition)"
                    >
                        <v-icon>mdi-share-variant</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <p class="achievement-badge-description mb-3" v-text="definition.description"></p>
                    <v-progress-linear
                        :value="progressValue(definition)"
                        :color="badgeColor(definition)"
                        height="8"
                        rounded
                        class="mb-2"
                    ></v-progress-linear>
                    <div class="d-flex align-center">
                        <v-chip x-small label outlined>
                            {{ progressText(definition) }}
                        </v-chip>
                        <v-spacer></v-spacer>
                        <v-chip x-small label outlined>
                            {{ definition.category.replace(/_/g, ' ') }}
                        </v-chip>
                    </div>
                </v-card-text>
            </v-card>
        </div>

        <v-snackbar v-model="notice" timeout="2200">
            {{ notice }}
        </v-snackbar>
    </v-container>
</section>
