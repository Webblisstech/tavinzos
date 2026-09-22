<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * An admin managing their OWN account: profile (name, email) and password.
 * All actions target the currently-authenticated admin, never another.
 */
class AdminAccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.account.index', [
            'admin' => auth('admin')->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
        ]);

        DB::table('admins')->where('id', $admin->id)->update([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'updated_at' => now(),
        ]);

        return back()->with('status', __('Profile updated.'));
    }

    public function updatePassword(Request $request)
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Verify the current password against the stored hash.
        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => __('Your current password is incorrect.')]);
        }

        DB::table('admins')->where('id', $admin->id)->update([
            'password'   => Hash::make($data['password']),
            'updated_at' => now(),
        ]);

        return back()->with('status', __('Password changed.'));
    }
}