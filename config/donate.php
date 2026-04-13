<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Revolut donation link
    |--------------------------------------------------------------------------
    |
    | Плащанията с карта се въвеждат само в интерфейса на Revolut (PCI).
    | Задайте DONATE_REVOLUT_ME_URL или само DONATE_REVOLUT_TAG.
    |
    */

    'revolut_tag' => env('DONATE_REVOLUT_TAG', 'aleksazn1b'),

    'revolut_me_url' => env('DONATE_REVOLUT_ME_URL') ?: null,

];
