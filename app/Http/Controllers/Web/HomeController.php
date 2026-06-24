<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PostService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        protected PostService $postService
    ) {}

    public function index(Request $request)
    {
        // Load initial feed to pass to blade
        $posts = $this->postService->getFeed($request->user());
        
        return view('web.home', compact('posts'));
    }
}
