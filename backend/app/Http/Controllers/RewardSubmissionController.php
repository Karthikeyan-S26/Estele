<?php

namespace App\Http\Controllers;

use App\Mail\NewRewardSubmissionNotification;
use App\Models\Order;
use App\Models\RewardSubmission;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class RewardSubmissionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $eligibleOrders = $user->orders()
            ->where('status', 'delivered')
            ->whereDoesntHave('rewardSubmission')
            ->get();

        $submissions = $user->rewardSubmissions()->with('order')->get();

        return view('account.rewards', [
            'eligibleOrders' => $eligibleOrders,
            'submissions' => $submissions,
            'walletBalance' => $user->wallet_balance,
            'walletTransactions' => $user->walletTransactions()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'order_id' => ['required', 'integer'],
            // Server-side validation is the real security boundary — the
            // frontend's accept/size checks are UX only and are never trusted.
            'image' => ['required', 'image', 'max:3072'],
            'video' => ['required', 'mimes:mp4,webm', 'max:10240'],
        ]);

        $order = $user->orders()->find($validated['order_id']);
        abort_if(! $order, 404);
        abort_unless($order->status === 'delivered', 422, 'Only delivered orders are eligible for a reward submission.');
        abort_if(RewardSubmission::where('order_id', $order->id)->exists(), 422, 'A submission already exists for this order.');

        // The exists() check above is a fast-path UX check, not the real
        // guard: a concurrent request for the same order can pass it before
        // either insert lands. `reward_submissions.order_id` has a DB-level
        // unique constraint that is the real backstop — this catch converts
        // that race's QueryException into the same clean 422 the pre-check
        // gives, instead of an uncaught 500.
        try {
            $submission = RewardSubmission::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'status' => 'pending',
            ]);
        } catch (QueryException $e) {
            $isUniqueViolation = str_contains($e->getMessage(), 'order_id')
                && (str_contains($e->getMessage(), 'UNIQUE constraint failed')
                    || str_contains($e->getMessage(), 'Duplicate entry')
                    || str_contains($e->getMessage(), 'unique constraint'));

            abort_if($isUniqueViolation, 422, 'A submission already exists for this order.');

            throw $e;
        }

        $submission->addMedia($request->file('image'))->toMediaCollection('image');
        $submission->addMedia($request->file('video'))->toMediaCollection('video');

        // Sent after the media/DB writes commit, same "outside the write
        // path" placement WalletService::credit() uses for its own
        // notification — a mail failure here must never roll back or block
        // the submission itself. 'marketing' is who reviews reward
        // submissions (see ShieldSeeder) — NOT 'vendor', which is the
        // old-jewellery bidding marketplace contact and has nothing to do
        // with this.
        $reviewers = User::role(['marketing', 'super_admin'])->whereNotNull('email')->get();
        foreach ($reviewers as $reviewer) {
            Mail::to($reviewer->email)->queue(new NewRewardSubmissionNotification($submission->fresh(['user', 'order'])));
        }

        return redirect()->route('account.rewards.index')
            ->with('success', 'Thanks! Your submission is pending review.');
    }
}
