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

/*
| -------------------------------------------------------------------------
| BUILD TOOL
| -------------------------------------------------------------------------
| Creates the app javascript file.
| Check in documentation: Development > Javascript > Build Tool
| -------------------------------------------------------------------------
*/

const path = require('path')
const fs = require('fs')

/**
 * Source Files
 * @type {string[]}
 */
const srcFiles = require(path.join(__dirname, '..', 'source.json'))
const UglifyJS = require("uglify-js");

/**
 * Output app javascript file path
 * @type {string}
 */
const appFilePath = path.join(__dirname, '../../..', 'assets/js/app.js')


/**
 * Output app minified javascript file path
 * @type {string}
 */
const appMinFilePath = path.join(__dirname, '../../..', 'assets/js/app.min.js')

/**
 * Command line flags
 * @type {{minify: boolean}}
 */
const flags = {
    minify: process.argv.indexOf('-m') !== -1 || process.argv.indexOf('--minify') !== -1,
}

/**
 * @type {{}}
 *
 * Configuration for "UglifyJS"
 * @see https://github.com/mishoo/UglifyJS#readme
 */
const uglifyOptions = {

}

/**
 * Collects and joins files content
 * @return {string}
 */
function collectFiles() {
    console.log('--------------------')
    console.log('COLLECTING...')
    let output = ''
    for (const file of srcFiles) {
        const filePath = path.join(__dirname, '/../src', file)
        try {
            output += fs.readFileSync(filePath, 'utf-8') + '\n'
            console.log(file)
        } catch (err) {
            console.error('NOT FOUND:', file)
            console.error('FAILED!')
            process.exit()
        }
    }
    return output
}


/**
 * All files code compiled
 * @type {string}
 */
let output = collectFiles()

// Writes app file
fs.writeFile(appFilePath, output, appFileErr => {
    if (appFileErr) {
        console.error(appFileErr)
        return
    }

    console.log('--------------------')
    console.log('APP:', appFilePath);

    // Writes app minified file
    try {
        // tries to minify code
        const UglifyJS = require('uglify-js')
        const minified = UglifyJS.minify(output, uglifyOptions)
        if (minified.error) throw new Error(minified.error)

        fs.writeFile(appMinFilePath, minified.code, appMinFileErr => {
            if (appMinFileErr) {
                console.error(appMinFileErr)
                return
            }

            console.log('--------------------')
            console.log('APP MINIFIED:', appMinFilePath);
            console.log('--------------------')
        })
    } catch (uglifyErr) {
        console.log('--------------------')
        console.log('APP MINIFIED: FAILED')
        console.log('Fix it running:')
        console.log('npm install uglify-js')
        console.log('--------------------')
    }
})





