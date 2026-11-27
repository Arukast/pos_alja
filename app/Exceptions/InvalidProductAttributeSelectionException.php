<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Marks product-capability attribute errors so quote responses can use the
 * selected_attributes field without moving legacy attribute errors off "quote".
 */
final class InvalidProductAttributeSelectionException extends InvalidArgumentException {}
