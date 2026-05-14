<?php

namespace App\Enums;

enum SenderType: string
{
    case Numeric = 'numeric';
    case AlphaNumeric = 'alphanumeric';
    case Shortcode = 'shortcode';
}

