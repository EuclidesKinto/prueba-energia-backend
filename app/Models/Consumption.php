<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasHourlySlots;
use Database\Factories\ConsumptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property float|null $h1
 * @property float|null $h2
 * @property float|null $h3
 * @property float|null $h4
 * @property float|null $h5
 * @property float|null $h6
 * @property float|null $h7
 * @property float|null $h8
 * @property float|null $h9
 * @property float|null $h10
 * @property float|null $h11
 * @property float|null $h12
 * @property float|null $h13
 * @property float|null $h14
 * @property float|null $h15
 * @property float|null $h16
 * @property float|null $h17
 * @property float|null $h18
 * @property float|null $h19
 * @property float|null $h20
 * @property float|null $h21
 * @property float|null $h22
 * @property float|null $h23
 * @property float|null $h24
 * @property float|null $h25
 */
class Consumption extends Model
{
    /** @use HasFactory<ConsumptionFactory> */
    use HasFactory;

    use HasHourlySlots;

    protected $fillable = [
        'date',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7', 'h8', 'h9', 'h10',
        'h11', 'h12', 'h13', 'h14', 'h15', 'h16', 'h17', 'h18', 'h19', 'h20',
        'h21', 'h22', 'h23', 'h24', 'h25',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];
}
