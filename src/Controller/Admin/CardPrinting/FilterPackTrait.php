<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Entity\Pack;
use App\Model\FilterPackDto;

/**
 * The pack the card printing pages are filtered on (query parameter "filter_pack", see FilterPackDto), read from the
 * PackRepository $packRepository of the controller.
 */
trait FilterPackTrait
{
    private function resolveFilterPack(FilterPackDto $query): ?Pack
    {
        $id = $query->filterPack;
        if (!$id) {
            return null;
        }

        return $this->packRepository->find($id);
    }
}
