<?php

namespace App\Controller\CardSearch;

use App\Repository\SphereRepository;
use App\Search\SearchKeys;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProcessSearchController extends AbstractController
{
    public function __construct(private readonly SphereRepository $sphereRepository)
    {
    }

    /**
     * Processes the action of the card search form.
     *
     * @Route("/process", name="cards_processSearchForm")
     */
    public function processAction(Request $request): RedirectResponse
    {
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';
        $operators = [':', '!', '<', '>'];
        $spheres = $this->sphereRepository->findAll();
        $params = [];
        if ('' != $request->query->get('q')) {
            $params[] = $request->query->get('q');
        }

        foreach (SearchKeys::$searchKeys as $key => $searchName) {
            if ('sphere' === $searchName) {
                $val = $request->query->all($key);
                if (count($val) > 0 && count($val) < count($spheres)) {
                    $params[] = $key.':'.implode('|', array_map(fn ($s) => str_contains($s, ' ') ? "\"{$s}\"" : $s, $val));
                }

                continue;
            }

            $val = $request->query->get($key);
            if (isset($val) && '' != $val) {
                if ('date_release' == $searchName) {
                    $op = '';
                } else {
                    if (!preg_match('/^[\\p{L}\\p{N}\\_\\-\\&]+$/u', $val, $match)) {
                        $val = "\"{$val}\"";
                    }

                    $op = $request->query->get($key.'o');
                    if (!in_array($op, $operators)) {
                        $op = ':';
                    }
                }

                $params[] = "{$key}{$op}{$val}";
            }
        }

        $find = ['q' => implode(' ', $params)];
        if ('name' != $sort) {
            $find['sort'] = $sort;
        }

        if ('list' != $view) {
            $find['view'] = $view;
        }

        return $this->redirect($this->generateUrl('cards_find').'?'.http_build_query($find));
    }
}
