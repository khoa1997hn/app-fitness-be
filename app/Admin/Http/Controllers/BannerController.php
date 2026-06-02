<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\StoreBannerRequest;
use App\Admin\Http\Requests\UpdateBannerRequest;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\Banner;

class BannerController extends BaseController
{
    public function index()
    {
        $banners = Banner::query()
            ->with('translations')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(StoreBannerRequest $request)
    {
        $validated = $request->validated();

        $banner = new Banner;
        $banner->description = $validated['description'] ?? null;
        $this->fillTranslations($banner, $validated);
        $banner->save();

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Tạo banner thành công.');
    }

    public function edit(Banner $banner)
    {
        $banner->load('translations');

        return view('admin.banners.edit', compact('banner'));
    }

    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        $validated = $request->validated();

        $banner->description = $validated['description'] ?? null;
        $this->fillTranslations($banner, $validated);
        $banner->save();

        return redirect()
            ->route('admin.banners.edit', $banner)
            ->with('success', 'Cập nhật banner thành công.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillTranslations(Banner $banner, array $validated): void
    {
        $requiredLocale = (string) config('translatable.fallback_locale');

        foreach ((array) config('translatable.locales') as $locale) {
            $data = $validated['translations'][$locale] ?? null;
            $imagePath = $data['image']['path'] ?? null;

            // Skip non-required locale if image is missing (image is NOT NULL in DB)
            if ($locale !== $requiredLocale && empty($imagePath)) {
                continue;
            }

            $banner->fill([
                $locale => [
                    'image' => $data['image'] ?? null,
                    'link_url' => $data['link_url'] ?? null,
                    'order' => $data['order'] ?? 0,
                    'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                ],
            ]);
        }
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Xóa banner thành công.');
    }
}
