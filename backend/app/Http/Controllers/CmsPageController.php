<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;

class CmsPageController extends Controller
{
    public function show(CmsPage $cmsPage)
    {
        abort_unless($cmsPage->status === 'published', 404);

        return view('pages.show', ['page' => $cmsPage]);
    }
}
