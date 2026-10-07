<?php

namespace App\Http\Controllers;

use App\Http\Resources\NoticeResource;
use App\Models\Notice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        return Inertia::render('Notices/Index', [
            'notices' => NoticeResource::collection(
                Notice::with(['readers' => fn ($q) => $q->where('users.id', $userId)])->latest()->get()
            ),
        ]);
    }

    public function read(Request $request, Notice $notice)
    {
        $notice->readers()->syncWithoutDetaching([$request->user()->id]);

        return back();
    }
}
