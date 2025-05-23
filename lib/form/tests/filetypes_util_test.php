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

namespace core_form;

/**
 * Test cases for the {@link core_form\filetypes_util} class.
 *
 * @package   core_form
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \core_form\filetypes_util
 */
final class filetypes_util_test extends \advanced_testcase {
    /**
     * Test normalizing list of extensions.
     */
    public function test_normalize_file_types(): void {
        $this->resetAfterTest(true);
        $util = new filetypes_util();

        $this->assertSame(['.odt'], $util->normalize_file_types('.odt'));
        $this->assertSame(['.odt'], $util->normalize_file_types('odt'));
        $this->assertSame(['.odt'], $util->normalize_file_types('.ODT'));
        $this->assertSame(['.doc', '.jpg', '.mp3'], $util->normalize_file_types('doc, jpg, mp3'));
        $this->assertSame(['.doc', '.jpg', '.mp3'], $util->normalize_file_types(['.doc', '.jpg', '.mp3']));
        $this->assertSame(['.doc', '.jpg', '.mp3'], $util->normalize_file_types('doc, *.jpg, mp3'));
        $this->assertSame(['.doc', '.jpg', '.mp3'], $util->normalize_file_types(['doc ', ' JPG ', '.mp3']));
        $this->assertSame(['.rtf', '.pdf', '.docx'],
            $util->normalize_file_types("RTF,.pdf\n...DocX,,,;\rPDF\trtf ...Rtf"));
        $this->assertSame(['.tgz', '.tar.gz'], $util->normalize_file_types('tgz,TAR.GZ tar.gz .tar.gz tgz TGZ'));
        $this->assertSame(['.notebook'], $util->normalize_file_types('"Notebook":notebook;NOTEBOOK;,\'NoTeBook\''));
        $this->assertSame([], $util->normalize_file_types(''));
        $this->assertSame([], $util->normalize_file_types([]));
        $this->assertSame(['.0'], $util->normalize_file_types(0));
        $this->assertSame(['.0'], $util->normalize_file_types('0'));
        $this->assertSame(['.odt'], $util->normalize_file_types('*.odt'));
        $this->assertSame([], $util->normalize_file_types('.'));
        $this->assertSame(['.foo'], $util->normalize_file_types('. foo'));
        $this->assertSame(['*'], $util->normalize_file_types('*'));
        $this->assertSame([], $util->normalize_file_types('*~'));
        $this->assertSame(['.pdf', '.ps'], $util->normalize_file_types('pdf *.ps foo* *bar .r??'));
        $this->assertSame(['*'], $util->normalize_file_types('pdf *.ps foo* * *bar .r??'));
    }

    /**
     * Test MIME type formal recognition.
     */
    public function test_looks_like_mimetype(): void {
        $this->resetAfterTest(true);
        $util = new filetypes_util();

        $this->assertTrue($util->looks_like_mimetype('type/subtype'));
        $this->assertTrue($util->looks_like_mimetype('type/x-subtype'));
        $this->assertTrue($util->looks_like_mimetype('type/x-subtype+xml'));
        $this->assertTrue($util->looks_like_mimetype('type/vnd.subtype.xml'));
        $this->assertTrue($util->looks_like_mimetype('type/vnd.subtype+xml'));

        $this->assertFalse($util->looks_like_mimetype('.gif'));
        $this->assertFalse($util->looks_like_mimetype('audio'));
        $this->assertFalse($util->looks_like_mimetype('foo/bar/baz'));
    }

    /**
     * Test getting/checking group.
     */
    public function test_is_filetype_group(): void {
        $this->resetAfterTest(true);
        $util = new filetypes_util();

        $audio = $util->is_filetype_group('audio');
        $this->assertNotFalse($audio);
        $this->assertIsArray($audio->extensions);
        $this->assertIsArray($audio->mimetypes);

        $this->assertFalse($util->is_filetype_group('.gif'));
        $this->assertFalse($util->is_filetype_group('somethingveryunlikelytoeverexist'));
    }


