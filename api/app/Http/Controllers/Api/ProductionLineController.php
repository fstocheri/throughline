<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductionLineRequest;
use App\Http\Requests\UpdateProductionLineRequest;
use App\Http\Resources\ProductionLineResource;
use App\Models\ProductionLine;

class ProductionLineController extends Controller
{
    public function index()
    {
        return ProductionLineResource::collection(ProductionLine::orderBy('code')->get());
    }

    public function store(StoreProductionLineRequest $request)
    {
        $line = ProductionLine::create($request->validated());

        return new ProductionLineResource($line);
    }

    public function update(UpdateProductionLineRequest $request, ProductionLine $productionLine)
    {
        $productionLine->update($request->validated());

        return new ProductionLineResource($productionLine);
    }

    public function destroy(ProductionLine $productionLine)
    {
        $productionLine->delete();

        return response()->noContent();
    }
}
