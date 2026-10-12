<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Setting};
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** The QR poster for the gym door. Members scan it, sign in, and are checked in for the day. */
class CheckinController extends Controller
{
    public function poster()
    {
        $url = route('member.checkin', ['code' => Setting::checkinCode()]);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(320, 1), new SvgImageBackEnd())))->writeString($url);
        $svg = preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
        return view('admin.checkin', ['url' => $url, 'svg' => $svg, 'gym' => Setting::get('gym_name', 'FitnessWalkers')]);
    }

    public function renew()
    {
        Setting::checkinCode(true);
        AuditLog::record('Made a new check-in code');
        return back()->with('ok', 'New code made. Print the new poster and take down the old one.');
    }
}
