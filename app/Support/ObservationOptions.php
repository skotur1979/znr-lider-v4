<?php

namespace App\Support;

class ObservationOptions
{
    public static function types(): array
    {
        return [
            'Near Miss' =>
                'Near Miss - Skoro nezgoda',

            'Negative Observation' =>
                'Negativno zapažanje',

            'Positive Observation' =>
                'Pozitivno zapažanje',
        ];
    }

    public static function publicTypes(): array
    {
        return [
            'Near Miss' =>
                'Near Miss - Skoro nezgoda',

            'Negative Observation' =>
                'Negativno zapažanje',
        ];
    }

    public static function priorities(): array
    {
        return [
            'low' =>
                'Nisko',

            'medium' =>
                'Srednje',

            'high' =>
                'Visoko',

            'critical' =>
                'Kritično',
        ];
    }

    public static function statuses(): array
    {
        return [
            'Not started' =>
                'Nije započeto',

            'In progress' =>
                'U tijeku',

            'Complete' =>
                'Završeno',
        ];
    }

    public static function hazards(): array
    {
        return [
            'Kontakt s pokretnim dijelovima strojeva',
            'Utapanje ili gušenje',
            'Izloženost struji',
            'Izloženost ekstremnim temperaturama',
            'Izloženost vatri',
            'Pad s visine',
            'Pad na istoj razini',
            'Udarac pokretnim vozilom',
            'Udarac pokretnim, letećim ili padajućim predmetom',
            'Udarac u nešto nepomično',
            'Ručno rukovanje, podizanje ili nošenje',
            'Profesionalna bolest/bolest',
            'Fizički napad',
            'Padovi, spoticanje ili pokliznuće',
            'Incident s trećom stranom',
            'Zarobljenost nečim što se ruši',
            'Ostalo',
            'Porezotine, ogrebotine ili abrazije',
            'Blokirana protupožarna oprema',
            'Blokirani evakuacijski putevi',
            'Nedostatak odgovarajuće rasvjete',
            'Nedostatak čistoće',
            'Nepravilno skladištenje',
        ];
    }
}