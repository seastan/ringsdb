<?php

declare(strict_types=1);

namespace App\Controller\Admin\Stat;

/**
 * The packs and the cycle date ranges of the statistics, read from the Connection $connection of
 * the controller.
 */
trait StatPacksTrait
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPacks(): array
    {
        $query = "SELECT name, date_release\nFROM pack\nWHERE date_release IS NOT NULL\nORDER BY date_release";

        return $this->connection->executeQuery($query, [])->fetchAllAssociative();
    }

    /**
     * @return array<string, array{string, string}>
     */
    private function getPackRuless(): array
    {
        return ['Core Set' => ['2000-01-01', '2011-07-21'], 'Shadows of Mirkwood' => ['2011-07-21', '2012-01-06'], 'Dwarrowdelf' => ['2012-01-06', '2012-08-17'], 'Against the Shadow' => ['2012-08-17', '2014-02-21'], 'The Ring-maker' => ['2014-02-21', '2015-04-03'], 'Angmar Awakened' => ['2015-04-03', '2016-02-11'], 'Dream-chaser' => ['2016-02-11', '2016-11-23'], 'Haradrim' => ['2016-11-23', '2018-06-14'], 'Ered Mithrin' => ['2018-06-14', '2019-08-02'], 'Vengeance of Mordor' => ['2019-08-02', '2021-03-21'], 'ALeP - Oaths of the Rohirrim' => ['2021-03-21', '2099-12-31']];
    }
}
