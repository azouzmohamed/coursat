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
 * Skype icons filter phpunit tests
 *
 * @package    filter_emoticon
 * @category   test
 * @copyright  2013 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_emoticon;

use filter_emoticon;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/emoticon/filter.php'); // Include the code to test.

/**
 * Skype icons filter testcase.
 */
class filter_test extends \advanced_testcase {

    /**
     * Test that filter ignores nolink/pre element, and processes remaining text
     *
     * @param string $input
     * @param string $expectedprefix
     *
     * @dataProvider filter_emoticon_filtered_provider
     */
    public function test_filter_emoticon_filtered(string $input, string $expectedprefix): void {
        $this->resetAfterTest();

        $filteredtext = (new testable_filter_emoticon())->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]);

        $this->assertStringStartsWith($expectedprefix, $filteredtext);
        $this->assertStringEndsWith($this->get_converted_content_for_emoticon('(n)'), $filteredtext);
    }

    /**
     * Data provider for {@see test_filter_emoticon_filtered}
     *
     * @return string[]
     */
    public function filter_emoticon_filtered_provider(): array {
        return [
            'FORMAT_HTML is filtered' => [
                'input' => 'Hello(n)',
                'expectedprefix' => 'Hello',
            ],
            'Nested nolink should not be processed, emoticon postfix should be' => [
                'input' => '<span class="nolink"><span>(n)</span>(n)</span>(n)',
                'expectedprefix' => '<span class="nolink"><span>(n)</span>(n)</span>',
            ],
            'Nested pre should not be processed, emoticon postfix should be' => [
                'input' => '<pre><pre>(n)</pre>(n)</pre>(n)',
                'expectedprefix' => '<pre><pre>(n)</pre>(n)</pre>',
            ],
        ];
    }

    /**
     * Tests the filter doesn't affect nolink classes.
     *
     * @dataProvider filter_emoticon_provider
     */
    public function test_filter_emoticon($input, $format, $expected) {
        $this->resetAfterTest();

        $filter = new testable_filter_emoticon();
        $this->assertEquals($expected, $filter->filter($input, [
                'originalformat' => $format,
            ]));
    }

    /**
     * The data provider for filter emoticon tests, containing input that is not expected to be filtered
     *
     * @return  array
     */
    public static function filter_emoticon_provider(): array {
        $grr = '(grr)';
        return [
            'FORMAT_MOODLE is not filtered' => [
                'input' => $grr,
                'format' => FORMAT_MOODLE,
                'expected' => $grr,
            ],
            'FORMAT_MARKDOWN is not filtered' => [
                'input' => $grr,
                'format' => FORMAT_MARKDOWN,
                'expected' => $grr,
            ],
            'FORMAT_PLAIN is not filtered' => [
                'input' => $grr,
                'format' => FORMAT_PLAIN,
                'expected' => $grr,
            ],
            'Script tag should not be processed' => [
                'input' => "<script language='javascript'>alert('{$grr}');</script>",
                'format' => FORMAT_HTML,
                'expected' => "<script language='javascript'>alert('{$grr}');</script>",
            ],
            'Basic nolink should not be processed' => [
                'input' => '<span class="nolink">(n)</span>',
                'format' => FORMAT_HTML,
                'expected' => '<span class="nolink">(n)</span>',
            ],
            'Nested nolink should not be processed' => [
                'input' => '<span class="nolink"><span>(n)</span>(n)</span>',
                'format' => FORMAT_HTML,
                'expected' => '<span class="nolink"><span>(n)</span>(n)</span>',
            ],
            'Basic pre should not be processed' => [
                'input' => '<pre>(n)</pre>',
                'format' => FORMAT_HTML,
                'expected' => '<pre>(n)</pre>',
            ],
            'Nested pre should not be processed' => [
                'input' => '<pre><pre>(n)</pre>(n)</pre>',
                'format' => FORMAT_HTML,
                'expected' => '<pre><pre>(n)</pre>(n)</pre>',
            ],
        ];
    }

    /**
     * Translate the text for a single emoticon into the rendered value.
     *
     * @param   string  $text The text to translate.
     * @return  string
     */
    public function get_converted_content_for_emoticon($text) {
        global $OUTPUT;
        $manager = get_emoticon_manager();
        $emoticons = $manager->get_emoticons();
        foreach ($emoticons as $emoticon) {
            if ($emoticon->text == $text) {
                return $OUTPUT->render($manager->prepare_renderable_emoticon($emoticon));
            }
        }

        return $text;
    }

    /**
     * Tests the filter doesn't break anything if activated but invalid format passed.
     *
     */
    public function test_filter_invalidformat() {
        global $PAGE;
        $this->resetAfterTest();

        $filter = new testable_filter_emoticon();
        $input = '(grr)';
        $expected = '(grr)';

        $this->assertEquals($expected, $filter->filter($input, [
            'originalformat' => 'ILLEGALFORMAT',
        ]));
    }

    /**
     * Tests the filter doesn't break anything if activated but no emoticons available.
     *
     */
    public function test_filter_emptyemoticons() {
        global $CFG;
        $this->resetAfterTest();
        // Empty the emoticons array.
        $CFG->emoticons = null;

        $filter = new filter_emoticon(\context_system::instance(), array('originalformat' => FORMAT_HTML));

        $input = '(grr)';
        $expected = '(grr)';

        $this->assertEquals($expected, $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]));
    }
}

/**
 * Subclass for easier testing.
 */
class testable_filter_emoticon extends filter_emoticon {
    public function __construct() {
        // Reset static emoticon caches.
        parent::$emoticontexts = array();
        parent::$emoticonimgs = array();
        // Use this context for filtering.
        $this->context = \context_system::instance();
        // Define FORMAT_HTML as only one filtering in DB.
        set_config('formats', implode(',', array(FORMAT_HTML)), 'filter_emoticon');
    }
}
