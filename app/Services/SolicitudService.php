<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SolicitudService
{
    public function getNextIdMov()
    {
        // Attempt to get the next value from the sequence
        $result = DB::select("SELECT nextval('IDSOLASIGNAR') as idMov FROM DUAL");

        if (!empty($result)) {
            // If the sequence exists and returns a value, use it
            $num_sol = $result[0]->idMov;
        } else {
            // If the sequence does not exist, create it and set the initial value
            DB::insert("INSERT INTO sequence(name, increment, cycle) VALUES('IDSOLASIGNAR', 1, 1)");
            $num_sol = 1;
        }

        return $num_sol;
    }
}