    /**
     * Test describing list of extensions.
     */
    public function test_describe_file_types(): void {
        $this->resetAfterTest(true);
        $util = new filetypes_util();

        force_current_language('en');

        // Check that it is able to describe individual file extensions.
        $desc = $util->describe_file_types('jpg .jpeg *.jpe PNG;.gif,  mudrd8mz');
        $this->assertTrue($desc->hasdescriptions);

        $desc = $desc->descriptions;
        $this->assertEquals(4, count($desc));

        $this->assertEquals('File', $desc[0]->description);
        $this->assertEquals('.mudrd8mz', $desc[0]->extensions);

        $this->assertEquals('Image (JPEG)', $desc[2]->description);
        $this->assertStringContainsString('.jpg', $desc[2]->extensions);
        $this->assertStringContainsString('.jpeg', $desc[2]->extensions);
        $this->assertStringContainsString('.jpe', $desc[2]->extensions);

        // Check that it can describe groups and mimetypes too.
        $desc = $util->describe_file_types('audio text/plain');
        $this->assertTrue($desc->hasdescriptions);

        $desc = $desc->descriptions;
        $this->assertEquals(2, count($desc));

        $this->assertEquals('Audio files', $desc[0]->description);
        $this->assertStringContainsString('.mp3', $desc[0]->extensions);
        $this->assertStringContainsString('.wav', $desc[0]->extensions);
        $this->assertStringContainsString('.ogg', $desc[0]->extensions);

        $this->assertEquals('Text file', $desc[1]->description);
        $this->assertStringContainsString('.txt', $desc[1]->extensions);

        // Empty.
        $desc = $util->describe_file_types('');
        $this->assertFalse($desc->hasdescriptions);
        $this->assertEmpty($desc->descriptions);

        // Any.
        $desc = $util->describe_file_types('*');
        $this->assertTrue($desc->hasdescriptions);
        $this->assertNotEmpty($desc->descriptions[0]->description);
        $this->assertEmpty($desc->descriptions[0]->extensions);

        // Unknown mimetype.
        $desc = $util->describe_file_types('application/x-something-really-unlikely-ever-exist');
        $this->assertTrue($desc->hasdescriptions);
        $this->assertEquals('application/x-something-really-unlikely-ever-exist', $desc->descriptions[0]->description);
        $this->assertEmpty($desc->descriptions[0]->extensions);
    }

    /**
     * Test expanding mime types into extensions.
     */
    public function test_expand(): void {
        $this->resetAfterTest(true);
        $util = new filetypes_util();

        $this->assertSame([], $util->expand(''));

        $expanded = $util->expand('document .cdr text/plain');
        $this->assertNotContains('document', $expanded);
        $this->assertNotContains('text/plain', $expanded);
        $this->assertContains('.doc', $expanded);
        $this->assertContains('.odt', $expanded);
        $this->assertContains('.txt', $expanded);
        $this->assertContains('.cdr', $expanded);

        $expanded = $util->expand('document .cdr text/plain', true, false);
        $this->assertContains('document', $expanded);
        $this->assertNotContains('text/plain', $expanded);
        $this->assertContains('.doc', $expanded);
        $this->assertContains('.odt', $expanded);
        $this->assertContains('.txt', $expanded);
        $this->assertContains('.cdr', $expanded);

        $expanded = $util->expand('document .cdr text/plain', false, true);
        $this->assertNotContains('document', $expanded);
        $this->assertContains('text/plain', $expanded);
        $this->assertContains('.doc', $expanded);
        $this->assertContains('.odt', $expanded);
        $this->assertContains('.txt', $expanded);
        $this->assertContains('.cdr', $expanded);

        $this->assertSame([], $util->expand('foo/bar', true, false));
        $this->assertSame(['foo/bar'], $util->expand('foo/bar', true, true));
    }

    /**
     * Test checking that a type is among others.
     */
    public function test_is_listed(): void {
        $this->resetAfterTest(true);
        $util = new filetypes_util();

        // These should be intuitively true.
        $this->assertTrue($util->is_listed('txt', 'text/plain'));
        $this->assertTrue($util->is_listed('txt', 'doc txt rtf'));
        $this->assertTrue($util->is_listed('.txt', '.doc;.txt;.rtf'));
        $this->assertTrue($util->is_listed('audio', 'text/plain audio video'));
        $this->assertTrue($util->is_listed('text/plain', 'text/plain audio video'));
        $this->assertTrue($util->is_listed('jpg jpe jpeg', 'image/jpeg'));
        $this->assertTrue($util->is_listed(['jpg', 'jpe', '.png'], 'image'));

        // These should be intuitively false.
        $this->assertFalse($util->is_listed('.gif', 'text/plain'));

        // Not all text/plain formats are in the document group.
        $this->assertFalse($util->is_listed('text/plain', 'document'));

        // Not all documents (and also the group itself) is not a plain text.
        $this->assertFalse($util->is_listed('document', 'text/plain'));

        // This may look wrong at the first sight as you might expect that the
        // mimetype should simply map to an extension ...
        $this->assertFalse($util->is_listed('image/jpeg', '.jpg'));

        // But it is principally same situation as this (there is no 1:1 mapping).
        $this->assertFalse($util->is_listed('.c', '.txt'));
        $this->assertTrue($util->is_listed('.txt .c', 'text/plain'));
        $this->assertFalse($util->is_listed('text/plain', '.c'));

        // Any type is included if the filter is empty.
        $this->assertTrue($util->is_listed('txt', ''));
        $this->assertTrue($util->is_listed('txt', '*'));

        // Empty value is part of any list.
        $this->assertTrue($util->is_listed('', '.txt'));
    }

