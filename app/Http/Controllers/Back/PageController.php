<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function dependencies()
    {
        return view('back.developer.dependencies');
    }

    public function session()
    {
        return view('back.developer.session');
    }

    public function test()
    {
        return view('back.developer.test');
    }

    // --------------------------------------------------------------------------------

    public function people()
    {
        return view('back.developer.people');
    }

    public function teams()
    {
        return view('back.developer.teams');
    }

    public function users()
    {
        return view('back.developer.users');
    }
}
