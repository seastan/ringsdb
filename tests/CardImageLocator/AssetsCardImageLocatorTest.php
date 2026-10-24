<?php

declare(strict_types=1);

namespace App\Tests\CardImageLocator;

use App\CardImageLocator\AssetsCardImageLocator;
use App\Entity\Card;
use App\Entity\CardPrinting;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * The locator returns the asset URL of an image only when the file exists in the public directory.
 */
class AssetsCardImageLocatorTest extends TestCase
{
    private string $publicDir;

    private AssetsCardImageLocator $locator;

    protected function setUp(): void
    {
        $this->publicDir = sys_get_temp_dir().'/'.uniqid('card_images_', true);
        mkdir($this->publicDir.'/bundles/cards', 0777, true);
        touch($this->publicDir.'/bundles/cards/01001.png');

        // The version query string must be ignored when looking for the file.
        $packages = new Packages(new PathPackage('/', new StaticVersionStrategy('v1')));
        $this->locator = new AssetsCardImageLocator(new ArrayAdapter(), $packages, $this->publicDir);
    }

    protected function tearDown(): void
    {
        unlink($this->publicDir.'/bundles/cards/01001.png');
        rmdir($this->publicDir.'/bundles/cards');
        rmdir($this->publicDir.'/bundles');
        rmdir($this->publicDir);
    }

    public function testCardImageUrl(): void
    {
        $this->assertSame('/bundles/cards/01001.png?v1', $this->locator->getCardImageUrl(new Card()->setCode('01001')));
        $this->assertNull($this->locator->getCardImageUrl(new Card()->setCode('01002')));
    }

    public function testPrintingImageUrl(): void
    {
        $this->assertSame('/bundles/cards/01001.png?v1', $this->locator->getPrintingImageUrl(new CardPrinting()->setImageCode('01001')));
        $this->assertNull($this->locator->getPrintingImageUrl(new CardPrinting()->setImageCode('01002')));
    }
}
