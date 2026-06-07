<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\StoreComboRequest;
use App\Admin\Http\Requests\UpdateComboRequest;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\Combo;
use App\Share\Models\ComboInfo;
use App\Share\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ComboController extends BaseController
{
    public function index(): View
    {
        $combos = Combo::query()
            ->with(['translations', 'programs'])
            ->withCount('programs')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.combos.index', compact('combos'));
    }

    public function create(): View
    {
        $programs = Program::query()->withTranslation()->orderByTranslation('sort')->get();

        return view('admin.combos.create', compact('programs'));
    }

    public function store(StoreComboRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $combo = new Combo;
            $this->fillTranslations($combo, $validated);
            $combo->save();

            $this->syncPrograms($combo, $validated['program_ids']);
            $this->syncInfos($combo, $validated['infos'] ?? []);
        });

        return redirect()
            ->route('admin.combos.index')
            ->with('success', 'Tạo combo thành công.');
    }

    public function edit(Combo $combo): View
    {
        $combo->load(['translations', 'infos.translations', 'programs']);
        $programs = Program::query()->withTranslation()->orderByTranslation('sort')->get();

        return view('admin.combos.edit', compact('combo', 'programs'));
    }

    public function update(UpdateComboRequest $request, Combo $combo): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($combo, $validated) {
            $this->fillTranslations($combo, $validated);
            $combo->save();

            $this->syncPrograms($combo, $validated['program_ids']);
            $combo->infos()->delete();
            $this->syncInfos($combo, $validated['infos'] ?? []);
        });

        return redirect()
            ->route('admin.combos.edit', $combo)
            ->with('success', 'Cập nhật combo thành công.');
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        $combo->delete();

        return redirect()
            ->route('admin.combos.index')
            ->with('success', 'Xóa combo thành công.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillTranslations(Combo $combo, array $validated): void
    {
        $requiredLocale = (string) config('translatable.fallback_locale');

        foreach ((array) config('translatable.locales') as $locale) {
            $data = $validated['translations'][$locale] ?? null;
            $name = $data['name'] ?? null;
            $coverPath = $data['cover']['path'] ?? null;

            if ($locale !== $requiredLocale && empty($name) && empty($coverPath)) {
                continue;
            }

            $combo->fill([
                $locale => [
                    'name' => $name,
                    'cover' => $data['cover'] ?? null,
                ],
            ]);
        }
    }

    /**
     * @param  list<int>  $programIds
     */
    private function syncPrograms(Combo $combo, array $programIds): void
    {
        $syncData = [];

        foreach (array_values($programIds) as $index => $programId) {
            $syncData[(int) $programId] = ['sort' => $index];
        }

        $combo->programs()->sync($syncData);
    }

    /**
     * @param  list<array<string, mixed>>  $infos
     */
    private function syncInfos(Combo $combo, array $infos): void
    {
        $requiredLocale = (string) config('translatable.fallback_locale');

        foreach (array_values($infos) as $index => $infoData) {
            $info = new ComboInfo([
                'combo_id' => $combo->id,
                'sort' => $index,
                'icon' => $infoData['icon'],
            ]);
            $info->save();

            foreach ((array) config('translatable.locales') as $locale) {
                $text = $infoData['translations'][$locale]['text'] ?? null;

                if ($locale !== $requiredLocale && empty($text)) {
                    continue;
                }

                $info->fill([
                    $locale => [
                        'text' => $text,
                    ],
                ]);
            }

            $info->save();
        }
    }
}
