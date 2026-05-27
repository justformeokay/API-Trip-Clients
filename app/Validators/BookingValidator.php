<?php

declare(strict_types=1);

namespace App\Validators;

final class BookingValidator extends BaseValidator
{
    public function validateCreate(array $data): void
    {
        $this->validate($data, [
            'trip_id'      => 'required|integer',
            'participants' => 'required|integer|min:1|max:100',
        ]);
    }

    public function validateCancel(array $data): void
    {
        $this->validate($data, [
            'reason' => 'nullable|string|max:500',
        ]);
    }
}
