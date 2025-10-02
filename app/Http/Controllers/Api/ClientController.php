<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    use HelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = Client::select('id', 'nameAr', 'nameEn', 'email', 'phone', 'gender')->where('referred_type' , 'client')->get();
        return $this->returnDataArray($clients);
    }

    public function getinstitutions()
    {
        $clients = Client::select('id', 'nameAr', 'nameEn', 'email', 'phone', 'gender')->where('referred_type' , 'institution')->get();
        return $this->returnDataArray($clients);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'nameAr' => 'required|string|max:255',
            'nameEn' => 'required|string|max:255',
            'referred_type' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string|max:20|unique:clients,phone',
            'gender' => 'required|in:1,2' // 1: ذكر، 2: أنثى
        ]);

        $client = new Client();
        $client->nameAr = $validated['nameAr'];
        $client->nameEn = $validated['nameEn'];
        $client->email = $validated['email'];
        $client->phone = $validated['phone'];
        $client->referred_type = $validated['referred_type'];
        $client->gender = $validated['gender'];
        $client->password = bcrypt('password'); // ثابت دائماً

        $client->save();
        return $this->returnDataArray($client, __('messages.created_successfully'));
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
