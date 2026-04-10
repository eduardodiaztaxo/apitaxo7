<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SolicitudService
{
    public function getNextIdMov()
    {
        $updated = DB::affectingStatement(
            'UPDATE `sequence` SET `cur_value` = LAST_INSERT_ID(`cur_value`) + `increment` WHERE `name` = ?',
            ['IDSOLASIGNAR']
        );

        if ($updated === 0) {
            DB::insert("INSERT INTO `sequence` (`name`, `increment`, `cycle`, `cur_value`) VALUES (?, 1, 1, 1)", ['IDSOLASIGNAR']);

            return 1;
        }

        $result = DB::selectOne('SELECT LAST_INSERT_ID() as idMov');

        if ($result && isset($result->idMov)) {
            return (int) $result->idMov;
        }

        return 1;
    }
}
