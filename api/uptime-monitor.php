<?php
/**
 * CLI entrypoint for the bundled uptime/health monitor.
 *
 * Polls the configured health endpoints (`/api/health` and `/api/ready` by
 * default) and pages the operations Telegram alert channel on failure, reusing
 * the same bot infrastructure as the alerts worker. The monitor is disabled by
 * default and only runs when TONBANKCARD_UPTIME_MONITOR_ENABLED=true.
 *
 * Schedule it from cron, e.g. every minute:
 *   * * * * * php /path/to/api/uptime-monitor.php >> /var/log/uptime-monitor.log 2>&1
 *
 * Exit codes: 0 when every probe is healthy (or the monitor is disabled), 1 when
 * at least one probe failed, so external schedulers can also detect outages.
 */

if ( 'cli' !== PHP_SAPI ) {
    http_response_code( 404 );
    exit;
}

$root = dirname( __DIR__ );

require_once $root . '/constants.php';
require_once GECKO_CLIENT_CONFIG_DIR . '/api.php';
require_once $root . '/functions.php';
require_once __DIR__ . '/observability.php';

$settings = tonbankcard_observability_uptime_settings(
    isset( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : [],
    isset( $api ) ? $api : []
);

$summary = tonbankcard_observability_uptime_run( $settings );

// Never echo the bot token or fully-qualified probe URLs; the summary only
// carries paths, statuses, and redacted reasons.
echo json_encode(
    [
        'ok'   => empty( $summary['failures'] ),
        'data' => [
            'ran'              => ! empty( $summary['ran'] ),
            'reason'           => isset( $summary['reason'] ) ? (string) $summary['reason'] : '',
            'checked'          => isset( $summary['checked'] ) ? (int) $summary['checked'] : 0,
            'failures'         => isset( $summary['failures'] ) ? $summary['failures'] : [],
            'alert_configured' => ! empty( $summary['alert_configured'] ),
            'alerted'          => ! empty( $summary['alerted'] ),
        ],
        'meta' => [
            'request_id'  => 'cli-uptime-monitor',
            'environment' => (string) $settings['environment'],
            'targets'     => $settings['targets'],
        ],
    ],
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
) . PHP_EOL;

exit( empty( $summary['failures'] ) ? 0 : 1 );
