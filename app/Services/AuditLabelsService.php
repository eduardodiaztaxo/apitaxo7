<?php

namespace App\Services;

use App\Models\CrudActivo;

class AuditLabelsService
{
    private array $uniques = [];
    private array $currentLabels;
    private array $initialLabels;

    public function __construct(array $currentLabels, array $initialLabels)
    {
        //Etiquetas encontradas
        $this->currentLabels = $currentLabels;

        //Etiquetas iniciales
        $this->initialLabels = $initialLabels;
        $this->uniques = $this->removeDuplicates($this->currentLabels);
    }

    public function getResumen(): array
    {
        $result = $this->getAuditListDetailGroupByAuditStatus();

        return [
            'coincidentes' => count($result['coincidentes']),
            'faltantes'    => count($result['faltantes']),
            'sobrantes'    => count($result['sobrantes']),
        ];
    }

    public function getLabelAuditStatus(string $label)
    {
        $status = 'ninguno';

        if ($this->isSobrante($label)) {
            $status = 'sobrante';
        } else if ($this->isCoincidente($label)) {
            $status = 'coincidente';
        } else {
            $status = 'faltante';
        }

        return $status;
    }

    public function getAuditListDetail(): array
    {
        $labels = [];

        foreach ($this->initialLabels as $element) {
            if ($this->isCoincidente($element)) {
                $labels[] = ['etiqueta' => $element, 'audit_status' => CrudActivo::AUDIT_STATUS_COINCIDENTE];
            } else {
                $labels[] = ['etiqueta' => $element, 'audit_status' => CrudActivo::AUDIT_STATUS_FALTANTE];
            }
        }

        foreach ($this->uniques as $element) {
            if ($this->isSobrante($element)) {
                $labels[] = ['etiqueta' => $element, 'audit_status' => CrudActivo::AUDIT_STATUS_SOBRANTE];
            }
        }

        return $labels;
    }

    private function getAuditListDetailGroupByAuditStatus(): array
    {
        $coincidentes = [];
        $faltantes = [];
        $sobrantes = [];

        foreach ($this->initialLabels as $element) {
            if ($this->isCoincidente($element)) {
                $coincidentes[] = $element;
            } else {
                $faltantes[] = $element;
            }
        }

        foreach ($this->uniques as $element) {
            if ($this->isSobrante($element)) {
                $sobrantes[] = $element;
            }
        }

        return [
            'coincidentes' => $coincidentes,
            'faltantes'    => $faltantes,
            'sobrantes'    => $sobrantes,
        ];
    }

    private function isCoincidente(string $element): bool
    {
        return in_array($element, $this->uniques, true);
    }

    private function isSobrante(string $element): bool
    {
        return !in_array($element, $this->initialLabels, true);
    }

    private function removeDuplicates(array $arr): array
    {
        $unique = [];

        foreach ($arr as $element) {
            if (!in_array($element, $unique, true)) {
                $unique[] = $element;
            }
        }

        return $unique;
    }
}
