<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\UpdateProgramRequest;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\Program;

class ProgramController extends BaseController
{
    public function index()
    {
        // duration_seconds nằm ở video_translations ⇒ eager load đủ để cộng dồn (tránh N+1).
        $programs = Program::query()
            ->with(['translations', 'lessons.videos.translations'])
            ->withCount(['favorites', 'lessons'])
            ->orderBy('id')
            ->get();

        return view('admin.programs.index', compact('programs'));
    }

    public function edit(Program $program)
    {
        $program->load('translations');

        $lessons = $program->lessons()
            ->with('translations')
            ->withCount('favorites')
            ->orderBy('id')
            ->paginate(10);

        return view('admin.programs.edit', compact('program', 'lessons'));
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        $validated = $request->validated();

        $program->fill([
            'rating' => $validated['rating'] ?? null,
        ]);

        foreach ((array) config('translatable.locales') as $locale) {
            $data = $validated['translations'][$locale] ?? null;

            if ($data === null || ($data['name'] ?? null) === null) {
                continue;
            }

            $program->fill([
                $locale => [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'cover' => $data['cover'] ?? null,
                    'sort' => $data['sort'] ?? 0,
                ],
            ]);
        }

        $program->save();

        return redirect()
            ->route('admin.programs.edit', $program)
            ->with('success', 'Cập nhật bộ môn thành công.');
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return redirect()
            ->route('admin.programs.index')
            ->with('success', 'Xóa bộ môn thành công.');
    }
}
