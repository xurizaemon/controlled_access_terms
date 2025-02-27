<?php

namespace Drupal\Tests\controlled_access_terms\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\controlled_access_terms\EDTFUtils;

/**
 * Tests the EDTF Utils.
 *
 * @package Drupal\Tests\controlled_access_terms\Kernel
 * @group controlled_access_terms
 * @coversDefaultClass \Drupal\controlled_access_terms\EDTFUtils
 */
class EdtfUtilsTest extends KernelTestBase {

  /**
   * @covers ::validate
   * @dataProvider validateDefaultsProvider
   */
  public function testEdtfValidateDefaults($input, $expected) {
    $this->assertEquals($expected, EDTFUtils::validate($input, FALSE, FALSE, FALSE));
  }

  /**
   * EDTF value and ISO 8601 Timestamp results.
   *
   * Empty values are valid (return no errors).
   *
   * @return array
   *   Array of test inputs.
   */
  public function validateDefaultsProvider(): array {
    return [
      ['1900', []],
      ['1900-01', []],
      ['1900-01-02', []],
      ['190X', []],
      ['1900-XX', []],
      ['1900-91', ['Provided month value \'91\' is not valid.']],
      ['1900-91-01', ['Provided month value \'91\' is not valid.']],
      ['1900-X1', ['Provided month value \'X1\' is not valid.']],
      // No validation for months with X.
      ['1900-3X', ['Provided month value \'3X\' is not valid.']],
      // Month 31 without a day matches summer so it's valid.
      ['1900-31', []],
      ['1900-31-01', ['Provided month value \'31\' is not valid.']],
      ['190X-5X-8X', ['Provided month value \'5X\' is not valid.']],
      ['19000', ['Years longer than 4 digits must be prefixed with a \'Y\'.']],
      ['Y19000', []],
      ['190u', ['Could not parse the date \'190u\'.']],
      ['190', ['Years must be at least 4 characters long.']],
      ['190-99-52',
        [
          'Years must be at least 4 characters long.',
          'Provided month value \'99\' is not valid.',
          'Provided day value \'52\' is not valid.',
        ],
      ],
      ['1900-01-02T', ['Time not provided with time seperator (T).']],
      ['1900-01-02T1:1:1', ['The date/time \'1900-01-02T1:1:1\' is invalid.']],
      ['1900-01-02T01:22:33', []],
      ['1900-01-02T01:22:33Z', []],
      ['1900-01-02T01:22:33+',
        [
          'The date/time \'1900-01-02T01:22:33+\' is invalid.',
        ],
      ],
      ['1900-01-02T01:22:33+05:00', []],
    ];
  }

  /**
   * @covers ::validate
   * @dataProvider validateAllParamsProvider
   */
  public function testEdtfValidateAllParams($input, $intervals, $sets, $strict, $expected) {
    $this->assertEquals($expected, EDTFUtils::validate($input, $intervals, $sets, $strict));
  }

  /**
   * EDTF value and ISO 8601 Timestamp results.
   *
   * Empty values are valid (return no errors).
   *
   * @return array
   *   Input, intervals, sets, strict, expected.
   */
  public function validateAllParamsProvider(): array {
    return [
      // Year only date.
      ['1900', FALSE, TRUE, FALSE, []],

      // Single.
      ['12/24', FALSE, TRUE, FALSE, ['Could not parse the date \'12/24\'.']],
      ['1024/2048', FALSE, TRUE, FALSE,
        [
          'Could not parse the date \'1024/2048\'.',
        ],
      ],

      // Valid interval, years only.
      ['1900/1920', TRUE, TRUE, FALSE, []],

      // Interval separator but invalid years.
      [
        '12/24',
        TRUE,
        TRUE,
        FALSE,
        [
          'Years must be at least 4 characters long.',
          'Years must be at least 4 characters long.',
        ],
      ],

      // Interval, one invalid year.
      ['1999/24', TRUE, TRUE, FALSE,
        [
          'Years must be at least 4 characters long.',
        ],
      ],
      ['1960-02/1960-03', TRUE, FALSE, FALSE, []],
      ['1960-02-01/1960-03-25', TRUE, FALSE, TRUE, []],

      // Invalid day value.
      [
        '1960-01-00',
        FALSE,
        FALSE,
        TRUE,
        [
          'Provided day value \'00\' is not valid.',
          'Strictly speaking, the date (and/or time) \'1960-01-00\' is invalid.',
        ],
      ],

    ];
  }

  /**
   * @covers ::iso8601Value
   * @dataProvider iso8601Provider
   */
  public function testIso8601($input, $expected) {
    $this->assertEquals($expected, EDTFUtils::iso8601Value($input));
  }

  /**
   * EDTF value and ISO 8601 Timestamp results.
   *
   * Empty values are invalid dates which return blank.
   *
   * @return array
   *   Array of test inputs.
   */
  public function iso8601Provider(): array {
    return [
      ['1900', '1900'],
      ['1900-01', '1900-01'],
      ['1900-01-02', '1900-01-02'],
      ['190X', '1900'],
      ['1900-XX', '1900-01'],
      ['1900-91', ''],
      ['1900-91-01', ''],
      ['1900-3X', ''],
      ['1900-31', '1900-03'],
      ['190X-5X-8X', ''],
      ['19000', ''],
      ['Y19000', '19000'],
      ['190u', ''],
      ['190', ''],
      ['190-99-52', ''],
      ['1900-01-02T', ''],
      ['1900-01-02T1:1:1', ''],
      ['1900-01-02T01:22:33', '1900-01-02T01:22:33'],
      ['1900-01-02T01:22:33Z', '1900-01-02T01:22:33Z'],
      ['1900-01-02T01:22:33+', ''],
      ['1900-01-02T01:22:33+05:00', '1900-01-02T01:22:33+05:00'],
      // Intervals and Sets should return the earliest value.
      ['1900/2023', '1900'],
      ['[1900,2023]', '1900'],
      ['[1900,2023}', '1900'],
    ];
  }

}
