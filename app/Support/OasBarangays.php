<?php

namespace App\Support;

class OasBarangays
{
    /**
     * Official barangays of the Municipality of Oas, Albay (PSA / PSGC).
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            'Badbad',
            'Badian',
            'Bagsa',
            'Bagumbayan',
            'Balogo',
            'Banao',
            'Bangiawon',
            'Bogtong',
            'Bongoran',
            'Busac',
            'Cadawag',
            'Cagmanaba',
            'Calaguimit',
            'Calpi',
            'Calzada',
            'Camagong',
            'Casinagan',
            'Centro Poblacion',
            'Coliat',
            'Del Rosario',
            'Gumabao',
            'Ilaor Norte',
            'Ilaor Sur',
            'Iraya Norte',
            'Iraya Sur',
            'Manga',
            'Maporong',
            'Maramba',
            'Matambo',
            'Mayag',
            'Mayao',
            'Moroponros',
            'Nagas',
            'Obaliw-Rinas',
            'Pistola',
            'Ramay',
            'Rizal (Rabak)',
            'Saban',
            'San Agustin',
            'San Antonio (Linintian)',
            'San Isidro (Tabuguk)',
            'San Jose (Badongay)',
            'San Juan',
            'San Miguel (Mangayaw)',
            'San Pascual (Nale)',
            'San Ramon',
            'San Vicente (Suca)',
            'Tablon',
            'Talisay',
            'Talongog',
            'Tapel',
            'Tobgon',
            'Tobog',
        ];
    }

    public static function label(string $name): string
    {
        return 'Brgy. '.$name.', Oas, Albay';
    }

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_map(fn (string $name) => static::label($name), static::names());
    }

    /**
     * @return list<array{name: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (string $name) => [
            'name'  => $name,
            'label' => static::label($name),
        ], static::names());
    }
}
