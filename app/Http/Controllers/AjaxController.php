<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function ajaxUserLookup(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $limit = (int) $request->get('limit', 20);

        $users = User::query()
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'results' => $users->map(fn($u) => [
                'id'   => $u->id,
                'text' => "{$u->name} <{$u->email}>",
            ]),
        ]);
    }
}