    /**
     * Test getting types not present in a list.
     */
    public function test_get_not_listed(): void {
        $this->resetAfterTest(true);
        $util = new filetypes_util();

        $this->assertEmpty($util->get_not_listed('txt', 'text/plain'));
        $this->assertEmpty($util->get_not_listed('txt', '.doc .txt .rtf'));
        $this->assertEmpty($util->get_not_listed('txt', 'text/plain'));
        $this->assertEmpty($util->get_not_listed(['jpg', 'jpe', 'jpeg'], 'image/jpeg'));
        $this->assertEmpty($util->get_not_listed('', 'foo/bar'));
        $this->assertEmpty($util->get_not_listed('.foobar', ''));
        $this->assertEmpty($util->get_not_listed('.foobar', '*'));

        // Returned list is normalized so extensions have the dot added.
        $this->assertContains('.exe', $util->get_not_listed('exe', '.c .h'));

        // If this looks wrong to you, see {@see self::test_is_listed()} for more details on this behaviour.
        $this->assertContains('image/jpeg', $util->get_not_listed('image/jpeg', '.jpg .jpeg'));
    }

    /**
     * Test populating the tree for the browser.
     */
    public function test_data_for_browser(): void {
        $this->resetAfterTest(true);
        $util = new filetypes_util();

        $data = $util->data_for_browser();
        $this->assertContainsOnly('object', $data);
        foreach ($data as $group) {
            $this->assertObjectHasAttribute('key', $group);
            $this->assertObjectHasAttribute('types', $group);
            if ($group->key !== '') {
                $this->assertTrue($group->selectable);
            }
        }

        // Confirm that the reserved type '.xxx' isn't present in the 'Other files' section.
        $types = array_reduce($data, function($carry, $group) {
            if ($group->name === 'Other files') {
                return $group->types;
            }
        });
        $typekeys = array_map(function($type) {
            return $type->key;
        }, $types);
        $this->assertNotContains('.xxx', $typekeys);

        // All these three files are in both "image" and also "web_image"
        // groups. We display both groups.
        $data = $util->data_for_browser('jpg png gif', true, '.gif');
        $this->assertEquals(3, count($data));
        $this->assertTrue($data[0]->key !== $data[1]->key);
        foreach ($data as $group) {
            $this->assertTrue(($group->key === 'image' || $group->key === 'web_image' || $group->key === 'optimised_image'));
            $this->assertEquals(3, count($group->types));
            $this->assertFalse($group->selectable);
            foreach ($group->types as $ext) {
                if ($ext->key === '.gif') {
                    $this->assertTrue($ext->selected);
                } else {
                    $this->assertFalse($ext->selected);
                }
            }
        }

        // The groups web_image and optimised_image are a subset of the group image. The
        // file extensions that fall into these groups will be displayed thrice.
        $data = $util->data_for_browser('web_image');
        foreach ($data as $group) {
            $this->assertTrue(($group->key === 'image' || $group->key === 'web_image' || $group->key === 'optimised_image'));
        }

        // Check that "All file types" are displayed first.
        $data = $util->data_for_browser();
        $group = array_shift($data);
        $this->assertEquals('*', $group->key);

        // Check that "All file types" is not displayed if should not.
        $data = $util->data_for_browser(null, false);
        $group = array_shift($data);
        $this->assertNotEquals('*', $group->key);

        // Groups with an extension selected start expanded. The "Other files"
        // starts expanded. The rest start collapsed.
        $data = $util->data_for_browser(null, false, '.png');
        foreach ($data as $group) {
            if ($group->key === 'document') {
                $this->assertfalse($group->expanded);
            } else if ($group->key === '') {
                $this->assertTrue($group->expanded);
            }
            foreach ($group->types as $ext) {
                foreach ($group->types as $ext) {
                    if ($ext->key === '.png') {
                        $this->assertTrue($ext->selected);
                        $this->assertTrue($group->expanded);
                    }
                }
            }
        }
    }

