<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage; // << เพิ่มบรรทัดนี้
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $req)
    {
        $filters = $req->only(['q', 'is_active', 'from', 'to', 'category']);

        $rs = News::query()
            ->when(($filters['q'] ?? null), function ($q, $kw) {
                $q->where(function ($w) use ($kw) {
                    $w->where('title', 'like', "%{$kw}%")
                        ->orWhere('excerpt', 'like', "%{$kw}%")
                        ->orWhere('body', 'like', "%{$kw}%");
                });
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn($q) =>
                $q->where('is_active', (bool) $filters['is_active'])
            )
            ->when(($filters['category'] ?? null), fn($q, $ct) => $q->where('category', $ct))
            ->when(($filters['from'] ?? null) && ($filters['to'] ?? null), function ($q) use ($filters) {
                $q->whereBetween('created_at', [
                    $filters['from'] . ' 00:00:00',
                    $filters['to'] . ' 23:59:59',
                ]);
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('backend.news.index', [
            'rs'      => $rs,
            'filters' => $filters,
            'q'       => $filters['q'] ?? null,
        ]);
    }

    public function create()
    {
        return view('backend.news.create', ['news' => new News()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'excerpt'   => ['nullable', 'string', 'max:500'],
            'body'      => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $data['slug'] = $this->makeUniqueSlug($data['title']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('uploads/news', 'public');
        }

        News::create($data);

        flash_notify('เพิ่มข่าวสำเร็จ', 'success');
        return redirect()->route('backend.news.index');
    }

    public function edit(News $news)
    {
        return view('backend.news.edit', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $data = $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'excerpt'   => ['nullable', 'string', 'max:500'],
            'body'      => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $data['slug'] = $this->makeUniqueSlug($data['title'], $news->id);

        if ($request->hasFile('image')) {
            if ($news->image_path) {
                Storage::disk('public')->delete($news->image_path);
            }
            $data['image_path'] = $request->file('image')->store('uploads/news', 'public');
        }

        $news->update($data);

        flash_notify('แก้ไขข่าวสำเร็จ', 'success');
        return redirect()->route('backend.news.index');
    }

    public function destroy(News $news)
    {
        if ($news->image_path) {
            Storage::disk('public')->delete($news->image_path);
        }
        $news->delete();

        flash_notify('ลบข่าวสำเร็จ', 'success');
        return back();
    }

    public function toggle(News $news)
    {
        $news->update(['is_active' => !$news->is_active]);
        flash_notify('อัปเดตสถานะข่าวสำเร็จ', 'success');
        return back();
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i    = 1;

        while (
            News::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->when(
                Schema::hasColumn('news', 'deleted_at'), // << เปลี่ยนเป็น Schema::
                fn($q) => $q->whereNull('deleted_at')
            )
            ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
