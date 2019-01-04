<?php
namespace Granam\Tests\WebContentBuilder;

use Granam\WebContentBuilder\Redirect;
use PHPUnit\Framework\TestCase;

class RedirectTest extends TestCase
{
    /**
     * @test
     */
    public function I_can_use_it(): void
    {
        $redirect = new Redirect('foo', 123);
        self::assertSame('foo', $redirect->getTarget());
        self::assertSame(123, $redirect->getAfterSeconds());
    }
}