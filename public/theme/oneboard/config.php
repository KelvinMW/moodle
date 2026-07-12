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
 * OneBoard theme config — a Boost child themed for oneboard.study.
 *
 * @package    theme_oneboard
 * @copyright  2026 oneboard.study
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$THEME->name = 'oneboard';

$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->enable_dock = false;

// Required so renderer overrides (theme_oneboard / theme_boost, e.g.
// firstview_fakeblocks used by Boost's drawers layout) are resolved.
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// Inherit Boost's page layouts.
$THEME->doctype = 'html5';

// SCSS pipeline (mirrors Boost, with the OneBoard brand layer added in lib.php).
$THEME->scss = function($theme) {
    return theme_oneboard_get_main_scss_content($theme);
};
$THEME->prescsscallback = 'theme_oneboard_get_pre_scss';
$THEME->extrascsscallback = 'theme_oneboard_get_extra_scss';

$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
