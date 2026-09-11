<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'popup_id' => ['nullable', 'integer', 'exists:popups,id'],
        ]);

        NewsletterSubscriber::firstOrCreate(
            ['email' => $validated['email']],
            [
                'source' => filled($validated['popup_id'] ?? null) ? 'popup' : 'footer',
                'popup_id' => $validated['popup_id'] ?? null,
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Subscribed.']);
        }

        return back()->with('success', 'Thanks for subscribing!');
    }
}
