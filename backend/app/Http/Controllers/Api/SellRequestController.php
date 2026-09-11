<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponses;
use App\Models\SellRequest;
use App\Services\SellRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SellRequestController
{
    use ApiResponses;

    public const ITEM_TYPES = ['ring', 'chain', 'necklace', 'earrings', 'bracelet', 'other'];

    private const IMAGE_MAX_BYTES = 3 * 1024 * 1024;

    private const VIDEO_MAX_BYTES = 20 * 1024 * 1024;

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const VIDEO_MIMES = ['video/mp4', 'video/webm'];

    public function __construct(private readonly SellRequestService $service) {}

    public function index(Request $request): JsonResponse
    {
        $requests = $request->user()
            ->sellRequests()
            ->withCount('activeBids')
            ->latest()
            ->get();

        return $this->ok($requests->map(fn (SellRequest $s) => $this->payload($s))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => ['required', 'string', 'in:'.implode(',', self::ITEM_TYPES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'digits_between:10,15'],
            'image' => ['nullable', 'array'],
            'image.mime' => ['nullable', 'string'],
            'image.data' => ['nullable', 'string'],
            'video' => ['required', 'array'],
            'video.mime' => ['required', 'string'],
            'video.data' => ['required', 'string'],
        ]);

        $image = $this->decodeOptionalMedia($request->input('image'), self::IMAGE_MIMES, self::IMAGE_MAX_BYTES, 'image');
        $video = $this->decodeMedia($request->input('video'), self::VIDEO_MIMES, self::VIDEO_MAX_BYTES, 'video');

        $sell = $this->service->createForCustomer(
            $request->user(),
            $validated,
            $image['data'],
            $image['mime'],
            $video['data'],
            $video['mime'],
        );

        return $this->created($this->payload($sell));
    }

    public function show(Request $request, string $requestNumber): JsonResponse
    {
        $sell = SellRequest::where('request_number', $requestNumber)->firstOrFail();

        abort_unless($sell->user_id === $request->user()->id, 404);

        return $this->ok($this->payload($sell));
    }

    public function cancel(Request $request, string $requestNumber): JsonResponse
    {
        $sell = SellRequest::where('request_number', $requestNumber)->firstOrFail();
        abort_unless($sell->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if (! $this->service->customerCancel($sell, $request->user(), $validated['reason'])) {
            return $this->error('This request can no longer be cancelled.', 422);
        }

        return $this->ok($this->payload($sell->fresh()));
    }

    /* ---------------------------------------------------------------- */

    private function payload(SellRequest $sell): array
    {
        return [
            'id' => $sell->id,
            'request_number' => $sell->request_number,
            'item_type' => $sell->item_type,
            'description' => $sell->description,
            'city' => $sell->city,
            'contact_phone' => $sell->contact_phone,
            'status' => $sell->status,
            'image_url' => $sell->image_path ? Storage::disk('public')->url($sell->image_path) : null,
            'video_url' => $sell->video_path ? Storage::disk('public')->url($sell->video_path) : null,
            'bids_start_at' => $sell->bids_start_at?->toIso8601String(),
            'bids_end_at' => $sell->bids_end_at?->toIso8601String(),
            'bidding_open' => $sell->isBiddingOpen(),
            'bid_count' => (int) $sell->active_bids_count ?: (int) $sell->activeBids()->count(),
            'highest_bid_amount' => $sell->highest_bid_amount !== null ? (float) $sell->highest_bid_amount : ($sell->activeBids()->max('amount') !== null ? (float) $sell->activeBids()->max('amount') : null),
            'admin_valuation' => $sell->admin_valuation !== null ? (float) $sell->admin_valuation : null,
            'deduction_amount' => $sell->deduction_amount !== null ? (float) $sell->deduction_amount : null,
            'wallet_credit' => $sell->wallet_credit !== null ? (float) $sell->wallet_credit : null,
            'settlement_at' => $sell->settlement_at?->toIso8601String(),
            'result_selected_at' => $sell->result_selected_at?->toIso8601String(),
            'cancelled_by' => $sell->cancelled_by,
            'cancel_reason' => $sell->cancel_reason,
            'created_at' => $sell->created_at?->toIso8601String(),
            'timeline' => $sell->auditLogs()->orderBy('created_at')->get()->map(fn ($log) => [
                'event' => $log->event,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'metadata' => $log->metadata,
                'at' => $log->created_at?->toIso8601String(),
            ])->values(),
        ];
    }

    private function decodeMedia(?array $media, array $allowedMimes, int $maxBytes, string $field): array
    {
        if (! $media || blank($media['data'] ?? null)) {
            return ['data' => null, 'mime' => null];
        }

        $mime = strtolower((string) ($media['mime'] ?? ''));
        if (! in_array($mime, $allowedMimes, true)) {
            abort(422, trim(strtoupper($field)).' must be a supported file type.');
        }

        $data = $media['data'];
        if (preg_match('/^data:[\w\/\-\.\+]+;base64,/', $data)) {
            $data = preg_replace('/^data:[\w\/\-\.\+]+;base64,/', '', $data);
        }

        $decoded = base64_decode((string) $data, true);
        if ($decoded === false) {
            abort(422, 'Could not decode the '.$field.' file.');
        }

        if (strlen($decoded) > $maxBytes) {
            abort(422, 'The '.$field.' file is too large.');
        }

        return ['data' => base64_encode($decoded), 'mime' => $mime];
    }

    private function decodeOptionalMedia(?array $media, array $allowedMimes, int $maxBytes, string $field): array
    {
        return $this->decodeMedia($media, $allowedMimes, $maxBytes, $field);
    }
}