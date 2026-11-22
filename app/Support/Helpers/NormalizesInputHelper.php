<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use App\Http\Requests\Concerns\NormalizesInput;

/**
 * Static access to the input normalisers for code that is not a Form Request.
 */
final class NormalizesInputHelper
{
    use NormalizesInput;
}
