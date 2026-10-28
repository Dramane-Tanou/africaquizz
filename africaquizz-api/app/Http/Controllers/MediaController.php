<?php
namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private MediaService $mediaService) {}

    public function index(Request $request)
    {
        $query = Media::orderBy('created_at', 'desc');
        if ($request->filled('search')) {
            $query->where('filename', 'ilike', "%{$request->search}%");
        }
        $media = $query->limit(100)->get();
        return response()->json(['data' => ['data' => $media, 'total' => $media->count()]]);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:5120',
            'license' => 'nullable|string',
            'author' => 'nullable|string',
            'source_url' => 'nullable|url',
            'attribution_text' => 'nullable|string',
            'alt_text_fr' => 'nullable|string',
            'alt_text_en' => 'nullable|string',
        ]);

        try {
            $media = $this->mediaService->upload(
                $request->file('file'),
                $request->user('admin')?->id,
                $validated
            );
            return response()->json(['data' => $media], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        \Storage::disk($media->disk)->delete($media->path);
        $media->delete();
        return response()->json(['message' => 'Média supprimé.']);
    }
}