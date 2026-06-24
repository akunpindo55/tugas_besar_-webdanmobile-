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
        $paginator = $this->postService->getFeed($request->user());
        $posts = $paginator->items();
        $paginator->appends(['page' => $paginator->currentPage()]);

        return view('web.home', [
            'posts' => $posts,
            'postsJson' => $posts,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function feed(Request $request)
    {
        $paginator = $this->postService->getFeed($request->user());

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
