<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * OneBoard theme callbacks.
 *
 * @package    theme_oneboard
 * @copyright  2026 oneboard.study
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main SCSS: Inter web font (imported at the very top so the CSS @import is valid),
 * the Boost default preset as the base, then the OneBoard brand layer.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_oneboard_get_main_scss_content($theme) {
    global $CFG;

    // @import must be the first CSS rule to be honoured by browsers.
    $scss = "@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');\n";

    // Base: Boost's self-contained default preset (bootstrap + fontawesome + moodle).
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');

    // OneBoard brand layer (fonts, login hero, accents) — compiled after the base.
    $scss .= file_get_contents($CFG->dirroot . '/theme/oneboard/scss/brand.scss');

    return $scss;
}

/**
 * Pre-SCSS: inject the OneBoard palette as SCSS variables before Bootstrap compiles,
 * so all derived colours (buttons, links, states) follow the brand automatically.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_oneboard_get_pre_scss($theme) {
    $scss = '';
    $scss .= '$primary: #534AB7;' . "\n";
    $scss .= '$secondary: #7F77DD;' . "\n";
    $scss .= '$link-color: #534AB7;' . "\n";
    $scss .= '$oneboard-dark: #3C3489;' . "\n";
    $scss .= '$oneboard-light: #AFA9EC;' . "\n";
    $scss .= '$oneboard-tint: #EEEDFE;' . "\n";
    $scss .= '$oneboard-darkbg: #1A1730;' . "\n";
    return $scss;
}

/**
 * Extra SCSS: defer to Boost so its background/footer handling still applies.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_oneboard_get_extra_scss($theme) {
    return theme_boost_get_extra_scss($theme);
}
