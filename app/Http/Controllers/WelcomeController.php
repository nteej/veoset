<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function index()
    {
        $sites = Site::with(['assets' => function ($query) {
            $query->where('is_active', true)
                  ->select('id', 'site_id', 'name', 'asset_type', 'status', 'manufacturer', 'model');
        }])
        ->where('is_active', true)
        ->get(['id', 'name', 'description', 'location', 'latitude', 'longitude']);

        return view('welcome', compact('sites'));
    }

    public function siteAssets($siteId)
    {
        $site = Site::with(['assets' => function ($query) {
            $query->where('is_active', true)
                  ->select('id', 'site_id', 'name', 'asset_type', 'status', 'manufacturer', 'model', 'installation_date');
        }])
        ->where('is_active', true)
        ->findOrFail($siteId, ['id', 'name', 'description', 'location']);

        return response()->json([
            'site' => $site,
            'assets' => $site->assets
        ]);
    }
}