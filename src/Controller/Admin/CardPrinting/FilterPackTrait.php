<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Entity\Pack;
use Symfony\Component\HttpFoundation\Request;

/**
 * The pack the card printing pages are filtered on (query parameter "filter_pack"), read from the
 * PackRepository $packRepository of the controller.
 */
trait FilterPackTrait
{
    private function resolveFilterPack(Request $request): ?Pack
    {
        $id = $request->query->get('filter_pack');
        if (!$id) {
            return null;
        }

        return $this->packRepository->find($id);
    }
}
