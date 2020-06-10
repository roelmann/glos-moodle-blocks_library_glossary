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
 * Main block file.
 *
 * @package    block_library_glossary
 * @copyright  2019 Scott Braithwaite
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/extdb/classes/task/extdb.php');

class block_library_glossary extends block_base {

    public function init() {
        $this->title = get_string('blocktitle', 'block_library_glossary');
    }

    public function has_config() {
        return false;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function hide_header() {
        return true;
    }

    public function get_content() {

        if (!isloggedin()) {
            return false;
        }
        global $COURSE, $DB, $PAGE, $USER, $CFG;
        $pagelink = new moodle_url ('/blocks/library_glossary/pages/glossary.php');

        if (strlen($USER->institution) > 1) { // If user has course identified.
            $cattree = explode('~', $USER->institution);
            if (empty($cattree[2])) {
                $subjcode = '';
            }
            $instcode = $cattree[0];
            $schcode = $cattree[1];
            $scarray = explode('|', $cattree[2]);
            $subjcode = $scarray[0];
            $crscode = 'CRS-'.$cattree[3];
        } else {
            $subjcode = '';
        }

        $imageurl = $CFG->wwwroot.'/blocks/library_glossary/pix/library.jpg';
        $this->content = new stdClass;

        $this->content->text = '<div>';
        $this->content->text .= '<img src="'.$imageurl.'">';
        $this->content->text .= '</div>';
        $this->content->text .= '<h5 class = "courselinktext">';
        $this->content->text .= '<form id="courselink" action="'.$pagelink.'" method="post" style="margin:0 0 2px 0">';
        $this->content->text .= '<input type="hidden" name="subjectselect" value="'.$subjcode.'">';
        $this->content->text .= '<input type="submit" value="Library Subject Resources" class="courselinksubmit p-2" >';
        $this->content->text .= '</form>';
        $this->content->text .= '</h5>';

        return $this->content;

    }

    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('blocktitle', 'block_library_glossary');
            } else {
                $this->title = $this->config->title;
            }

            if (empty($this->config->text)) {
                $this->config->text = get_string('blocktext', 'block_library_glossary');
            }
        }
    }

    public function instance_config_save($data, $nolongerused = false) {
        if (get_config('library_glossary', 'Allow_HTML') == '1') {
            $data->text = strip_tags($data->text);
        }

        // And now forward to the default implementation defined in the parent class.
        return parent::instance_config_save($data, $nolongerused);
    }

    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values.
        $attributes['class'] .= ' block_'. $this->name(); // Append our class to class attribute.
        $attributes['class'] .= ' block_'. $this->title; // Append our class to class attribute.
        return $attributes;
    }
}

