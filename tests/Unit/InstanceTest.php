<?php

namespace Tests\Unit;

use App\Instance;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstanceTest extends TestCase
{
    #[Test]
    public function it_normalizes_the_domain_to_lowercase(): void
    {
        $instance = new Instance;
        $instance->domain = 'PIXELFED.Example.COM';

        $this->assertSame('pixelfed.example.com', $instance->domain);
    }
}
