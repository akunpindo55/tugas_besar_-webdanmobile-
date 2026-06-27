<?php

namespace App\Http\Controllers\Web;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumTopic;
use App\Services\ForumService;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function __construct(
        protected ForumService $forumService
    ) {}

    public function index(Request $request)
    {
        $forums = $this->forumService->listPublicForums();
        return view('web.forum.index', compact('forums'));
    }

    public function myForums(Request $request)
    {
        $forums = $this->forumService->listUserForums($request->user());
        return view('web.forum.my', compact('forums'));
    }

    public function create()
    {
        return view('web.forum.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_private' => 'boolean',
        ]);

        try {
            $forum = $this->forumService->createForum($request->user(), $validated);
            return redirect()->route('forums.show', $forum->id)->with('success', 'Forum berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Request $request, $id)
    {
        $forum = Forum::with(['members', 'creator'])->findOrFail($id);
        $isMember = $request->user()->forums()->where('forum_id', $id)->exists();
        $userRole = $isMember ? $request->user()->forums()->where('forum_id', $id)->first()?->pivot?->role : null;
        $topics = $forum->topics()->with(['user'])->latest()->get();
        return view('web.forum.show', compact('forum', 'topics', 'isMember', 'userRole'));
    }

    public function join(Request $request, $id)
    {
        try {
            $this->forumService->joinForum($request->user(), $id);
            return redirect()->route('forums.show', $id)->with('success', 'Berhasil bergabung ke forum.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function leave(Request $request, $id)
    {
        try {
            $this->forumService->leaveForum($request->user(), $id);
            return redirect()->route('forums.index')->with('success', 'Berhasil meninggalkan forum.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function invite(Request $request, $id)
    {
        $validated = $request->validate([
            'invited_user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $this->forumService->inviteToForum($request->user(), $id, $validated['invited_user_id']);
            return back()->with('success', 'Undangan berhasil dikirim.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function kick(Request $request, $id, $memberId)
    {
        try {
            $this->forumService->kickMember($request->user(), $id, $memberId);
            return back()->with('success', 'Anggota berhasil dikeluarkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function createTopic(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'content' => 'required|string',
            'media' => 'nullable|array|max:5',
            'media.*' => 'file|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        try {
            $mediaData = [];
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $url = StorageHelper::storeFile($file, 'forum-topics');
                    if ($url) {
                        $mediaData[] = [
                            'file_url' => $url,
                            'media_type' => 'image',
                        ];
                    }
                }
            }

            $topic = $this->forumService->createTopic($request->user(), $id, $validated, $mediaData);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['data' => $topic, 'redirect' => route('topics.show', $topic->id)]);
            }

            return redirect()->route('topics.show', $topic->id)->with('success', 'Topik berhasil dibuat.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function showTopic(Request $request, $id)
    {
        $topic = ForumTopic::with(['forum', 'user', 'comments' => function ($q) {
            $q->whereNull('parent_comment_id')->with(['user', 'replies.user']);
        }])->findOrFail($id);

        $isMember = $request->user()->forums()->where('forum_id', $topic->forum_id)->exists();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['data' => $topic->load(['user', 'comments.user', 'comments.replies.user'])]);
        }

        return view('web.forum.topic', [
            'topic' => $topic,
            'isMember' => $isMember,
            'topicJson' => $topic->toArray(),
        ]);
    }

    public function replyTopic(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'parent_comment_id' => 'nullable|integer|exists:forum_comments,id',
            'media' => 'nullable|array|max:5',
            'media.*' => 'file|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        try {
            $mediaData = [];
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    $url = StorageHelper::storeFile($file, 'forum-comments');
                    if ($url) {
                        $mediaData[] = [
                            'file_url' => $url,
                            'media_type' => 'image',
                        ];
                    }
                }
            }

            $comment = $this->forumService->replyTopic($request->user(), $id, $validated, $mediaData);
            $comment->load('user');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['data' => $comment]);
            }

            return back()->with('success', 'Komentar berhasil ditambahkan.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroyComment(Request $request, $id, $commentId)
    {
        try {
            $this->forumService->deleteTopicComment($request->user(), $commentId);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyTopic(Request $request, $id, $topicId)
    {
        try {
            $this->forumService->deleteTopic($request->user(), $topicId);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function respondInvitation(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:accepted,declined',
        ]);

        try {
            $this->forumService->respondToInvitation($request->user(), $id, $validated['status']);
            return redirect()->route('forums.my')->with('success', 'Undangan berhasil diproses.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
