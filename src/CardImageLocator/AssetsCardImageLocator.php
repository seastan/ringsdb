<?php

namespace App\CardImageLocator;

use App\Entity\Card;
use App\Entity\CardPrinting;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

readonly class AssetsCardImageLocator implements CardImageLocatorInterface
{
    public function __construct(
        #[Target('card_image_url.cache')]
        private CacheInterface $cache,
        private Packages $packages,
        private string $publicDir,
    ) {
    }

    private function getImageUrlByCode(string $code): ?string
    {
        return $this->cache->get($code, function (ItemInterface $item) use ($code): ?string {
            $url = $this->packages->getUrl('bundles/cards/'.$code.'.png');
            $filename = $this->publicDir.preg_replace('/\?.*/', '', $url);

            return file_exists($filename) ? $url : null;
        });
    }

    public function getCardImageUrl(Card $card): ?string
    {
        return $this->getImageUrlByCode($card->getCode());
    }

    public function getPrintingImageUrl(CardPrinting $cardPrinting): ?string
    {
        return $this->getImageUrlByCode($cardPrinting->getImageCode());
    }
}
