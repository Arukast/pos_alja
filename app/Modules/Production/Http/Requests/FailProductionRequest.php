<?php

declare(strict_types=1);

namespace App\Modules\Production\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

final class FailProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && $this->user()?->can('fail', $order) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }
}
