<?php

declare(strict_types=1);

namespace App\Domain\Transport;

use RuntimeException;

/**
 * A transport rule was broken. The message is safe to show to the user.
 */
class TransportException extends RuntimeException {}
