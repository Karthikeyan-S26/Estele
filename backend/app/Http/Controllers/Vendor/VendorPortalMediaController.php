<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\OldJewelleryRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a request's original photo/video to the vendor that was invited
 * to it, scoped by session (EnsureVendorAccess) rather than the signed URL
 * the no-login token flow uses (App\Http\Controllers\VendorMediaController)
 * — same private-disk file, different way of proving the viewer is allowed
 * to see it.
 */
class VendorPortalMediaController extends Controller
{
    public function image(Request $request, OldJewelleryRequest $oldJewelleryRequest): StreamedResponse
    {
        return $this->stream($oldJewelleryRequest, 'image');
    }

    public function video(Request $request, OldJewelleryRequest $oldJewelleryRequest): StreamedResponse
    {
        return $this->stream($oldJewelleryRequest, 'video');
    }

    private function stream(OldJewelleryRequest $oldJewelleryRequest, string $collection): StreamedResponse
    {
        $vendor = Vendor::current();

        abort_unless(
            $vendor && $vendor->invitations()->where('old_jewellery_request_id', $oldJewelleryRequest->id)->exists(),
            403,
        );

        $media = $oldJewelleryRequest->getFirstMedia($collection);
        abort_unless($media, 404);

        return response()->stream(function () use ($media) {
            fpassthru($media->stream());
        }, 200, [
            'Content-Type' => $media->mime_type,
            'Content-Length' => $media->size,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
