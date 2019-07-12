<?php
/**
 * Created by PhpStorm.
 * User: tonyzou
 * Date: 2019-07-11
 * Time: 22:51
 */

namespace App\Controllers\API\v1;

use App\Models\Ann;

class AnnouncementController
{
    public function info($request, $response, $args)
    {
        $Anns = Ann::orderBy('date', 'desc')->get();

        $res['code'] = 0;
        $res['data'] = array();

        foreach ($Anns as $ann) {
            array_push($res['data'], array(
                'id' => $ann->id,
                'date' => $ann->date,
                'content' => $ann->markdown,
            ));
        }

        return $response->getBody()->write(json_encode($res));
    }
}