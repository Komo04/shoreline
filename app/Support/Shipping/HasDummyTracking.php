<?php

namespace App\Support\Shipping;

/**
 * Backward-compatible alias trait.
 *
 * Use this when older classes still import HasDummyTracking.
 * The implementation is delegated to DummyTracking to avoid method conflicts.
 */
trait HasDummyTracking
{
    use DummyTracking;
}
