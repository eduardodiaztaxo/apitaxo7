<?php

namespace App\Services;

use App\Models\Inventario\EmplazamientoNn;

class EmplazamientosRecursiveTreeViewService
{

    const NUM_LEVELS = 6;

    public static function showEmplazamientosRecursiveTreeView(string $keyword, int $agenda_id)
    {




        $found_parents_codes = self::doEmptyArrayCodesByLevel();

        $found_children_codes = self::doEmptyArrayCodesByLevel();

        for ($i = self::NUM_LEVELS; $i > 0; $i--) {


            $codes = self::getSubPlacesByLevelAndWord($agenda_id, $i, $keyword);


            foreach ($codes as $code => $itemCode) {


                $parentCodes = self::getParentLevelCode($agenda_id, $i, $itemCode);


                $found_parents_codes = self::mergeCodesByLevel($found_parents_codes, $parentCodes);


                $childrenCodes = self::getChildrenLevelCode($agenda_id, $i, $code);

                $found_children_codes = self::mergeCodesByLevel($found_children_codes, $childrenCodes);
            }
        }



        //Consolidate, merge and delete repeat data
        $consolidate = self::consolidateChildAndParentCodes($found_children_codes, $found_parents_codes);


        //Este array ya contiene todos los datos normalizados
        return self::getTreeSubplaceFromConsolidateData($consolidate);
    }

    private static function doEmptyArrayCodesByLevel()
    {
        $group = [];
        for ($i = 1; $i <= self::NUM_LEVELS; $i++) {
            $indexLevel = "n" . $i;
            $group[$indexLevel] = [];
        }
        return $group;
    }

    private static function getParentLevelCode(int $agenda_id, int $level, array $itemCode): array
    {
        $codes = [];
        $baseCode = $itemCode['codigoUbicacion'];
        $codes['n' . $level][$baseCode] = $itemCode;
        for ($j = $level - 1; $j > 0; $j--) {
            $parentCode = substr($baseCode, 0, 2 * $j);
            $parentTable = 'ubicaciones_n' . $j;
            $emplaObj = EmplazamientoNn::fromTable($parentTable)->where('idAgenda', $agenda_id)->where('codigoUbicacion', $parentCode)->first();
            $codes['n' . $j][$parentCode] = [
                'codigoUbicacion' => $emplaObj->codigoUbicacion,
                'nombre' => $emplaObj->descripcionUbicacion
            ];
        }

        return $codes;
    }

    private static function getChildrenLevelCode(int $agenda_id, int $level, string $code): array
    {
        $codes = [];
        for ($k = $level + 1; $k <= self::NUM_LEVELS; $k++) {
            $nextLevel = 'n' . $k;
            $nextTable = 'ubicaciones_n' . $k;

            $children_codes = EmplazamientoNn::fromTable($nextTable)
                ->select('codigoUbicacion', 'descripcionUbicacion')->where('idAgenda', $agenda_id)
                ->where('codigoUbicacion', 'LIKE', '' . $code . '%')
                ->get();

            foreach ($children_codes as $childCodeItem) {
                $childCode = $childCodeItem->codigoUbicacion;
                $codes[$nextLevel][$childCode] = [
                    'codigoUbicacion' => $childCodeItem->codigoUbicacion,
                    'nombre' => $childCodeItem->descripcionUbicacion
                ];
            }
        }

        return $codes;
    }

    private static function mergeCodesByLevel(array $currentCodes, array $newCodes)
    {
        foreach ($newCodes as $key => $groupCode) {
            foreach ($groupCode as $code => $itemCode) {
                $currentCodes[$key][$code] = $itemCode;
            }
        }

        return $currentCodes;
    }




    private static function getSubPlacesByLevelAndWord(int $agenda_id, int $level, string $phrase)
    {

        $complete_word = trim($phrase);
        $possible_name_words = keyword_search_terms_from_keyword($phrase);
        $table = 'ubicaciones_n' . $level;
        $queryBuilder = EmplazamientoNn::fromTable($table)->where('idAgenda', $agenda_id)
            ->where('descripcionUbicacion', 'LIKE', '%' . $complete_word . '%');


        if (count($possible_name_words) > 1) {
            $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                foreach ($possible_name_words as $palabra) {
                    $query->where('descripcionUbicacion', 'LIKE', "%$palabra%");
                }
            });
        }

        $emplazamientos = $queryBuilder->get();

        $codes = [];

        foreach ($emplazamientos as $emplazamiento) {
            $codes[$emplazamiento->codigoUbicacion] = [
                'codigoUbicacion' => $emplazamiento->codigoUbicacion,
                'nombre' => $emplazamiento->descripcionUbicacion
            ];
        }

        return $codes;
    }

    /**
     * @param array $find_children_codes['n1'=>[code1,code2,code3...coden],'n2'=>[code1,code2,code3...coden], ... 'nn'=>[code1,code2,code3...coden]]
     * @param array $find_parents_codes['n1'=>[code1,code2,code3...coden],'n2'=>[code1,code2,code3...coden], ... 'nn'=>[code1,code2,code3...coden]]
     * 
     * @return array ['n1'=>[code1,code2,code3...coden],'n2'=>[code1,code2,code3...coden], ... 'nn'=>[code1,code2,code3...coden]]
     */
    private static function consolidateChildAndParentCodes(array $found_children_codes, array $found_parents_codes)
    {
        $consolidate = [];

        for ($m = 1; $m <= 6; $m++) {
            $consolidate['n' . $m] = array_merge($found_parents_codes['n' . $m], $found_children_codes['n' . $m]);
            //$consolidate['n' . $m] = array_values(array_unique($found_codes));
        }

        return $consolidate;
    }

    /**
     * @param array $consolidate ['n1'=>[code1,code2,code3], 'n2'=>[code1,code2,code3], 'n3'=>[code1,code2,code3]]
     * 
     * @return array [
     *                  [
     *                      'item' => $itemN1, 
     *                      'children' => [
     *                          ['item' => $itemN2, 'children' => $children_code],
     *                          ['item' => $itemN2, 'children' => $children_code]  
     *                      ]
     *                  ],
     *                  [
     *                      'item' => $itemN1, 
     *                      'children' => [
     *                          ['item' => $itemN2, 'children' => $children_code],
     *                          ['item' => $itemN2, 'children' => $children_code]  
     *                      ]
     *                  ]
     *              ]
     */
    private static function getTreeSubplaceFromConsolidateData(array $consolidate): array
    {


        $tree_codes = [];

        $last_codes = [];

        for ($n = 6; $n > 0; $n--) {
            foreach ($consolidate['n' . $n] as $parentcode => $itemParentCode) {

                $item = $itemParentCode;
                $children_code = [];

                foreach ($tree_codes as $childcode) {
                    if (str_starts_with($childcode['item']['codigoUbicacion'], $parentcode)) {
                        $children_code[] = $childcode;
                    }
                }

                $last_codes[] = ['item' => $item, 'children' => $children_code];
            }

            //bajo el supuesto de que todos los objetos fueron organizados de forma diferente
            //Y que el arreglo contiene todo lo de consolidate en el nivel especificado 
            $tree_codes = $last_codes;

            $last_codes = [];
        }

        return $tree_codes;
    }
}
