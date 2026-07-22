<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DateRangeRequest;
use App\Http\Resources\V1\ConsumptionResource;
use App\Repositories\Contracts\ConsumptionRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ConsumptionController extends Controller
{
    public function __construct(
        private readonly ConsumptionRepositoryInterface $consumptions,
    ) {}

    public function index(DateRangeRequest $request): AnonymousResourceCollection
    {
        $paginator = $this->consumptions->paginate(
            $request->getStartDate(),
            $request->getEndDate(),
            $request->getPerPage(),
        );

        return ConsumptionResource::collection($paginator);
    }
}
