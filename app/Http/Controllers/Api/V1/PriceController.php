<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DateRangeRequest;
use App\Http\Resources\V1\PriceResource;
use App\Repositories\Contracts\PriceRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PriceController extends Controller
{
    public function __construct(
        private readonly PriceRepositoryInterface $prices,
    ) {}

    public function index(DateRangeRequest $request): AnonymousResourceCollection
    {
        $paginator = $this->prices->paginate(
            $request->getStartDate(),
            $request->getEndDate(),
            $request->getPerPage(),
        );

        return PriceResource::collection($paginator);
    }
}
