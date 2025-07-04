<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailTrackingController extends Controller
{
    public function trackOpen($id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $log = EmailLog::where("id", $id)->first();

        if($log && !$log->opened_at) {
            $log->opened_at = now();
            $log->save();
        }

        return response()->file(public_path('images/pixel.jpg'), ['Content-type' => 'image/png']);
    }
}
