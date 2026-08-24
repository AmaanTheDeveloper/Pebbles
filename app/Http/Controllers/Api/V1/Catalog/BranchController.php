<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $branches = Branch::query()->catalog()->get();

        return BranchResource::collection($branches);
    }
}
