<?php

namespace App\Http\Controllers\Apps;

use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UsersDataTable $dataTable)
    {
        return $dataTable->render('pages/apps.user-management.users.list');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function show(User $user)
    {
        return view('pages/apps.user-management.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    /**
     * Show the user's profile page.
     */
    public function profile(Request $request)
    {
        return view('pages/apps.user-management.users.profile', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Show the user's settings page.
     */
    public function settings(Request $request)
    {
        return view('pages/apps.user-management.users.settings', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's settings.
     */
    public function updateSettings(Request $request)
    {
        // Validate and update user settings here
        // Example: $request->user()->update($request->only(['name', 'email']));
        return redirect()->route('account.settings')->with('status', 'Settings updated!');
    }
}
