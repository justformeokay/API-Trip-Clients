<?php

declare(strict_types=1);

namespace App\Validators;

final class SearchValidator extends BaseValidator
{
    public function validateSearch(array $data): void
    {
        $this->validate($data, [
            'keyword'        => 'nullable|string|max:200',
            'destination'    => 'nullable|string|max:200',
            'category_id'    => 'nullable|integer',
            'destination_id' => 'nullable|integer',
            'departure_date' => 'nullable|date',
            'departure_from' => 'nullable|date',
            'departure_to'   => 'nullable|date',
            'price_min'      => 'nullable|numeric|min:0',
            'price_max'      => 'nullable|numeric|min:0',
            'min_slots'      => 'nullable|integer|min:1',
            'latitude'       => 'nullable|latitude',
            'longitude'      => 'nullable|longitude',
            'radius_km'      => 'nullable|numeric|min:1|max:500',
            'page'           => 'nullable|integer|min:1',
            'limit'          => 'nullable|integer|min:1|max:100',
        ]);
    }
}
