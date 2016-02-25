<?php

namespace Keboola\Syrup\Test\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 22/02/16
 * Time: 12:07
 */
class TestController extends Controller
{
    public function noticeAction()
    {
        $foo = ['a', 'b', 'c'];
        return new JsonResponse(['result' => $foo[3]]);
    }
}
