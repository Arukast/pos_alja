<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Models\Attribute;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attribute */
final class AttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'price' => Decimal::money($this->price),
            'is_active' => (bool) $this->is_active,
            'branch_id' => $this->branch_id,
            'branch' => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ] : null,
        ];

        $actor = $request->user();

        if ($actor instanceof User && in_array($actor->role, ['owner', 'manager'], true)) {
            $data['cost'] = Decimal::money($this->cost);
        }

        return $data;
    }
}
