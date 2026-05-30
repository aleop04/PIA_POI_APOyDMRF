<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LocationSearch
{
    public static function termsFromQuery(string $q): Collection
    {
        $locationParts = collect(explode(',', $q))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->reject(fn ($part) => self::isCountryTerm($part))
            ->values();

        if ($locationParts->isEmpty()) {
            return collect();
        }

        $isStateOnlySearch = $locationParts->count() === 1
            && count(self::stateEquivalents($locationParts->first())) > 0;

        if ($isStateOnlySearch) {
            return collect(self::stateEquivalents($locationParts->first()))
                ->filter()
                ->unique()
                ->values();
        }

        $firstPart = $locationParts->first();

        return collect([$firstPart])
            ->filter()
            ->unique()
            ->values();
    }

    private static function isCountryTerm(string $value): bool
    {
        $key = self::normalizeLocationKey($value);

        return in_array($key, [
            'mexico',
            'mx',
        ], true);
    }

    private static function normalizeLocationKey(string $value): string
    {
        $value = Str::ascii($value);
        $value = mb_strtolower($value);
        $value = str_replace('.', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private static function stateEquivalents(?string $state): array
    {
        if (empty($state)) {
            return [];
        }

        $key = self::normalizeLocationKey($state);

        $states = [
            'aguascalientes' => ['Aguascalientes', 'Ags.', 'AGS'],
            'ags' => ['Aguascalientes', 'Ags.', 'AGS'],

            'baja california' => ['Baja California', 'B.C.', 'BC'],
            'bc' => ['Baja California', 'B.C.', 'BC'],

            'baja california sur' => ['Baja California Sur', 'B.C.S.', 'BCS'],
            'bcs' => ['Baja California Sur', 'B.C.S.', 'BCS'],

            'campeche' => ['Campeche', 'Camp.'],
            'camp' => ['Campeche', 'Camp.'],

            'chiapas' => ['Chiapas', 'Chis.'],
            'chis' => ['Chiapas', 'Chis.'],

            'chihuahua' => ['Chihuahua', 'Chih.'],
            'chih' => ['Chihuahua', 'Chih.'],

            'ciudad de mexico' => ['Ciudad de México', 'CDMX', 'Cd. de México'],
            'cdmx' => ['Ciudad de México', 'CDMX', 'Cd. de México'],

            'coahuila' => ['Coahuila', 'Coah.'],
            'coah' => ['Coahuila', 'Coah.'],

            'colima' => ['Colima', 'Col.'],
            'col' => ['Colima', 'Col.'],

            'durango' => ['Durango', 'Dgo.'],
            'dgo' => ['Durango', 'Dgo.'],

            'guanajuato' => ['Guanajuato', 'Gto.'],
            'gto' => ['Guanajuato', 'Gto.'],

            'guerrero' => ['Guerrero', 'Gro.'],
            'gro' => ['Guerrero', 'Gro.'],

            'hidalgo' => ['Hidalgo', 'Hgo.'],
            'hgo' => ['Hidalgo', 'Hgo.'],

            'jalisco' => ['Jalisco', 'Jal.'],
            'jal' => ['Jalisco', 'Jal.'],

            'estado de mexico' => ['Estado de México', 'Edo. Méx.', 'Edomex', 'Méx.'],
            'edomex' => ['Estado de México', 'Edo. Méx.', 'Edomex', 'Méx.'],

            'michoacan' => ['Michoacán', 'Michoacan', 'Mich.'],
            'michoacan de ocampo' => ['Michoacán', 'Michoacan', 'Mich.'],
            'mich' => ['Michoacán', 'Michoacan', 'Mich.'],

            'morelos' => ['Morelos', 'Mor.'],
            'mor' => ['Morelos', 'Mor.'],

            'nayarit' => ['Nayarit', 'Nay.'],
            'nay' => ['Nayarit', 'Nay.'],

            'nuevo leon' => ['Nuevo León', 'Nuevo Leon', 'N.L.', 'NL'],
            'nl' => ['Nuevo León', 'Nuevo Leon', 'N.L.', 'NL'],

            'oaxaca' => ['Oaxaca', 'Oax.'],
            'oax' => ['Oaxaca', 'Oax.'],

            'puebla' => ['Puebla', 'Pue.'],
            'pue' => ['Puebla', 'Pue.'],

            'queretaro' => ['Querétaro', 'Queretaro', 'Qro.'],
            'qro' => ['Querétaro', 'Queretaro', 'Qro.'],

            'quintana roo' => ['Quintana Roo', 'Q.R.', 'Q Roo'],
            'qr' => ['Quintana Roo', 'Q.R.', 'Q Roo'],

            'san luis potosi' => ['San Luis Potosí', 'San Luis Potosi', 'S.L.P.', 'SLP'],
            'slp' => ['San Luis Potosí', 'San Luis Potosi', 'S.L.P.', 'SLP'],

            'sinaloa' => ['Sinaloa', 'Sin.'],
            'sin' => ['Sinaloa', 'Sin.'],

            'sonora' => ['Sonora', 'Son.'],
            'son' => ['Sonora', 'Son.'],

            'tabasco' => ['Tabasco', 'Tab.'],
            'tab' => ['Tabasco', 'Tab.'],

            'tamaulipas' => ['Tamaulipas', 'Tamps.', 'Tamp.'],
            'tamps' => ['Tamaulipas', 'Tamps.', 'Tamp.'],
            'tamp' => ['Tamaulipas', 'Tamps.', 'Tamp.'],

            'tlaxcala' => ['Tlaxcala', 'Tlax.'],
            'tlax' => ['Tlaxcala', 'Tlax.'],

            'veracruz' => ['Veracruz', 'Ver.'],
            'veracruz de ignacio de la llave' => ['Veracruz', 'Ver.'],
            'ver' => ['Veracruz', 'Ver.'],

            'yucatan' => ['Yucatán', 'Yucatan', 'Yuc.'],
            'yuc' => ['Yucatán', 'Yucatan', 'Yuc.'],

            'zacatecas' => ['Zacatecas', 'Zac.'],
            'zac' => ['Zacatecas', 'Zac.'],
        ];

        return $states[$key] ?? [];
    }
}