    /**
     * Data provider for testing test_is_allowed_file_type.
     *
     * @return array
     */
    public static function is_allowed_file_type_provider(): array {
        return [
            'Filetype not in extension list' => [
                'filename' => 'test.xml',
                'list' => '.png .jpg',
                'expected' => false
            ],
            'Filetype not in mimetype list' => [
                'filename' => 'test.xml',
                'list' => 'image/png',
                'expected' => false
            ],
            'Filetype not in group list' => [
                'filename' => 'test.xml',
                'list' => 'web_file',
                'expected' => false
            ],
            'Filetype in list as extension' => [
                'filename' => 'test.xml',
                'list' => 'xml',
                'expected' => true
            ],
            'Empty list should allow all' => [
                'filename' => 'test.xml',
                'list' => '',
                'expected' => true
            ],
            'Filetype in list but later on' => [
                'filename' => 'test.xml',
                'list' => 'gif;jpeg,image/png xml xlsx',
                'expected' => true
            ],
            'Filetype in list as mimetype' => [
                'filename' => 'test.xml',
                'list' => 'image/png application/xml',
                'expected' => true
            ],
            'Filetype in list as group' => [
                'filename' => 'test.html',
                'list' => 'video,web_file',
                'expected' => true
            ],
        ];
    }

    /**
     * Test is_allowed_file_type().
     * @dataProvider is_allowed_file_type_provider
     * @param string $filename The filename to check
     * @param string $list The space , or ; separated list of types supported
     * @param boolean $expected The expected result. True if the file is allowed, false if not.
     */
    public function test_is_allowed_file_type($filename, $list, $expected) {
        $util = new filetypes_util();
        $this->assertSame($expected, $util->is_allowed_file_type($filename, $list));
    }

    /**
     * Data provider for testing test_get_unknown_file_types.
     *
     * @return array
     */
    public static function get_unknown_file_types_provider(): array {
        return [
            'Empty list' => [
                'filetypes' => '',
                'expected' => [],
            ],
            'Any file type' => [
                'filetypes' => '*',
                'expected' => [],
            ],
            'Unknown extension' => [
                'filetypes' => '.rat',
                'expected' => ['.rat']
            ],
            'Multiple unknown extensions' => [
                'filetypes' => '.ricefield .rat',
                'expected' => ['.ricefield', '.rat']
            ],
            'Existant extension' => [
                'filetypes' => '.xml',
                'expected' => []
            ],
            'Existant group' => [
                'filetypes' => 'web_file',
                'expected' => []
            ],
            'Nonexistant mimetypes' => [
                'filetypes' => 'ricefield/rat',
                'expected' => ['ricefield/rat']
            ],
            'Existant mimetype' => [
                'filetypes' => 'application/xml',
                'expected' => []
            ],
            'Multiple unknown mimetypes' => [
                'filetypes' => 'ricefield/rat cam/ball',
                'expected' => ['ricefield/rat', 'cam/ball']
            ],
            'Strange characters in unknown extension/group' => [
                'filetypes' => '©ç√√ß∂å√©åß©√',
                'expected' => ['.©ç√√ß∂å√©åß©√']
            ],
            'Some existant some not' => [
                'filetypes' => '.txt application/xml web_file ©ç√√ß∂å√©åß©√ .png ricefield/rat document',
                'expected' => ['.©ç√√ß∂å√©åß©√', 'ricefield/rat']
            ],
            'Reserved file type xxx included' => [
                'filetypes' => '.xxx .html .jpg',
                'expected' => ['.xxx']
            ]
        ];
    }

    /**
     * Test get_unknown_file_types().
     * @dataProvider get_unknown_file_types_provider
     * @param string $filetypes The filetypes to check
     * @param array $expected The expected result. The list of non existant file types.
     */
    public function test_get_unknown_file_types($filetypes, $expected) {
        $util = new filetypes_util();
        $this->assertSame($expected, $util->get_unknown_file_types($filetypes));
    }
}
