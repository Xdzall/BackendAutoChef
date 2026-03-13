<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name_unit' => 'required|string|unique:unit,name_unit',
            'abbreviation' => 'required|string|max:10',
            'type_unit' => ['required', Rule::in(['weight', 'volume', 'quantity'])], 
            'conversion_to_grams' => 'nullable|numeric',
        ]);
        $unit = Unit::create($request->all());

        return response()->json(['message' => 'Unit berhasil ditambahkan', 'data' => $unit], 201);
    }

    public function getUnits()
    {
        return response()->json(Unit::all());
    }
}