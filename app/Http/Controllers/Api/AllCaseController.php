<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AllCaseRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AllCaseController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AllCaseRequest $request)
    {
        $validated = $request->validated();

        $case = $request->user()->cases()->create([
            'issue_type' => $validated['issue_type'],
            'location_city' => $validated['location_city'] ?? null,
            'location_state' => $validated['location_state'],
            'location_country' => $validated['location_country'],
            'situation_description' => $validated['situation_description'],
            'status' => 'active'
        ]);

        if ($case) {
            return $this->successResponse(
                $case,
                'Case created successfully',
                201
            );
        }
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $case = auth()->user()->cases()->find($id);

        if ($case) {
            return $this->successResponse(
                $case,
                'Case found successfully',
                200
            );
        }

        return $this->errorResponse(
            'Case not found',
            404
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
