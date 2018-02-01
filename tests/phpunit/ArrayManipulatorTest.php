<?php

namespace Grasmash\ComposerConverter\Tests;

use Grasmash\ComposerConverter\Utility\ArrayManipulator;

/**
 * Tests the ArrayManipulator class.
 */
class ArrayManipulatorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests ArrayManipulator::arrayMergeRecursiveExceptEmpty().
     *
     * @dataProvider providerTestArrayMergeRecursiveDistinct
     */
    public function testArrayMergeRecursiveDistinct(
        $array1,
        $array2,
        $expected_array
    ) {
        $this->assertEquals(ArrayManipulator::arrayMergeRecursiveDistinct(
            $array1,
            $array2
        ), $expected_array);
    }

    /**
     * Provides values to testArrayMergeRecursiveDistinct().
     *
     * @return array
     *   An array of values to test.
     */
    public function providerTestArrayMergeRecursiveDistinct()
    {

        return [
            [
                [
                    'modules' => [
                        'local' => [
                            'enable' => ['test'],
                        ],
                        'ci' => [
                            'uninstall' => ['shield'],
                        ],
                    ],
                    'behat' => [
                        'tags' => 'test',
                        'launch-selenium' => 'true',
                    ],
                ],
                [
                    'modules' => [
                        'local' => [
                            'enable' => [],
                        ],
                        'ci' => [
                            'uninstall' => ['shield'],
                        ],
                    ],
                    'behat' => [
                        'tags' => 'nottest',
                    ],
                ],
                [
                    'modules' => [
                        'local' => [
                            'enable' => ['test'],
                        ],
                        'ci' => [
                            'uninstall' => ['shield'],
                        ],
                    ],
                    'behat' => [
                        'tags' => 'nottest',
                        'launch-selenium' => 'true',
                    ],
                ],
            ],
        ];
    }
}
