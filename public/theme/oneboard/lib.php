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
 * Structure mirrors Boost/Classic: the Boost preset is the SCSS base, the brand
 * palette is injected as pre-SCSS (so it wins over Bootstrap's `!default`), and
 * the brand rules are appended as extra SCSS (compiled in the same pass).
 *
 * @package    theme_oneboard
 * @copyright  2026 oneboard.study
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main SCSS — the Boost default preset (self-contained: bootstrap + fontawesome + moodle).
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_oneboard_get_main_scss_content($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}

/**
 * Pre-SCSS — inject the OneBoard palette before Bootstrap compiles so every
 * derived colour (buttons, links, states) follows the brand automatically.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_oneboard_get_pre_scss($theme) {
    return <<<SCSS
\$primary: #534AB7;
\$secondary: #7F77DD;
\$link-color: #534AB7;
\$oneboard-dark: #3C3489;
\$oneboard-light: #AFA9EC;
\$oneboard-tint: #EEEDFE;
\$oneboard-darkbg: #1A1730;
SCSS;
}

/**
 * Extra SCSS — the OneBoard brand layer (typography, accents, login hero).
 * Moodle already appends the parent Boost extra SCSS separately, so we only
 * return our own here (returning Boost's too would compile it twice and fail).
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_oneboard_get_extra_scss($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/oneboard/scss/brand.scss');
}
