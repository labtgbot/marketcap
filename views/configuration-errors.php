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

/**
 * @var array $invalid_configs
 */

/*
| -------------------------------------------------------------------------
| CONFIGURATION ERROR VIEW
| -------------------------------------------------------------------------
|
| Show bad configuration with correction examples.
|
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuration Errors</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            padding: 24px;
            color: #4a4a4a;
            font-size: 1em;
            font-weight: 400;
            line-height: 1.5;
        }
        code {
            font-family: monospace;
            background-color: #f5f5f5;
            font-size: 1.1em;
            font-weight: 400;
            color: #da1039;
            padding: .5em;
        }
        code.example {
            display: block;
            padding: 1em;
            background-color: #1f2330;
            color: #37e7ac;
        }
        .card {
            border: 1px solid rgba(0, 0, 0, 0.12);
            padding: 1em;
            margin-bottom: 2em;
            max-width: 1200px;
        }
        #reload {
            font-size: 16px;
            background: #1867c0;
            color: #fff;
            padding: 0.5em;
            border: none;
        }
    </style>
</head>
<body>
    <h1>Configuration Errors</h1>

    <?php foreach ( $invalid_configs as $entry ) : ?>
        <?php
            $entry_is_constant = ! empty( $entry['constant'] );
            $entry_type = $entry_is_constant ? 'Constant' : 'Variable';
            $entry_name = $entry_is_constant ? $entry['constant'] : $entry['config'];

            if ( ! isset( $entry['example'] ) ) {
                $entry_example = false;
            } elseif ( $entry_is_constant ) {
                $entry_example = sprintf( "define( '%s', %s );", $entry_name, $entry['example'] );
            } else {
                $entry_example = sprintf( '%s = %s;', $entry_name, $entry['example'] );
            }
        ?>
        <div class="card">
            <p class="file">
                <strong>File:</strong>
                <code><?php echo esc_html( $entry['file'] ); ?></code>
            </p>
            <p class="name">
                <strong><?php echo esc_html( $entry_type ); ?></strong>
                <code><?php echo esc_html( $entry_name ); ?></code>
            </p>
            <p class="message">
                <strong>Message:</strong>
                <?php echo esc_html( $entry['message'] ); ?>
            </p>
            <?php if ( $entry_example ) : ?>
                <p><strong>Example:</strong></p>
                <p><code class="example"><?php echo esc_html( $entry_example ); ?></code></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button id="reload" onclick="location.reload()">&orarr; Refresh</button>
</body>
</html>

