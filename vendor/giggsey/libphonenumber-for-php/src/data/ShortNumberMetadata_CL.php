<?php
/**
 * libphonenumber-for-php-lite data file
 * This file has been @generated from libphonenumber data
 * Do not modify!
 * @internal
 */

return [
    'generalDesc' => [
        'NationalNumberPattern' => '[1-9]\\d{2,4}',
        'PossibleLength' => [
            3,
            4,
            5,
        ],
        'PossibleLengthLocalOnly' => [],
    ],
    'tollFree' => [
        'NationalNumberPattern' => '1(?:213|3[1-3])|434\\d|911',
        'ExampleNumber' => '131',
        'PossibleLength' => [
            3,
            4,
        ],
        'PossibleLengthLocalOnly' => [],
    ],
    'premiumRate' => [
        'NationalNumberPattern' => '1(?:211|3(?:13|[348]0|5[01]))|(?:1(?:[05]6|[48]1|9[18])|2(?:01\\d|[23]2|77|88)|3(?:0[59]|13|3[279]|66)|4(?:[12]4|36\\d|4[017]|55)|5(?:00|41\\d|5[67]|99)|6(?:07\\d|13|22|3[06]|50|69)|787|8(?:[01]1|[48]8)|9(?:01|[12]0|33))\\d',
        'ExampleNumber' => '1060',
        'PossibleLength' => [
            4,
            5,
        ],
        'PossibleLengthLocalOnly' => [],
    ],
    'emergency' => [
        'NationalNumberPattern' => '13[1-3]|911',
        'ExampleNumber' => '131',
        'PossibleLength' => [
            3,
        ],
        'PossibleLengthLocalOnly' => [],
    ],
    'shortCode' => [
        'NationalNumberPattern' => '1(?:00|21[13]|3(?:13|[348]0|5[01])|4(?:0[02-6]|17|[379])|818|919)|2(?:0(?:01|122)|22[47]|323|777|882)|3(?:0(?:51|99)|132|3(?:29|[37]7)|665)|43656|5(?:(?:00|415)4|5(?:66|77)|995)|6(?:131|222|366|699)|7878|8(?:011|11[28]|482|889)|9(?:01|1)1|13\\d|4(?:[13]42|243|4(?:02|15|77)|554)|(?:1(?:[05]6|98)|339|6(?:07|[35])0|9(?:[12]0|33))0',
        'ExampleNumber' => '100',
        'PossibleLength' => [],
        'PossibleLengthLocalOnly' => [],
    ],
    'standardRate' => [
        'NationalNumberPattern' => '(?:200|333)\\d',
        'ExampleNumber' => '2000',
        'PossibleLength' => [
            4,
        ],
        'PossibleLengthLocalOnly' => [],
    ],
    'carrierSpecific' => [
        'PossibleLength' => [
            -1,
        ],
        'PossibleLengthLocalOnly' => [],
    ],
    'smsServices' => [
        'NationalNumberPattern' => '13(?:13|[348]0|5[01])|(?:1(?:[05]6|[28]1|4[01]|9[18])|2(?:0(?:0|1\\d)|[23]2|77|88)|3(?:0[59]|13|3[2379]|66)|436\\d|5(?:00|41\\d|5[67]|99)|6(?:07\\d|13|22|3[06]|50|69)|787|8(?:[01]1|[48]8)|9(?:01|[12]0|33))\\d|4(?:[1-3]4|4[017]|55)\\d',
        'ExampleNumber' => '1060',
        'PossibleLength' => [
            4,
            5,
        ],
        'PossibleLengthLocalOnly' => [],
    ],
    'id' => 'CL',
    'countryCode' => 0,
    'internationalPrefix' => '',
    'sameMobileAndFixedLinePattern' => false,
    'numberFormat' => [],
    'intlNumberFormat' => [],
    'mainCountryForCode' => false,
    'mobileNumberPortableRegion' => false,
];
