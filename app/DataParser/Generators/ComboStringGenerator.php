<?php

namespace App\DataParser\Generators;

use App\Models\LteComponent;
use App\Models\NrComponent;

class ComboStringGenerator
{
    public function getComboStringFromComponents(array $components): string
    {

        foreach ($components as $c) {
            if (!($c instanceof LteComponent || $c instanceof NrComponent)) {
                var_dump($c);
                throw new \InvalidArgumentException('Invalid array passed: one or more elements are not of instances of NrComponent or LteComponent');
            }
        }

        usort($components, function ($a, $b) {
            if ($a instanceof LteComponent && $b instanceof NrComponent) {
                return -1;
            }

            if ($a instanceof NrComponent && $b instanceof LteComponent) {
                return 1;
            }

            return $a->band <=> $b->band;
        });

        $comboStringComponents = [];

        /**
         * @var LteComponent|NrComponent $component
         */
        foreach ($components as $component) {

            $str = $component instanceof NrComponent ? 'n' : '';
            $str .= $component->band;

            if (isset($component->dl_class)) {
                $str .= $component->dl_class;
            }

            if ($component->dl_mimos()->count() > 0) {
                $str .= $component->dl_mimos()->max('mimo');
            }

            if (isset($component->ul_class)) {
                $str .= $component->ul_class;
            }

            if ($component->ul_mimos()->count() > 0) {
                $maxMimo = $component->ul_mimos()->max('mimo');

                if ($maxMimo > 1) {
                    $str .= $component->ul_mimos()->max('mimo');
                }
            }

            $comboStringComponents[] = $str;
        }

        $comboString = implode('-', $comboStringComponents);

        return $comboString;
    }
}
