<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Return an impersonating admin to their own session. The admin's id is
 * stashed in the session when impersonation starts; here we drop the customer
 * session and explicitly restore the admin guard from that stored id.
 */
class ImpersonationController extends Controller
{
    public function stop(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_admin_id');

        // Leave the customer session.
        Auth::guard('web')->logout();

        // Explicitly log the admin back in on the admin guard — don't assume it
        // survived, so the return is reliable regardless of guard behaviour.
        if ($adminId && Auth::guard('admin')->loginUsingId($adminId)) {
            return redirect('/admin/customers')->with('status', __('Returned to admin.'));
        }

        // No stored admin — fall back to the admin login.
        return redirect('/admin/login');
    }
}