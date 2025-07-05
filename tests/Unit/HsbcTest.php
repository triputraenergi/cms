<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;

class HsbcTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        Log::info("hello");
        $this->assertTrue(true);
    }
}
