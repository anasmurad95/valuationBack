<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use HelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::select('id', 'nameAr', 'nameEn', 'email')->get();
        return $this->returnDataArray($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
