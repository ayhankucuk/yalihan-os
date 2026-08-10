<?php

namespace App\Infrastructure\ChannelManager\Channex\Exceptions;

/**
 * Thrown when availability data fails validation before sending to Channex.
 * This is a validation error, NOT a transport error.
 */
class ChannexMalformedDataException extends \RuntimeException {}
